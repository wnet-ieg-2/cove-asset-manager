<?php
/*
 * Plugin Name: COVE Asset Manager 
 * Version: 3.0.0
 * Plugin URI: http://www.thirteen.org/
 * Description: COVE Asset Manager
 * Author: William Tam, WNET
 * Author URI: http://ieg.wnet.org/
 * Requires at least: 4.6
 * Tested up to: 3.5.1
 * 
 * @package WordPress
 * @author William Tam
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

register_activation_hook(__FILE__ , 'cove_asset_manager_install');
register_deactivation_hook(__FILE__ , 'cove_asset_manager_uninstall');

// Include plugin class files
require_once( 'classes/class-cove-asset-manager.php' );
require_once( 'classes/class-cove-asset-manager-settings.php' );
require_once( 'classes/class-cove-asset-metaboxes.php' );

if ( !class_exists('PBS_Media_Manager_API_Client') ) {
  require_once( 'assets/php/PBS_Media_Manager_Client/class-PBS-Media-Manager-API-Client.php' );
}


// Include the YouTube server scripts
require_once( 'classes/class-youtube-oauth.php' );

// Instantiate necessary classes
global $plugin_obj;
$plugin_obj = new COVE_Asset_Manager( __FILE__ );
$plugin_settings_obj = new COVE_Asset_Manager_Settings( __FILE__ );

$youtube_oauth_obj = new WNET_Google_oAuth(__FILE__);

if ( !class_exists('COVE_API_Request') ) {
  require_once( 'classes/class-cove-request-api.php' );
}


//load up the metabox admin
function call_COVE_Asset_Metaboxes() {
  new COVE_Asset_Metaboxes( __FILE__ );
}
if ( is_admin() ) {
    add_action( 'load-post.php', 'call_COVE_Asset_Metaboxes' );
    add_action( 'load-post-new.php', 'call_COVE_Asset_Metaboxes' );
}


function coveam_get_video( $id ) {
// array to eventually return
	$videofields = array();

	// first lets get the stuff from the post meta
	$postmeta = get_post_custom($id);
	$videofields['title'] = $postmeta['_coveam_video_title'][0];
	$videofields['description'] = $postmeta['_coveam_description'][0];
  $videofields['status'] = $postmeta['_coveam_video_status'][0];
	$videofields['short_description'] = $postmeta['_coveam_shortdescription'][0];
	$videofields['airdate'] = $postmeta['_coveam_airdate'][0];
	$videofields['youtubeid'] = $postmeta['_coveam_youtube_id'][0];
	$videofields['coveplayerid'] = $postmeta['_coveam_cove_player_id'][0];
	$videofields['rights'] = $postmeta['_coveam_rights'][0];
	$videofields['fullprogram'] = $postmeta['_coveam_video_fullprogram'][0];
  $videofields['duration'] = $postmeta['_coveam_duration'][0];
	$videofields['video_override_url'] = $postmeta['_coveam_video_override_url'][0];
	// Most older videos don't have the cove/youtube status set, default to good if overall status set to publish
  if ($videofields['status'] == 'publish') {
    $videofields['covestatus'] = 'available';
    $videofields['youtubestatus'] = 'public';
  }
  if ($postmeta['_coveam_covestatus'][0]) {
    $videofields['covestatus'] = strtolower($postmeta['_coveam_covestatus'][0]);
  }
  if ($postmeta['_coveam_youtubestatus'][0]) {
    $videofields['youtubestatus'] = $postmeta['_coveam_youtubestatus'][0];
  }
  $videofields['slug'] = $postmeta['_coveam_video_slug'][0];
  $videofields['raw_video_url'] = $postmeta['_coveam_video_url'][0];
  $videofields['raw_image_url'] = $postmeta['_coveam_video_image'][0];

  if (isset ($postmeta['_coveam_metadata_json']) && isset ($postmeta['_coveam_metadata_json'][0])) {
    $fields_json = $postmeta['_coveam_metadata_json'][0];
    $morefields = json_decode($fields_json, true);
    foreach ($morefields as $field => $value) {
      $videofields[$field] = $value;
    }
  }

	// get the thumbnail url for the post 
	// TODO make this pull in the object so it can be queried for size
	// see get_intermediate_image_sizes
	$videofields['thumbnail'] = wp_get_attachment_url( get_post_thumbnail_id($id) );

	// return the list of cove topic slugs assigned to this video
	$cove_topics = get_the_terms( $id, 'cove_topics' );
  if ($cove_topics && ! is_wp_error( $cove_topics ) ) {
    $topic_array = array();
    foreach ( $cove_topics as $cove_topic ) {
      $topic_array[] = $cove_topic->slug;
    }
  }
	$videofields['cove_topics'] = $topic_array; 

  // return the list of tag slugs assigned to this post
  $thesetags = get_the_terms( $id, 'post_tag' );
  if ($thesetags && ! is_wp_error( $thesetags ) ) {
    $thistag_array = array();
    foreach ( $thesetags as $tag ) {
      $thistag_array[] = $tag->slug;
    }
  }

  $videofields['tags'] = $thistag_array;

	return $videofields;
}

function coveam_update_video_status($id) {
  if (function_exists('coveam_get_video')) {
    $video = coveam_get_video( $id );
    $video_status = strtolower($video["status"]);
    $cove_status = strtolower($video["covestatus"]);
    $cove_rights = strtolower($video["rights"]);
    $cove_airdate = $video["airdate"];
    $youtube_status = strtolower($video["youtubestatus"]);
    $video_override_url = $video["video_override_url"];
    if ($cove_status) {
      if ($cove_status == 'available') {
        $video_status = 'publish';
      } else {
        $video_status = $cove_status;
      }
    }
    if (!in_array($cove_status, array('expired', 'not_yet_available'))) {
      if ($youtube_status) {
        if ($youtube_status == 'public') {
          $video_status = 'publish';
        }
      }
      if ($video_override_url != '') {
        $video_status = 'publish';
      }  
    }
    if ($cove_rights == "limited") {
      $video_expire_date = $cove_airdate;
      $video_expire_date = strtotime($video_expire_date);
      $video_expire_date = strtotime("+30 day", $video_expire_date);
      if ((time() > $video_expire_date)) {
        $video_status = "expired";
      }
    }
    update_post_meta($id, '_coveam_video_status', $video_status);
    return 'cove_status = ' . $cove_status. ', youtube_status = ' . $youtube_status ;
  }
}




function coveam_render_player( $id, $args = array() ) {
  global $whereami;
  $defaults = array('player_chrome' => 'show',
                    'show_related' => 'hide',
                    'display' => 'static'
                  );
  if (! is_array($args)) {
    $args = array();
  }
  $args = array_merge($defaults, $args);

  $linkedpostid = $id;
  if (function_exists('wt_p2p_return_related_postid')) {
    if (get_post_type($id) == 'videos') {
      $linkedpost = wt_p2p_return_related_postid($id,'p2p_to','featured_video',1);
      $linkedpostid = $linkedpost[0][0];
    } else {
      $videopost = wt_p2p_return_related_postid($id,'p2p_from','featured_video',1);
      $id = $videopost[0][0];
    }
  }
	
  // generate a unique id for the div.  It is possible for the same video to appear multiple times on the page so add a rand
  $div_id = "coveam_player_" . $id . "_" . rand(10,100);
	
  // get all the data about the video -- maybe there won't be any data
  if (function_exists('coveam_get_video')) {
    $video = coveam_get_video( $id );
    $available = true;
    $error = "We're sorry, no data exists for this video(" . $id . ")";
    if (strtolower($video["rights"]) == "limited") {
      $video_expire_date = $video["airdate"];
      $video_expire_date = strtotime($video_expire_date);
      $video_expire_date = strtotime("+30 day", $video_expire_date);
      if ((time() > $video_expire_date)) {
        $available = false;
        $error = 'We\'re sorry, the rights for this video have expired.';
		if ( is_feed() ) {
			return $error;
		}
      }
    } 
    
    if ( is_feed() ) {
		if ($video && $available) {
			if ($video['coveplayerid'] && ($video['covestatus'] == 'available')) {
				if ($whereami == "rss-description") {
					$playlink = '<a href="http://video.pbs.org/video/' . $video['coveplayerid']. '/">[Watch Video]</a>';
				} else {
					$playlink = "<iframe class='partnerPlayer' frameborder='0' marginwidth='0' marginheight='0' scrolling='no' width='100%' height='100%' src='http://player.pbs.org/widget/partnerplayer/" . $video['coveplayerid'] . "/?start=0&end=0&chapterbar=false&endscreen=false' allowfullscreen></iframe>";
				}		 
			} elseif (($video['youtubestatus'] == "public") && $video['youtubeid'] ) {
				if ($whereami == "rss-description") {
					$playlink = '<a href="http://www.youtube.com/watch?v=' . $video['youtubeid'] . '">[Watch Video]</a>';
				} else {
					$playlink = '<iframe width="100%" height="100%" src="http://www.youtube.com/embed/' . $video['youtubeid'] . '" frameborder="0" allowfullscreen></iframe>';
				}          
			} elseif ($video['video_override_url']) {
				if ($whereami == "rss-description") {
					$playlink = 'There should be an embedded item here. Please visit the original post to view it.';
				} else {
					$playlink = wp_oembed_get( $video['video_override_url'] );
				}
			} else {
				$playlink = "This video is not currently available.";
			}
			return $playlink;
		}
    } else {
    // print out a div with a class, a unique id, title, description, youtube_id, cove_id
    $html = '<div class="video-wrapper coveam-videoplayer" id="' . $div_id . '">';
    
    //check if we've got anything
    if ($video && $available) {
      $html .= '<div class="coveam_vars" style="display:none;">';
      $covepreferred = get_option( 'coveam_cove_preferred', 'true' );
      $html .= '<span class="coveam_videoid">' . $id . '</span>';
      $html .= '<span class="coveam_linkedpostid">' . $linkedpostid . '</span><span class="coveam_excluded">' . $linkedpostid . '</span>';
	    $html .= '<span class="coveam_videotitle">' . $video['title'] . '</span>';
      $html .= '<span class="coveam_videodescription">' . $video['description'] . '</span>';	
      $html .= '<span class="coveam_airdate">' . $video['airdate'] . '</span>';

      // these are blank on purpose, only updated by jquery
      $html .= '<span class="coveam_autoplay">disabled</span>';

      if ($args['player_chrome'] == 'hide') {
        $html .= '<span class="coveam_playerchrome">hide</span>';
	    }
	    // check status of cove and youtube and if there's a server-setting to use cove
      $html .= '<span class="coveplayerid">';
      if ($video['coveplayerid'] && ($video['covestatus'] == 'available')) {
		    $html .= $video['coveplayerid']; 
	    }
      $html .= '</span><span class="youtubeid">';
	    if (($video['youtubestatus'] == "public") && $video['youtubeid']) {
        $html .= $video['youtubeid'];
      }
	    $html .= '</span><span class="coveam_covepreferred">' . $covepreferred . '</span><span class="coveam_video_override_encoded">';
      if ($video['video_override_url']) {
        $html .= rawurlencode( wp_oembed_get( $video['video_override_url'] ) );
      }
      $html .= '</span>';
      if ($args['show_related'] == 'show') {
        $relatedvideos = wt_p2p_related_videos($id, 3, null);
        if(is_array($relatedvideos)){
          $vidcount=1;
          foreach($relatedvideos as $relatedvideo) {
            $html .= '<span class="coveam_relatedvid_linkedpostid_' . $vidcount . '">' . $relatedvideo['ID'] . '</span>';
            $html .= '<span class="coveam_relatedvid_id_' . $vidcount . '">' . $relatedvideo['video_post'] . '</span>';
            $html .= '<span class="coveam_relatedvid_permalink_' . $vidcount . '">' . $relatedvideo['permalink'] . '</span>';
            $html .= '<span class="coveam_relatedvid_title_' . $vidcount . '">' . $relatedvideo['title'] . '</span>';
            $html .= '<span class="coveam_relatedvid_description_' . $vidcount . '">' . $relatedvideo['description'] . '</span>';
            $html .= '<span class="coveam_relatedvid_airdate_' . $vidcount . '">' . $relatedvideo['airdate'] . '</span>';
            $html .= '<span class="coveam_relatedvid_img_' . $vidcount . '">' . $relatedvideo['thumbnail_url'] . '</span>';
            $html .= '<span class="coveam_relatedvid_coveplayerid_' . $vidcount . '">' . $relatedvideo['coveplayerid'] . '</span>';
            $html .= '<span class="coveam_relatedvid_youtubeid_' . $vidcount . '">' . $relatedvideo['youtubeid'] . '</span>';
            $html .= '<span class="coveam_relatedvid_video_override_encoded_' . $vidcount . '">' . rawurlencode( wp_oembed_get($relatedvideo['video_override_url']) ) . '</span>';
            $vidcount++;
          }
        }
      }
	    $html .= '</div><div class="coveam_player"></div></div>';
      if ($args['display'] == "ajax") {
        //fire the display player script if called after page load ie via ajax
	      $html .= '<script type="text/javascript"> jQuery(function(){ jQuery("#' . $div_id . '").coveamDisplayPlayer(); }); </script>';
      }
    } else {
	    $html .= '<div class="errormessage">' . $error . '</div></div>';
    }
    return $html;
    }
  } else {
    return false;
  }
}

function coveam_remove_watchvideo_from_excerpt($excerpt) {
  return preg_replace("/\[Watch Video\](.*)/", "$1", $excerpt);
}
add_filter( 'get_the_excerpt', 'coveam_remove_watchvideo_from_excerpt' );

add_action( 'wp_ajax_coveam_ajax_relatedvideos', 'coveam_ajax_relatedvideos' );
add_action( 'wp_ajax_nopriv_coveam_ajax_relatedvideos', 'coveam_ajax_relatedvideos' );

function coveam_ajax_relatedvideos() {
  $videoid = ( isset( $_POST['videoid'] ) ) ? $_POST['videoid'] : '';
  $excluded = ( isset( $_POST['excluded'] ) ) ?  $_POST['excluded'] : '';
  $returnarray = wt_p2p_related_videos($videoid, 3, $excluded);
  if ($returnarray ) {
    echo json_encode($returnarray);
  } else {
    echo json_encode($error);
  }
  die();
}

add_action( 'wp_ajax_coveam_sign_aws_request', 'coveam_sign_aws_request' );

function coveam_sign_aws_request() {
    $slug = $_GET['slug'];
    $fileinfo = $_GET['fileinfo'];
    $return = 'config params needed';
    if (get_option( 'coveam_aws_key' ) && get_option( 'coveam_aws_secret_key' ) && get_option ( 'coveam_s3_bucket' )) {
      $SIGNPUT_S3_KEY=get_option( 'coveam_aws_key' );
      $SIGNPUT_S3_SECRET=get_option( 'coveam_aws_secret_key' );
      $SIGNPUT_S3_BUCKET= '/' . get_option( 'coveam_s3_bucket' );


      $EXPIRE_TIME=(60 * 5); // 5 minutes
      $S3_URL='https://s3.amazonaws.com';
      $objectName='/' . $slug;

      if (get_option( 'coveam_s3_bucket_dir' ) ) {
        $objectName = '/' . get_option( 'coveam_s3_bucket_dir' ) . $objectName;
      }

      $mimeType=$fileinfo;
      $expires = time() + $EXPIRE_TIME;
      $amzHeaders= "x-amz-acl:public-read";
      $stringToSign = "PUT\n\n$mimeType\n$expires\n$amzHeaders\n$SIGNPUT_S3_BUCKET$objectName";
      $sig = urlencode(base64_encode(hash_hmac('sha1', $stringToSign, $SIGNPUT_S3_SECRET, true)));

      $return = urlencode("$S3_URL$SIGNPUT_S3_BUCKET$objectName?AWSAccessKeyId=$SIGNPUT_S3_KEY&Expires=$expires&Signature=$sig");
    }
    echo $return;
    die;
  }

function coveam_daily_expire_videos() {
    $expire_vids_before_this_date = strtotime("-30 day", time());
    $ids_to_expire = array();
    $args = array(
      'post_status' => 'publish',
      'post_type' => 'videos',
      'meta_query' => array(
        'relation' => 'AND',
        array(
          'key' => '_coveam_rights',
          'value' => 'Limited',
          'compare' => '='
        ),
        array(
          'key' => '_coveam_video_status',
          'value' => 'publish',
          'compare' => '='
        )
      ),
      'date_query' => array(
        array(
          'after' => '2 month ago'
        )
      ),
      'posts_per_page' => 200,
      'orderby' => 'date',
      'order' => 'ASC'
    );
    $returnlist = '';
    $youtubelist = '';
    $limitedvideos = new WP_Query($args);
    if ($limitedvideos->have_posts()){
      while ( $limitedvideos->have_posts() ) : $limitedvideos->the_post();
        $thisairdate = get_post_meta($limitedvideos->post->ID, '_coveam_airdate', true);
        if ($thisairdate) {
          $rawairdate = strtotime($thisairdate);
          if ($rawairdate < $expire_vids_before_this_date && $rawairdate > 0){
            array_push($ids_to_expire, $limitedvideos->post->ID);
            $returnlist .= 'vidid = ' . $limitedvideos->post->ID . ' date = ' . $thisairdate . "\n";
          }
        }
      endwhile;
    }
    // init the youtube class
    $youtube_oauth= new WNET_Google_oAuth(__FILE__);

    // first, we go through the videos and expire them in WordPress.
    foreach ($ids_to_expire as $id) {
      update_post_meta($id, '_coveam_video_status', 'expired');
      // second, see if there are youtube videos to expire
      $youtubeid = get_post_meta($id, '_coveam_youtube_id', true);
      if ($youtubeid) {
        $privacy_set = $youtube_oauth->submit_youtube_expire_request($youtubeid);
        if ($privacy_set === true){
          update_post_meta($id, '_coveam_youtubestatus', 'private');
          $youtubelist .= ' http://www.youtube.com/watch?v=' . $youtubeid;
        } else {
          $youtubelist .= "\nError: $youtubeid $privacy_set \n";
        }
      }
    }


    // sanity check lets return the list of ids
    $subject = 'coveam videos expired on' . get_bloginfo('name'); 
    $message = 'expiration date: ' . date('Y-m-d', $expire_vids_before_this_date);
    $message .= "\n these videos were expired: " . $returnlist;
    if ($youtubelist != '') {
      $message .= "\n these youtube videos were set to private: " . $youtubelist;
    }
    $to = get_option('coveam_notify_email');
    if (!empty($to)) {
      wp_mail( $to, $subject, $message);
    }
    return $ids_to_expire;
}


function coveam_check_inprogress_ingest_videos() {
    $plugin_obj = new COVE_Asset_Manager( __FILE__ );
    $ignore_vids_before_this_date = strtotime("-3 day", time());
    $inprogress_videos = array();

    // only do this for the legacy stuff
    if (!$plugin_obj->use_media_manager) {
      //first lets find the videos that have a task url
      $args = array(
        'post_type' => 'videos',
        'post_status' => array('publish', 'pending', 'draft', 'future'),
        'meta_key' => '_coveam_ingest_task',
        'posts_per_page' => 100,
        'orderby' => 'date',
        'order' => 'DESC'
      );
      $pendingvideos = new WP_Query($args);
      if ($pendingvideos->have_posts()){
        while ( $pendingvideos->have_posts() ) : $pendingvideos->the_post();
          $postid = $pendingvideos->post->ID;
          array_push($inprogress_videos, $postid);
          $task_url = get_post_meta($postid, '_coveam_ingest_task', true);
          coveam_get_batch_ingest_task_status($task_url, $postid);
        endwhile;
      }
      // next lets find the videos that have a success timestamp

      $newargs = array(
        'post_type' => 'videos',
        'post_status' => array('publish', 'pending', 'draft', 'future'),
        'meta_key' => '_coveam_ingest_success_timestamp',
        'posts_per_page' => 100,
        'orderby' => 'date',
        'order' => 'DESC'
      );
      $timedvideos = new WP_Query($newargs);
      $waiting_videos = array();
      if ($timedvideos->have_posts()){
        while ( $timedvideos->have_posts() ) : $timedvideos->the_post();
          $postid = $timedvideos->post->ID;
          array_push($waiting_videos, $postid);
          $guid = get_post_meta($postid, '_coveam_video_asset_guid', true);
          coveam_update_status_from_guid($postid, $guid);
        endwhile;
      }
    // end conditional on legacy stuff
    } 

    // see if there are youtube videos processing 
    $youtubeargs = array(
      'post_type' => 'videos',
      'post_status' => array('publish', 'pending', 'draft', 'future'),
      'meta_query' => array(
        array(
          'key' => '_coveam_youtubestatus',
          'value' => array('uploaded','processing'),
          'compare' => 'IN'
        )
      ),
      'posts_per_page' => 100,
      'orderby' => 'date',
      'order' => 'DESC'
    );
    $youtubevideos = new WP_Query($youtubeargs);
    $yt_processedvideos = array();
    if ($youtubevideos->have_posts()){
      // init the youtube class
      $youtube_oauth= new WNET_Google_oAuth(__FILE__);


      while ( $youtubevideos->have_posts() ) : $youtubevideos->the_post();
        $postid = $youtubevideos->post->ID;
        $processed = $youtube_oauth->update_youtube_status_from_youtube($postid);
        if ($processed) {
          array_push($yt_processedvideos, $postid);
        } else {
          array_push($inprogress_videos, $postid);
        }
      endwhile;
    }



    
    // sanity check lets return the list of ids
    $json_response = json_encode($affected_videos);
    echo $json_response;
    /* 
    $subject = 'coveam_check_inprogress_ingest_videos on ' . get_bloginfo('name');
    $message = 'These posts inprogress: ' . join(",", $inprogress_videos);
    $message .= 'These videos ready with timestamp: ' . join(",", $waiting_videos);
    $message .= 'these youtube videos affected: ' . join(",", $yt_processedvideos);
    if ((count($inprogress_videos) + count($waiting_videos) + count($yt_processedvideos)) > 0 ) {
    	//wp_mail( 'wmgtam@gmail.com', $subject, $message);
    }
    */
}


