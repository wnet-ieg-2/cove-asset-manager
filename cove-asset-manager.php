<?php
/*
 * Plugin Name: COVE Asset Manager 
 * Version: 3.5 -- adds DRM code for partner player
 * Plugin URI: https://www.thirteen.org/
 * Description: COVE Asset Manager
 * Author: William Tam, WNET
 * Author URI: https://www.wnet.org/
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

$youtube_oauth_obj = new WNET_Google_oAuth(__FILE__);



if ( is_admin() ) {
  $plugin_metaboxes_obj = new COVE_Asset_Metaboxes( __FILE__ );
  $plugin_settings_obj = new COVE_Asset_Manager_Settings( __FILE__ );
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
	$defaults = array( 'amp' => false, 'rss' => false );
	if ( ! is_array($args) ) { $args = array(); }
	$args = array_merge($defaults, $args);

	$player_to_display = ""; $extraClass = "";

	// get all the data about the video -- maybe there won't be any data
	if (function_exists('coveam_get_video')) {
    	$video = coveam_get_video( $id );
    	$available = true;
    	$error = "We're sorry, no data exists for this video (" . $id . ").";
    	if (strtolower($video["rights"]) == "limited") {
      		$video_expire_date = $video["airdate"];
      		$video_expire_date = strtotime($video_expire_date);
      		$video_expire_date = strtotime("+30 day", $video_expire_date);
      		if ((time() > $video_expire_date)) {
        		$available = false;
        		$error = 'We\'re sorry, the rights for this video have expired.';
		    	if ( is_feed() ) { return $error; }
      		}
		}		
    }

	if ($video && $available) {
		// when the video override is set we use that by default... else we look at the cove / youtube results and use the preferred player set on the options page.

		$available_players = array();
		$preferred_player = get_option('coveam_preferred_player') ? get_option('coveam_preferred_player') : 'cove';
		if ( !empty($video['video_override_url']) ) { $available_players[] = 'alternate'; }	 
		else {
			if ( $video['covestatus'] == 'available' && !empty($video['coveplayerid']) ) { $available_players[] = 'cove'; }
			if ( $video['youtubestatus'] == "public" && !empty($video['youtubeid']) ) { $available_players[] = 'youtube'; }
		}

		$player_to_display = in_array($preferred_player, $available_players) ? $preferred_player : $available_players[0]; 
		if (empty($player_to_display)) {
      		// no available players
      		return false;
    	} 
	}
	else {
		return '<div class="cove-am errormessage" style="border: 1px solid silver; padding: 2em; text-align: center;">' . $error . '</div>';
	}


	if (!empty($player_to_display)) {
		if ( is_feed() || !empty($args['rss']) ) {
			if ($player_to_display == 'cove') {
				if ($whereami == "rss-description") {$playlink = '<a href="https://video.pbs.org/video/' . $video['coveplayerid']. '/">[Watch Video]</a>'; }
				else { $playlink = "<iframe class='partnerPlayer' frameborder='0' marginwidth='0' marginheight='0' scrolling='no' width='100%' height='100%' src='https://player.pbs.org/widget/partnerplayer/" . $video['coveplayerid'] . "/?start=0&end=0&chapterbar=false&endscreen=false' allow='encrypted-media' allowfullscreen></iframe>"; }		 
			} elseif ( $player_to_display == 'youtube' ) {
				if ($whereami == "rss-description") { $playlink = '<a href="http://www.youtube.com/watch?v=' . $video['youtubeid'] . '">[Watch Video]</a>'; } 
				else { $playlink = '<iframe width="100%" height="100%" src="http://www.youtube.com/embed/' . $video['youtubeid'] . '?rel=0" frameborder="0" allowfullscreen></iframe>'; }          
			} elseif ($video['video_override_url']) {
				if ($whereami == "rss-description") {$playlink = 'There should be an embedded item here. Please visit the original post to view it.'; } 
				else { $playlink = wp_oembed_get( $video['video_override_url'] );	}
			} else {
				$playlink = "This video is not currently available.";
			}
			return $playlink;
		} else {
			// return the regular player embeds...
			if ($player_to_display == 'cove') {
				$playerhtml = '<div class="video-wrap cove-am media-manager" style="aspect-ratio: 16/9; position:relative;"><iframe class="partnerPlayer" marginwidth="0" marginheight="0" scrolling="no" style="" src="https://player.pbs.org/widget/partnerplayer/' . $video['coveplayerid'] . '/?start=0&amp;end=0&amp;chapterbar=false&amp;endscreen=false&amp;topbar=true&amp;autoplay=false" allow="encrypted-media" allowfullscreen="" width="100%" height="100%" frameborder="0"></iframe></div>'; 
			}
			else if ($player_to_display == 'youtube') {
				$playerhtml = '<div class="video-wrap cove-am youtube" style="aspect-ratio: 16/9; position:relative;" ><iframe width="100%" height="100%" style="" src="https://www.youtube.com/embed/' . $video['youtubeid'] . '?enablejsapi=1&rel=0" frameborder="0" allowfullscreen></iframe></div>';
			}
			else if ($player_to_display == 'alternate') {
				if (strpos($video['video_override_url'], 'facebook')) { $extraClass = "facebook"; }
				else if (strpos($video['video_override_url'], 'youtube')) { $extraClass = "youtube"; }
				$playerhtml = '<div class="video-wrap cove-am oembed-video ' . $extraClass . '">' . wp_oembed_get( $video['video_override_url'] ) . '</div>';
			}
	
			if (!empty($playerhtml)) {
				return $playerhtml;
			}
	
		}
	}
	else {
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
    $allowed_post_types = get_option('coveam_showonposttypes');
    $args = array(
      'post_status' => 'publish',
      'post_type' => $allowed_post_types,
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

    if ($returnlist || $youtubelist) {
      // only send an email if actual videos were expired
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
    }
    // sanity check lets return the list of ids
    return $ids_to_expire;
}


function coveam_check_inprogress_ingest_videos() {
    $plugin_obj = new COVE_Asset_Manager( __FILE__ );
    $ignore_vids_before_this_date = date("Y-m-d", strtotime("-2 day", time()));
    $inprogress_videos = array();

    $allowed_post_types = get_option('coveam_showonposttypes');

    // see if there are youtube videos processing 
    $youtubeargs = array(
      'post_type' => $allowed_post_types,
      'post_status' => array('publish', 'pending', 'draft', 'future'),
      'meta_query' => array(
        array(
          'key' => '_coveam_youtubestatus',
          'value' => array('uploaded','processing','private'),
          'compare' => 'IN'
        )
      ),
      'date_query' => array(
        array('after' => $ignore_vids_before_this_date)
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
    /*
    $json_response = json_encode($affected_videos);
    echo $json_response;
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



