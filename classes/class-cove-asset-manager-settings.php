<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class COVE_Asset_Manager_Settings {
	private $dir;
	private $file;
  private $plugin_url;
	private $assets_dir;
	private $assets_url;

	public function __construct( $file ) {
		$this->dir = dirname( $file );
		$this->file = $file;
    $this->plugin_url = esc_url( trailingslashit( plugins_url( '/', $file ) ) );
		$this->assets_dir = trailingslashit( $this->dir ) . 'assets';
		$this->assets_url = esc_url( trailingslashit( plugins_url( '/assets/', $file ) ) );

		// Register plugin settings
		add_action( 'admin_init' , array( $this , 'register_settings' ) );

		// Add settings page to menu
		add_action( 'admin_menu' , array( $this , 'add_menu_item' ) );

		// Add settings link to plugins page
		add_filter( 'plugin_action_links_' . plugin_basename( $this->file ) , array( $this , 'add_settings_link' ) );
	}
	
	public function add_menu_item() {
		add_options_page( 'COVE Asset Manager Settings' , 'COVE Asset Manager Settings' , 'manage_options' , 'cove_asset_manager_settings' ,  array( $this , 'settings_page' ) );
	}

	public function add_settings_link( $links ) {
		$settings_link = '<a href="options-general.php?page=cove_asset_manager_settings">Settings</a>';
  		array_push( $links, $settings_link );
  		return $links;
	}

	public function register_settings() {
		
		// Add settings section
		add_settings_section( 'main_settings' , __( 'Modify plugin settings' , 'cove-asset-manager' ) , array( $this , 'main_settings' ) , 'cove_asset_manager_settings' );
		
		// Add settings fields
    add_settings_field( 'coveam_cove_preferred' , __( 'Display COVE video by default' , 'cove-asset-manager' ) , array( $this , 'settings_field' )  , 'cove_asset_manager_settings' , 'main_settings' , array('coveam_cove_preferred', 'Making this false will make YouTube version display when available') );
    register_setting( 'cove_asset_manager_settings' , 'coveam_cove_preferred' );

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



   add_settings_field( 'coveam_showonposttypes' , __( 'Show the COVE metaboxes on these post types:' , 'cove-asset-manager' ) , array( $this , 'settings_field_allowed_post_types' )  , 'cove_asset_manager_settings' , 'main_settings'  , array('coveam_showonposttypes', '') );
   register_setting( 'cove_asset_manager_settings' , 'coveam_showonposttypes' );


	
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
        
			  echo '</div>';
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