add_action( 'wp_ajax_coveam_check_inprogress_ingest_videos', 'coveam_check_inprogress_ingest_videos' );

//schedule events
//note -- per the documentation from wp, best for schedule hooks to have all lowercase and no underscores
//http://codex.wordpress.org/Function_Reference/wp_schedule_event

 add_filter( 'cron_schedules', 'cron_add_fiveminute' );
 
 function cron_add_fiveminute( $schedules ) {
  // Adds once weekly to the existing schedules.
  $schedules['fiveminute'] = array(
    'interval' => 300,
    'display' => __( 'Every Five Minutes ' )
  );
  return $schedules;
 }


add_action( 'coveamdailyevent', 'coveam_daily_expire_videos' );

add_action( 'coveamfiveminuteevent', 'coveam_check_inprogress_ingest_videos' );
add_action( 'wp', 'coveam_setup_schedule' );

function coveam_setup_schedule() {
  if ( ! wp_next_scheduled( 'coveamdailyevent' ) ) {
    wp_schedule_event( time(), 'daily', 'coveamdailyevent');
  }
  if ( ! wp_next_scheduled( 'coveamfiveminuteevent' ) ) {
    wp_schedule_event( time(), 'fiveminute', 'coveamfiveminuteevent');
  }

}

# on install/activate
function cove_asset_manager_install() {
  $plugin_obj = new COVE_Asset_Manager( __FILE__ );

  // setup the daily episode generation
  $ep_gen = $plugin_obj->do_daily_episode_generate();
  if (!empty($ep_gen['errors'])) {
    error_log(json_encode($ep_gen));
  } else {
    error_log('scheduled daily episode generation');
  }
}
# on uninstall/deactivate
function cove_asset_manager_uninstall() {
  $plugin_obj = new COVE_Asset_Manager( __FILE__ );
 
  // remove any scheduled episode generation
  $plugin_obj->clear_scheduled_episode_generation();
}



