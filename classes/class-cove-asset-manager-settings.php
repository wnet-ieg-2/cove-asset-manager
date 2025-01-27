<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class COVE_Asset_Manager_Settings {
	private $dir;
	private $file;
  private $plugin_url;
	private $assets_dir;
	private $assets_url;
  private $plugin_obj;

	public function __construct( $file ) {
		$this->dir = dirname( $file );
		$this->file = $file;
    $this->plugin_url = esc_url( trailingslashit( plugins_url( '/', $file ) ) );
		$this->assets_dir = trailingslashit( $this->dir ) . 'assets';
		$this->assets_url = esc_url( trailingslashit( plugins_url( '/assets/', $file ) ) );
    $this->plugin_obj = new COVE_Asset_Manager( $file );


		// Register plugin settings
		add_action( 'admin_init' , array( $this , 'register_settings' ) );

		// Add settings page to menu
		add_action( 'admin_menu' , array( $this , 'add_menu_item' ) );

		// Add settings link to plugins page
		add_filter( 'plugin_action_links_' . plugin_basename( $this->file ) , array( $this , 'add_settings_link' ) );

    add_action( 'admin_enqueue_scripts', array( $this, 'setup_custom_scripts' ) );

    // bulk importers
    add_action( 'wp_ajax_bulk_import_media_manager_asset_and_episode_ids', array( $this, 'ajax_bulk_import_media_manager_asset_and_episode_ids'), 10, 2);
    add_action( 'wp_ajax_bulk_match_media_manager_episodes', array( $this, 'ajax_bulk_match_media_manager_episodes'), 10, 4);

    // check whenever setting for daily ep autogenerate is changed
    add_action( 'update_option_coveam_mm_episode_autocreate', array( $this, 'update_episode_autocreate_status' ), 10, 2);

    // Run this after show id is updated
    add_action( 'update_option_coveam_mm_show_id', array( $this, 'run_after_show_id_changed'), 10, 2);


	}
	
	public function add_menu_item() {
		add_options_page( 'COVE Asset Manager Settings' , 'COVE Asset Manager Settings' , 'manage_options' , 'cove_asset_manager_settings' ,  array( $this , 'settings_page' ) );
	}

	public function add_settings_link( $links ) {
		$settings_link = '<a href="options-general.php?page=cove_asset_manager_settings">Settings</a>';
  		array_push( $links, $settings_link );
  		return $links;
	}

  public function setup_custom_scripts() {
    wp_enqueue_script( 'pbs_media_manager_settings_admin', $this->assets_url . 'js/mm_settings_page.js', array( 'jquery'), 1, true);
    wp_enqueue_media();
  }


	public function register_settings() {
		
		// Add settings section
    add_settings_section( 'main_settings' , __( 'Modify plugin settings' , 'cove-asset-manager' ) , array( $this , 'main_settings' ) , 'cove_asset_manager_settings' );
		
		// Add settings fields

	add_settings_field( 'coveam_preferred_player', 'Preferred Player', array( $this, 'settings_field_radio'), 'cove_asset_manager_settings', 'main_settings', array( 'field' => 'coveam_preferred_player', 'options' => array('cove','youtube'), 'note' => 'When available the preferred player will be displayed with fallback to other player. The Alternative Video URL field takes precedence over this selection.' ) );
	register_setting( 'cove_asset_manager_settings' , 'coveam_preferred_player' );


    

    add_settings_field( 'coveam_showonposttypes' , __( 'Show the COVE metaboxes on these post types:' , 'cove-asset-manager' ) , array( $this , 'settings_field_allowed_post_types' )  , 'cove_asset_manager_settings' , 'main_settings'  , array('coveam_showonposttypes', '') );
    register_setting( 'cove_asset_manager_settings' , 'coveam_showonposttypes' );


		add_settings_field( 'coveam_s3_bucket' , __( 'S3 Bucket to use:' , 'cove-asset-manager' ) , array( $this , 'settings_field' )  , 'cove_asset_manager_settings' , 'main_settings' , array('coveam_s3_bucket', 'ex: pbs-ingest') );
		register_setting( 'cove_asset_manager_settings' , 'coveam_s3_bucket' );

    add_settings_field( 'coveam_s3_bucket_dir' , __( '(Optional) S3 Directory or subdirectory:' , 'cove-asset-manager' ) , array( $this , 'settings_field' )  , 'cove_asset_manager_settings' , 'main_settings' , array('coveam_s3_bucket_dir', 'ex: uploads') );
    register_setting( 'cove_asset_manager_settings' , 'coveam_s3_bucket_dir' );

		add_settings_field( 'coveam_aws_key' , __( 'AWS Access Key:' , 'cove-asset-manager' ) , array( $this , 'settings_field' )  , 'cove_asset_manager_settings' , 'main_settings' , array('coveam_aws_key', '') );
		register_setting( 'cove_asset_manager_settings' , 'coveam_aws_key' );

    add_settings_field( 'coveam_aws_secret_key' , __( 'AWS Secret Key:' , 'cove-asset-manager' ) , array( $this , 'settings_field' )  , 'cove_asset_manager_settings' , 'main_settings' , array('coveam_aws_secret_key', '') );
    register_setting( 'cove_asset_manager_settings' , 'coveam_aws_secret_key' );

    add_settings_field( 'coveam_s3_proxy' , __( 'Proxy/public hostname (optional)' , 'cove-asset-manager' ) , array( $this , 'settings_field' )  , 'cove_asset_manager_settings' , 'main_settings' , array('coveam_s3_proxy', 'The hostname for unsecured web requests for files uploaded to your S3 bucket if direct web traffic to your S3 bucket is not allowed and has been put behind a proxy or CDN.') );
    register_setting( 'cove_asset_manager_settings' , 'coveam_s3_proxy' );


    add_settings_field( 'coveam_youtube_username' , __( 'YouTube login username:' , 'cove-asset-manager' ) , array( $this , 'settings_field' )  , 'cove_asset_manager_settings' , 'main_settings' , array('coveam_youtube_username', 'The account you login with to manage your YouTube channel ex: newshour@gmail.com') );
    register_setting( 'cove_asset_manager_settings' , 'coveam_youtube_username' );

    add_settings_field( 'coveam_youtube_channel_email' , __( 'YouTube Channel email:' , 'cove-asset-manager' ) , array( $this , 'settings_field' )  , 'cove_asset_manager_settings' , 'main_settings' , array('coveam_youtube_channel_email', '(Optional) If your channel is owned by a "Brand" or "Google+" account different from your login account, put that email in here. ex: something-0069@pages.plusgoogle.com') );
    register_setting( 'cove_asset_manager_settings' , 'coveam_youtube_channel_email' );

    add_settings_field( 'coveam_youtube_default_text' , __( 'YouTube video default description:' , 'cove-asset-manager' ) , array( $this , 'settings_field' )  , 'cove_asset_manager_settings' , 'main_settings' , array('coveam_youtube_default_text', '(Optional) Default text to be inserted when uploading a video to YouTube.  This will be appended to any description on ingest.  Use \n for newlines.') );
    register_setting( 'cove_asset_manager_settings' , 'coveam_youtube_default_text' );


    add_settings_field( 'coveam_google_backend_key' , __( 'Google API key for backend application:' , 'cove-asset-manager' ) , array( $this , 'settings_field' )  , 'cove_asset_manager_settings' , 'main_settings' , array('coveam_google_backend_key', 'You must register a separate "application" with Google specific to the URL of this server for automated server checks of YouTube status and expiring videos') );
    register_setting( 'cove_asset_manager_settings' , 'coveam_google_backend_key' );

    add_settings_field( 'coveam_google_backend_secret' , __( 'Google backend API secret:' , 'cove-asset-manager' ) , array( $this , 'settings_field' )  , 'cove_asset_manager_settings' , 'main_settings' , array('coveam_google_backend_secret', 'Get from the application settings page on Google') );
    register_setting( 'cove_asset_manager_settings' , 'coveam_google_backend_secret' );

    add_settings_field( 'coveam_google_redirect_uri' , __( 'Google oAuth Redirect URI:' , 'cove-asset-manager' ) , array( $this , 'settings_field' )  , 'cove_asset_manager_settings' , 'main_settings' , array('coveam_google_redirect_uri', 'Get from same page, by default should be ' . $this->plugin_url . 'oauth2callback.php .  This URL MUST be in the app settings page AND be viewable without error in a browser, preferably a blank page') );
    register_setting( 'cove_asset_manager_settings' , 'coveam_google_redirect_uri' );

    add_settings_field( 'coveam_mm_show_id' , 'PBS Media Manager Show ID' , array( $this , 'settings_field' )  , 'cove_asset_manager_settings' , 'main_settings' , array('coveam_mm_show_id', 'content id for this show, get from PBS Media Manager console') );
    register_setting( 'cove_asset_manager_settings' , 'coveam_mm_show_id' );

    add_settings_field( 'coveam_mm_api_key' , 'PBS Media Manager API key' , array( $this , 'settings_field' )  , 'cove_asset_manager_settings' , 'main_settings' , array('coveam_mm_api_key', 'Get from PBS') );
    register_setting( 'cove_asset_manager_settings' , 'coveam_mm_api_key' );

    add_settings_field( 'coveam_mm_api_secret' , 'PBS Media Manager API secret' , array( $this , 'settings_field' )  , 'cove_asset_manager_settings' , 'main_settings' , array('coveam_mm_api_secret', 'Get from PBS') );
    register_setting( 'cove_asset_manager_settings' , 'coveam_mm_api_secret' );

    add_settings_field( 'coveam_mm_api_endpoint' , 'PBS Media Manager API endpoint' , array( $this , 'settings_field' )  , 'cove_asset_manager_settings' , 'main_settings' , array('coveam_mm_api_endpoint', 'Staging: https://media-staging.services.pbs.org/api/v1 Prod: https://media.services.pbs.org/api/v1') );
    register_setting( 'cove_asset_manager_settings' , 'coveam_mm_api_endpoint' );

    add_settings_field( 'coveam_mm_episode_autocreate' , __( 'Auto-create an episode every morning' , 'cove-asset-manager' ) , array( $this , 'settings_field' )  , 'cove_asset_manager_settings' , 'main_settings' , array('coveam_mm_episode_autocreate', 'Making this true will automatically generate a new episode post and media manager episode every weekday morning') );
    register_setting( 'cove_asset_manager_settings' , 'coveam_mm_episode_autocreate' );

    add_settings_field( 'coveam_mm_episode_autocreate_weekend' , __( 'Auto-create weekend episodes' , 'cove-asset-manager' ) , array( $this , 'settings_field' )  , 'cove_asset_manager_settings' , 'main_settings' , array('coveam_mm_episode_autocreate_weekend', 'Making this true will automatically generate a new episode post and media manager episode weekend mornings also') );
    register_setting( 'cove_asset_manager_settings' , 'coveam_mm_episode_autocreate_weekend' );


    add_settings_field( 'coveam_mm_episode_autotitle' , __( 'Title template for auto-created episodes' , 'cove-asset-manager' ) , array( $this , 'settings_field' )  , 'cove_asset_manager_settings' , 'main_settings' , array('coveam_mm_episode_autotitle', 'Use all-uppercase word "DATESTRING" as a placeholder for the date. Use all-uppercase word "WEEKENDSTRING" if you want "Weekend" inserted on weekend titles. ex: PBS Newshour WEEKENDSTRING full episode for DATESTRING') );
    register_setting( 'cove_asset_manager_settings' , 'coveam_mm_episode_autotitle' );

    add_settings_field( 'coveam_mm_episode_autodateformat' , __( 'Date format for auto-created episodes' , 'cove-asset-manager' ) , array( $this , 'settings_field' )  , 'cove_asset_manager_settings' , 'main_settings' , array('coveam_mm_episode_autodateformat', 'The PHP-style date format that will replace DATESTRING above. ex: "F j, Y" will result in dates that look like "March 10, 2018". See http://php.net/manual/en/function.date.php') );
    register_setting( 'cove_asset_manager_settings' , 'coveam_mm_episode_autodateformat' );


    add_settings_field( 'coveam_notify_email' , __( 'Email notifications to' , 'cove-asset-manager' ) , array( $this , 'settings_field' )  , 'cove_asset_manager_settings' , 'main_settings' , array('coveam_notify_email', 'Comma-delimited list of addresses to send notices regarding ingest, expiration etc to') );
    register_setting( 'cove_asset_manager_settings' , 'coveam_notify_email' );


	
	}

	public function main_settings() { echo '<p>' . __( 'These fields are all required.' , 'cove-asset-manager' ) . '</p>'; }

	public function settings_field( $args ) {
   
    $fieldid = $args[0];
    $fieldlabel = $args[1];
		$option = get_option($fieldid);

		$data = '';
		if( $option && strlen( $option ) > 0 && $option != '' ) {
			$data = $option;
		}

		echo '<input type="text" class="widefat" name="' . $fieldid . '" value="' . $data . '"/>
				<label for="' . $fieldid . '"><span class="description">' . __( $fieldlabel , 'cove-asset-manager' ) . '</span></label>';
	}

  public function settings_field_allowed_post_types( $args ) {

    $fieldid = $args[0];
    $fieldlabel = $args[1];
    $settingspage = $args[3];
    $option = get_option($fieldid);
    if( ! $option ) {
      $option = array();
    }
    $html = '<label for="' . $fieldid . '"><p class="description">' . __( $fieldlabel , 'cove-asset-manager' ) . '</p></label>';
    $post_types = get_post_types( '', 'objects' ); 
    foreach($post_types as $post_type) {
		// santalone.. lets just use the public post types..
		if ( !empty( $post_type->public ) ) {
			$slug = $post_type->name;
      		$label = $post_type->label;
			$checked = in_array($slug, $option) ? 'checked="checked"' : '';
			$html .= sprintf( '<div style="display: inline-block;"><input type="checkbox" id="%1$s[%2$s]" name="%1$s[]" value="%2$s" %3$s />', $fieldid, $slug, $checked );
			$html .= sprintf( '<label for="%1$s[%3$s]"> %2$s</label> &nbsp; &nbsp; </div>', $fieldid, $label, $slug );
		}

    }
    echo $html;
  }


  public function settings_field_radio( $args ) {
    $settingname = esc_attr( $args['field'] );
	$field = isset($args['field']) ? esc_attr( $args['field'] ) : '';
	$note = isset($args['note']) ? esc_attr( $args['note'] ) : '';
	$value = get_option($settingname) ? get_option($settingname) : '';

    $data = array();
    if( is_array($value) ) {
      $data = $value;
    } else {
      array_push($data, $value);
    }
    $html = ''; 

    foreach($args['options'] as $radio) {
		$checked = '';
		if ($radio == $value) { $checked = ' checked'; }
		$html .= '<input type="radio" name="' . $field . '" value="' . $radio . '" ' . $checked . ' />&nbsp;' . $radio . '  &nbsp; ';
    }
    $html .= '<label for="' . $field . '"><p class="description">' . $note . '</p></label>'; 
    echo $html;
  }

	public function validate_field( $slug ) {
		if( $slug && strlen( $slug ) > 0 && $slug != '' ) {
			$slug = urlencode( strtolower( str_replace( ' ' , '-' , $slug ) ) );
		}
		return $slug;
	}

	public function settings_page() {

		echo '<div class="wrap">
				<div class="icon32" id="cove_asset_manager_settings-icon"><br/></div>
				<h2>COVE Asset Manager Settings</h2>
				<form method="post" action="options.php" enctype="multipart/form-data">';

		settings_fields( 'cove_asset_manager_settings' );
	  do_settings_sections( 'cove_asset_manager_settings' );

	  echo '<p class="submit">
						<input name="Submit" type="submit" class="button-primary" value="' . esc_attr( __( 'Save Settings' , 'cove-asset-manager' ) ) . '" />
					</p>
				</form>';
    $this->write_out_oAuth_JavaScript();
    echo '<p>&nbsp;</p><div id = "initiate_batch_import"><button>Batch import media manager data</button><div class="status"></div><div class="failed"></div><div class="success"></div></div>';
    echo '<p>&nbsp;</p><div id = "initiate_episode_match"><button>Match Episodes for season_id</button><input type="text" name="mm_season_import" /> <input type=checkbox id="create_episodes" value="true"> Check to create Episode posts if none exist for episodes found in the Media Manager <div class="status"></div><div class="failed"></div><div class="success"></div></div>';
	  echo '</div>';
	}


  public function run_after_show_id_changed() {
    // regen the season list
    $season_resp = $this->plugin_obj->update_media_manager_season_list(TRUE);
    error_log(json_encode($season_resp));
    // 
  }




  private function bulk_import_media_manager_asset_and_episode_ids($pagenum) {
    $client = $this->plugin_obj->get_media_manager_client();
    $allowed_post_types = get_option('coveam_showonposttypes');
    $videos_to_update = array();
    $failed_videos = array();
    $args = array(
      'post_status' => 'publish', 
      'post_type' => $allowed_post_types,
      'meta_query' => array(
        array(
          'key' => '_coveam_cove_player_id',
          'value' => '',
          'compare' => '!='
        )
      ),
      'posts_per_page' => 25,
      'paged' => $pagenum
    );
    $videos = new WP_Query($args);
    if ($videos->have_posts()) {
      while ( $videos->have_posts() ) : $videos->the_post();
        $post_id = $videos->post->ID;
        $video_id = get_post_meta($post_id, '_coveam_cove_player_id', true);
        if (!$video_id) {
          array_push($failed_videos, $post_id);
        } else {
          $asset = $client->get_asset_by_tp_media_id($video_id);
          if (!empty($asset['errors'])) {
            //retry once
            $asset = $client->get_asset_by_tp_media_id($video_id);
            if (!empty($asset['errors'])) {
              array_push($failed_videos, $post_id);
              continue;
            }
          }
          if (empty($asset['data']['id'])) {
            array_push($failed_videos, $post_id);
            error_log(json_encode($asset));
            continue;
          } 
          $temp_obj = $asset['data'];
          update_post_meta($post_id, '_coveam_video_asset_guid', $temp_obj['id']);
          $this->plugin_obj->import_media_manager_asset($post_id, $temp_obj['id']);
          
          update_post_meta($post_id, '_pbs_media_manager_episode_cid', $temp_obj['attributes']['episode']['id']);
          update_post_meta($post_id, '_pbs_media_manager_episode_title', sanitize_text_field($temp_obj['attributes']['episode']['attributes']['title']));
          array_push($videos_to_update, $post_id);
        } 
      endwhile;
      wp_reset_postdata();
    }
    return array('updated' => $videos_to_update, 'failed' => $failed_videos);
  }

  public function update_episode_autocreate_status($oldvalue, $newvalue) {
    error_log('triggering episode create ' . $oldvalue . ' : ' . $newvalue );
    $response = $this->plugin_obj->do_daily_episode_generate();
    if (!empty($response['errors'])) {
      error_log(json_encode($response));
    } else {
      error_log('successfully updated'); 
    }
  }

  private function bulk_match_media_manager_episodes($season_id, $pagenum, $create_episodes = false) {
    /* this goes through  season 
     * gets all of the episodes
     * tries to match each episode to a full_episode post by date */
    $client = $this->plugin_obj->get_media_manager_client();
    $season = $client->get_season_episodes($season_id, array('page' => $pagenum, 'platform-slug' => 'partnerplayer'));
    if (!empty($season['errors'])){
      return $season;
    }
    $found_episodes = array();
    $not_found_episodes = array();
    foreach($season as $episode) {
      $airdate = $episode['attributes']['premiered_on'];
      $dateary = explode('-', $airdate); // input format is 2017-01-01 
      $args = array(
        'post_status' => array('publish','private'), 
        'post_type' => 'episodes',
        'year' => $dateary[0],
        'monthnum' => $dateary[1],
        'day' => $dateary[2]
      );
      $this_post_id = false;
      $episode_id = $episode['id'];
      $eps = new WP_Query($args);
      if ($eps->have_posts()) {
        while ( $eps->have_posts() ) : $eps->the_post();
          $this_post_id = $eps->post->ID;
          $curr_ep_mm_id = get_post_meta($this_post_id, '_pbs_media_manager_episode_id', true);
          if (!empty($curr_ep_mm_id[0])) {
            if ($curr_ep_mm_id[0] != $episode_id ) {
              // this is another ep 
              $this_post_id = false; 
            }
          }
          if ($this_post_id) {
            $this->plugin_obj->import_media_manager_episode($this_post_id, $episode_id);
            // don't try to update any more of the have_posts
            array_push($found_episodes, $this_post_id);
            break;
          }
        endwhile;
        wp_reset_postdata();
      }
      if (!$this_post_id) {
        array_push($not_found_episodes, $episode['attributes']['title']);
        if (!$create_episodes) {
          continue;
        }
        $postarr = array(
          'post_author' => 1,
          'post_title' => $episode['attributes']['title'],
          'post_type' => 'episodes',
          'post_date' => $episode['attributes']['premiered_on'] . " 19:00:00",
          'post_status' => 'publish',
          'meta_input' => array( '_pbs_media_manager_episode_id' => $episode_id )
        );
        // create the post
        $post_id = -1;
        $post_id = wp_insert_post($postarr);
        if ($post_id < 1) {
          return array('errors' => 'Episode post create failed');
        }
        $this->plugin_obj->import_media_manager_episode($post_id, $episode_id);
      }
    }
    return array('updated' => $found_episodes, 'failed' => $not_found_episodes);
  }

  public function ajax_bulk_import_media_manager_asset_and_episode_ids() {
    $pagenum = ( isset( $_POST['pagenum'] ) ) ? $_POST['pagenum'] : '';
    $returnarray = $this->bulk_import_media_manager_asset_and_episode_ids($pagenum) ;
    if ($returnarray ) {
      echo json_encode($returnarray);
    } else {
      echo json_encode($error);
    }
    die();
  }

  public function ajax_bulk_match_media_manager_episodes() {
    $pagenum = ( isset( $_POST['pagenum'] ) ) ? $_POST['pagenum'] : '';
    $season_id = ( isset( $_POST['season_id'] ) ) ? $_POST['season_id'] : '';
    $create_episodes = ( isset( $_POST['create_episodes'] ) ) ? $_POST['create_episodes'] : false;
    $returnarray = $this->bulk_match_media_manager_episodes($season_id, $pagenum, $create_episodes) ;
    if ($returnarray ) {
      echo json_encode($returnarray);
    } else {
      echo json_encode($error);
    }
    die();
  }



 private function write_out_oAuth_JavaScript() {
    $settings = get_option('cove_asset_manager_settings', true);
    $youtube_oauth= new WNET_Google_oAuth(__FILE__);
    ?>
  <script language=javascript>
  // we declare this variable at the top level scope to make it easier to pass around
  var google_access_token = "<?php echo $youtube_oauth->get_google_access_token(); ?>";

  jQuery(document).ready(function($) {
    var GOOGLECLIENTID = "<?php echo get_option('coveam_google_backend_key', true); ?>";
    var GOOGLECLIENTREDIRECT = "<?php echo get_option('coveam_google_redirect_uri', true); ?>";
    var GOOGLEUSERNAME = "<?php echo get_option('coveam_youtube_username', true); ?>";
    // we don't need the client secret for this, and should not expose it to the web.

  function requestGoogleoAuthCode() {
    var OAUTHURL = 'https://accounts.google.com/o/oauth2/auth';
    var SCOPE = 'profile email openid https://www.googleapis.com/auth/youtube';
    var popupurl = OAUTHURL + '?scope=' + SCOPE + '&client_id=' + GOOGLECLIENTID + '&redirect_uri=' + GOOGLECLIENTREDIRECT + '&response_type=code&access_type=offline&approval_prompt=force&login_hint=' + GOOGLEUSERNAME;
    var win =   window.open(popupurl, "googleauthwindow", 'width=800, height=600'); 
    var pollTimer = window.setInterval(function() { 
      try {
        if (win.document.URL.indexOf(GOOGLECLIENTREDIRECT) != -1) {
          window.clearInterval(pollTimer);
          var response_url = win.document.URL;
          var auth_code = gup(response_url, 'code');
          console.log(response_url);
          win.close();
          // We don't have an access token yet, have to go to the server for it
          var data = {
            action: 'coveam_finish_code_exchange',
            auth_code: auth_code
          };
          $.post(ajaxurl, data, function(response) {
            console.log(response);
            google_access_token = response;
            getGoogleUserInfo(google_access_token);
          });
        }
      } catch(e) {}    
    }, 500);
  }

  // helper function to parse out the query string params
  function gup(url, name) {
    name = name.replace(/[\[]/,"\\\[").replace(/[\]]/,"\\\]");
    var regexS = "[\\?#&]"+name+"=([^&#]*)";
    var regex = new RegExp( regexS );
    var results = regex.exec( url );
    if( results == null )
      return "";
    else
      return results[1];
  }

  function getGoogleUserInfo(google_access_token) {
    $.ajax({
      url: 'https://www.googleapis.com/plus/v1/people/me/openIdConnect',
      data: {
        access_token: google_access_token
      },
      success: function(resp) {
        var user = resp;
        console.log(user);
        $('#googleUserName').html('This server is now logged into Google and YouTube as ' + user.name + ' <i>(' + user.email + ')</i>');
        loggedInToGoogle = true;
        $('#google-login-block').hide();
        $('#google-logout-block').show();
      },
      dataType: "jsonp"
    });
  }

  function logoutFromGoogle() {
    $.ajax({
      url: ajaxurl,
      data: {
        action: 'coveam_logout_from_google'
      },
      success: function(resp) {
        console.log(resp);
        $('#googleUserName').text(resp);
        $('#google-login-block').show();
        $('#google-logout-block').hide();
        google_access_token = '';
      }
    });
  }

  // We also want to setup the initial click event and page status on document.ready
   $(function() {
    $('#google-login-block').click(requestGoogleoAuthCode);
    $('#google-logout-block').hide();
    $('#google-logout-block').click(logoutFromGoogle);
    // now lets show that they're logged in if they are
    if (google_access_token) {
      getGoogleUserInfo(google_access_token);
    }
   });  
  });
  </script>
  <h3>YouTube/Google Login Status</h3>
  <p>In order for YouTube integration to work, the server must be logged in and authorized with Google.</p>
  <a id="google-login-block">Login to Google and YouTube </a>
  <span id="googleUserName"><i>If given multiple account options during login, choose the "brand account" -- it will say "YouTube, Google+" below it)</i><br />You are not logged in.  YouTube integration will not work!</span>
  <span id="google-logout-block"><a>Logout from Google and deauthorize this server.</a></span>
  <iframe id="googleAuthIFrame" style="visibility:hidden;" width=1 height=1></iframe>
  <?php
  // END inlined JavaScript and HTML
  }
	
}
