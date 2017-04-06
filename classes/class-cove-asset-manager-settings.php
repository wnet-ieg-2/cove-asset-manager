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

    // bulk importer
    add_action( 'wp_ajax_bulk_import_media_manager_asset_and_episode_ids', array( $this, 'ajax_bulk_import_media_manager_asset_and_episode_ids'), 10, 2);
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
    if ($this->plugin_obj->use_media_manager) { 
      wp_enqueue_script( 'pbs_media_manager_settings_admin', $this->assets_url . 'js/mm_settings_page.js', array( 'jquery'), 1, true);
    }
    wp_enqueue_media();
  }


	public function register_settings() {
		
		// Add settings section
    add_settings_section( 'main_settings' , __( 'Modify plugin settings' , 'cove-asset-manager' ) , array( $this , 'main_settings' ) , 'cove_asset_manager_settings' );
		
		// Add settings fields
    add_settings_field( 'coveam_cove_preferred' , __( 'Display COVE video by default' , 'cove-asset-manager' ) , array( $this , 'settings_field' )  , 'cove_asset_manager_settings' , 'main_settings' , array('coveam_cove_preferred', 'Making this false will make YouTube version display when available') );
    register_setting( 'cove_asset_manager_settings' , 'coveam_cove_preferred' );

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


		add_settings_field( 'coveam_cove_channel' , __( 'COVE Channel:' , 'cove-asset-manager' ) , array( $this , 'settings_field' )  , 'cove_asset_manager_settings' , 'main_settings' , array('coveam_cove_channel', '') );
		register_setting( 'cove_asset_manager_settings' , 'coveam_cove_channel' );

		add_settings_field( 'coveam_cove_key' , __( 'COVE API Key:' , 'cove-asset-manager' ) , array( $this , 'settings_field' )  , 'cove_asset_manager_settings' , 'main_settings'  , array('coveam_cove_key', 'Must request from PBS Jira') );
		register_setting( 'cove_asset_manager_settings' , 'coveam_cove_key' );

		add_settings_field( 'coveam_cove_secret' , __( 'COVE API Secret:' , 'cove-asset-manager' ) , array( $this , 'settings_field' )  , 'cove_asset_manager_settings' , 'main_settings'  , array('coveam_cove_secret', '') );
		register_setting( 'cove_asset_manager_settings' , 'coveam_cove_secret' );

    add_settings_field( 'coveam_cove_batch_key' , __( 'COVE Batch API Key:' , 'cove-asset-manager' ) , array( $this , 'settings_field' )  , 'cove_asset_manager_settings' , 'main_settings'  , array('coveam_cove_batch_key', 'Specific to batch ingest, allows job creation in COVE. Ask Edgar Roman @ PBS') );
    register_setting( 'cove_asset_manager_settings' , 'coveam_cove_batch_key' );

   add_settings_field( 'coveam_cove_batch_secret' , __( 'COVE Batch API Secret:' , 'cove-asset-manager' ) , array( $this , 'settings_field' )  , 'cove_asset_manager_settings' , 'main_settings'  , array('coveam_cove_batch_secret', '') );
   register_setting( 'cove_asset_manager_settings' , 'coveam_cove_batch_secret' );

   add_settings_field( 'coveam_cove_taxonomy_name' , __( 'COVE Taxonomy Name:' , 'cove-asset-manager' ) , array( $this , 'settings_field' )  , 'cove_asset_manager_settings' , 'main_settings'  , array('coveam_cove_taxonomy_name', '') );
   register_setting( 'cove_asset_manager_settings' , 'coveam_cove_taxonomy_name' );


    add_settings_field( 'coveam_youtube_username' , __( 'YouTube username:' , 'cove-asset-manager' ) , array( $this , 'settings_field' )  , 'cove_asset_manager_settings' , 'main_settings' , array('coveam_youtube_username', 'ex: newshour@gmail.com') );
    register_setting( 'cove_asset_manager_settings' , 'coveam_youtube_username' );


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

    add_settings_field( 'coveam_use_mm_ingest' , __( 'Use Media Manager for ingest' , 'cove-asset-manager' ) , array( $this , 'settings_field' )  , 'cove_asset_manager_settings' , 'main_settings' , array('coveam_use_mm_ingest', 'Making this true will switch ingest fields displayed and backend to use Media Manager API instead of COVE API') );
    register_setting( 'cove_asset_manager_settings' , 'coveam_use_mm_ingest' );

    add_settings_field( 'coveam_mm_episode_autocreate' , __( 'Auto-create an episode every morning' , 'cove-asset-manager' ) , array( $this , 'settings_field' )  , 'cove_asset_manager_settings' , 'main_settings' , array('coveam_mm_episode_autocreate', 'Making this true will automatically generate a new episode post and media manager episode every morning') );
    register_setting( 'cove_asset_manager_settings' , 'coveam_mm_episode_autocreate' );


	
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
      $slug = $post_type->name;
      $label = $post_type->label;

      $checked = in_array($slug, $option) ? 'checked="checked"' : '';
      $html .= sprintf( '<div style="display: inline-block;"><input type="checkbox" id="%1$s[%2$s]" name="%1$s[]" value="%2$s" %3$s />', $fieldid, $slug, $checked );
      $html .= sprintf( '<label for="%1$s[%3$s]"> %2$s</label> &nbsp; &nbsp; </div>', $fieldid, $label, $slug );
    }
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
    if ($this->plugin_obj->use_media_manager) {
      echo '<p>&nbsp;</p><div id = "initiate_batch_import"><button>Batch import media manager data</button><div class="status"></div><div class="failed"></div><div class="success"></div></div>';
    } 
	  echo '</div>';
	}

  private function bulk_import_media_manager_asset_and_episode_ids($pagenum) {
    $client = $this->plugin_obj->get_media_manager_client();

    $videos_to_update = array();
    $failed_videos = array();
    $args = array(
      'post_status' => 'publish', 
      'post_type' => 'videos',
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
          update_post_meta($post_id, '_pbs_media_manager_episode_cid', $temp_obj['attributes']['episode']['id']);
          update_post_meta($post_id, '_pbs_media_manager_episode_title', sanitize_text_field($temp_obj['attributes']['episode']['attributes']['title']));
          array_push($videos_to_update, $post_id);
        } 
      endwhile;
    }
    return array('updated' => $videos_to_update, 'failed' => $failed_videos);
  }

  private function bulk_match_media_manager_episodes($season_id) {
    /* this goes through  season 
     * gets all of the episodes
     * tries to match each episode to a full_episode post by date */
    
    $client = $this->plugin_obj->get_media_manager_client();
    $season = $client->get_season_episodes($season_id);
    foreach($season as $episode) {


    }
    $eps_to_update = array();
    $failed_eps = array();
    $args = array(
      'post_status' => 'publish', 
      'post_type' => 'full_episodes',
      'meta_query' => array(
        array(
          'key' => '_pbs_media_manager_episode_cid',
          'value' => '',
          'compare' => '='
        )
      ),
      'posts_per_page' => 1,
      'paged' => $pagenum
    );
    return array('updated' => $videos_to_update, 'failed' => $failed_videos);
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
        $('#googleUserName').text('This server is now logged into Google and YouTube as ' + user.name);
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
  <span id="googleUserName">You are not logged in.  YouTube integration will not work!</span>
  <span id="google-logout-block"><a>Logout from Google and deauthorize this server.</a></span>
  <iframe id="googleAuthIFrame" style="visibility:hidden;" width=1 height=1></iframe>
  <?php
  // END inlined JavaScript and HTML
  }
	
}
