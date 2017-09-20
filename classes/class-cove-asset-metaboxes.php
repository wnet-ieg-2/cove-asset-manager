<?php

if ( ! defined( 'ABSPATH' ) ) exit;


class COVE_Asset_Metaboxes {
	private $dir;
	private $file;
	private $assets_dir;
	private $assets_url;
	private $token;
  private $plugin_obj;

	public function __construct( $file ) {
		$this->dir = dirname( $file );
		$this->file = $file;
		$this->assets_dir = trailingslashit( $this->dir ) . 'assets';
		$this->assets_url = esc_url( trailingslashit( plugins_url( '/assets/', $file ) ) );
		$this->token = 'cove_asset';
    $this->plugin_obj = new COVE_Asset_Manager( $file );

		add_action( 'add_meta_boxes', array( $this, 'meta_box_setup' ), 20 );
		add_action( 'save_post', array( $this, 'meta_box_save' ) );	
    add_action( 'admin_enqueue_scripts', array( $this, 'setup_custom_scripts' ) );

    add_action( 'wp_ajax_coveam_get_episode_option_list', array( $this, 'ajax_get_episode_option_list'));

	}

  public function setup_custom_scripts() {
    wp_enqueue_script( 'youtube_cors', $this->assets_url . 'js/youtube_cors.js', array( 'jquery' ), 2, true );
    wp_enqueue_script( 'amazon_cors', $this->assets_url . 'js/amazon_cors.js', array( 'jquery' ), 2, true );
    wp_enqueue_script( 'pbs_media_manager_admin', $this->assets_url . 'js/media_manager_admin.js', array( 'jquery'), 1, true);
    wp_enqueue_media();
    wp_enqueue_script( 'wp_mediamanager_select', $this->assets_url . 'js/wp-mediamanager-select.js', array( 'jquery' ), 1, true );
  }

	public function meta_box_setup( $post_type ) {
    $allowed_post_types = get_option('coveam_showonposttypes');
    if ( in_array( $post_type, $allowed_post_types ) ) {
		  add_meta_box( 'cove-asset-details', __( 'COVE/YouTube Video Asset' , 'cove_asset_manager' ), array( $this, 'meta_box_content' ), $post_type, 'normal', 'high' );
    }
    // special case for the episoder
    if ( $post_type == 'episodes' ) {
      add_meta_box( 'media_manager_episode_details', 'Media Manager Episode Details', array( $this, 'episode_metabox_content' ), 'episodes', 'normal', 'high' );
    }
	}


  public function episode_metabox_content() {
    global $post_id;
		$fields = get_post_custom( $post_id );
		$field_data = $this->get_episode_fields_settings();
    $readonly_epid = false;
    $html = "<table>";
		$html .= '<input type="hidden" name="' . $this->token . '_nonce" id="' . $this->token . '_nonce" value="' . wp_create_nonce( plugin_basename( $this->dir ) ) . '" />';
	  if (empty($fields['_pbs_media_manager_episode_cid'][0])) {
      // prompt to either create a new asset or pull in asset data 
      $html .= '<tr valign="top"><th scope="row">New Media Manager Episode record creation</th><td>Either <br /><input type="radio" name="media_manager_episode_action" value="noaction"><b>Neither create nor import</b> a Media Manager episode record, or<br /><input type="radio" name="media_manager_episode_action" value="create" checked><b>create</b> a new episode record in the Media Manager or <br /><input type="radio" name="media_manager_episode_action" value="import"><b>import</b> an existing Media Manager record with the following PBS Content ID: <input name="media_manager_import_episode_id" type="text" class="regular-text" /></td></tr>';
    } else {
      $html .= '<input type="hidden" name="media_manager_episode_action" value="update" />';
      $readonly_epid = true;
    }
    foreach ( $field_data as $k => $v ) {
      $data = $v['default'];
		  if ( isset( $fields[$k] ) && isset( $fields[$k][0] ) ) {
				$data = $fields[$k][0];
			}
      if ($readonly_epid && ($k == '_pbs_media_manager_season_cid')) {
        $v['type'] = 'readonly';
      }

      // automated formatting switches 
      $maxinput = '';
      if( $v['type'] == 'textarea' ) {
        if ($v['maxlength']) { $maxinput = ' data-limit-input="' . $v['maxlength'] . '" '; }
          $html .= '<tr valign="top" class="' . $v['section'] . '"><th scope="row"><label for="' . esc_attr( $k ) . '">' . $v['name'] . '</label></th><td><textarea class="widefat" name="' . esc_attr( $k ) . '" id="' . esc_attr( $k ) . '"' . $maxinput . '>' . esc_textarea( $data ) . '</textarea>' . "\n";
          $html .= '<span></span><p class="description">' . $v['description'] . '</p>' . "\n";
		  	  $html .= '</td></tr>' . "\n";
      } else if( $v['type'] == 'readonly' ) {
        $html .= '<tr valign="top" class="' . $v['section'] . '"><th scope="row"><label for="' . esc_attr( $k ) . '">' . $v['name'] . '</label></th><td><input name="' . esc_attr( $k ) . '" type="hidden" id="' . esc_attr( $k ) . '" value="' . esc_attr( $data ) . '" />' . esc_attr( $data ) . "\n";
        $html .= '<p class="description">' . $v['description'] . '</p>' . "\n";
        $html .= '</td></tr>' . "\n";
      } else if( $v['type'] == 'select' ) {
        $html .= '<tr valign="top" class="' . $v['section'] . '"><th scope="row"><label for="' . esc_attr( $k ) . '">' . $v['name'] . '</label></th><td><select name="' . esc_attr( $k ) . '" id="' . esc_attr( $k ) . '">';
        foreach ( $v['options'] as $option ) {
          $html .= '<option id="' . esc_attr( $option['value'] ) . '" value="' . esc_attr( $option['value'] ) . '" ';
          if ($data == $option['value']) { $html .= ' selected '; }
          $html .= ' />' . $option['label'] . '</option>' . "\n";
        }
        $html .= '</select>';
        $html .= '<p class="description">' . $v['description'] . '</p>' . "\n";
        $html .= '</td></tr>' . "\n";
  		} else {
        if ($v['maxlength']) { $maxinput = ' data-limit-input="' . $v['maxlength'] . '" '; }
	  	  $html .= '<tr valign="top" class="' . $v['section'] . '"><th scope="row"><label for="' . esc_attr( $k ) . '">' . $v['name'] . '</label></th><td><input name="' . esc_attr( $k ) . '" type="text" id="' . esc_attr( $k ) . '" class="regular-text" value="' . esc_attr( $data ) . '"' . $maxinput . ' />' . "\n";
        $html .= '<span></span><p class="description">' . $v['description'] . '</p>' . "\n";
			  $html .= '</td></tr>' . "\n";
  	  } // end formatting switches
    } // end foreach
    $html .= "</table>";
		echo $html;	
  }


  private function build_media_manager_api_form_section($fields, $field_data) {
    /* unlike in the COVE Ingest API case, most fields are writable
     * and we can get the status directly from the created object 
     * including during ingest.  So the during-ingest cases 
     * are no longer used, but the 'video id' and 'video guid' fields are either null or read-only  */

    if ( empty($fields['_coveam_cove_player_id'][0]) && empty($fields['_coveam_video_asset_guid'][0]) ) {
      // once populated, these fields are read-only.  so prompt to either create a new asset or pull in asset data 
      $html .= '<tr valign="top"><th scope="row">New Media Manager record creation</th><td>Either <br /><input type="radio" name="media_manager_action" value="noaction"><b>Neither create nor import</b> a Media Manager record, or<br /><input type="radio" name="media_manager_action" value="create" checked><b>create</b> a new asset record in the Media Manager or <br /><input type="radio" name="media_manager_action" value="import"><b>import</b> an existing Media Manager record with the following PBS Content ID: <input name="media_manager_import_content_id" type="text" class="regular-text" /></td></tr>';
    } 
    $html .= '<tr valign="top"><th scope="row">Media Manager Episode</th><td>';
    $currentVal = $fields['_pbs_media_manager_episode_cid'][0];
  	if (!empty($currentVal)) {
      $html .= 'Current value: ' . $currentVal . " <br /><i>" . $fields['_pbs_media_manager_episode_title'][0] . "</i><br />";
    }  
    $args = array('post_type' => 'episodes', 'meta_key' => '_pbs_media_manager_episode_cid', 'orderby' => 'date', 'order' => 'asc', 'posts_per_page' => 1);
	  $my_query = new WP_Query($args); 
    $thisyear = date('Y');
    while ($my_query->have_posts()) : $my_query->the_post(); 
	    $oldest = get_the_date('Y');
		endwhile; 
    $html .= '<select name="_pbs_media_manager_episode_cid" id="_pbs_media_manager_episode_cid">';
    if (!empty($currentVal)) {
      $html .= '<option value = "">Only select if you want to change the episode</option>';
    }
    $html .= $this->get_episode_option_list(0, $thisyear);
		$html .= "</select>";
	  $html .= "<br />Search: <select id='epyearselect'><option value=''>Year</option>";
		
	  foreach (range( $thisyear, $oldest) as $year) {
  		$html .= "<option value='$year'>$year</option>";
	  }
	  $html .= "</select>";
	
	  $html .= "<select id='epmonthselect'><option value=''>Month</option>";
	  foreach (range(01, 12) as $month) {
   	  $html .= "<option value='$month'>$month</option>";
		}
	  $html .= "</select>";
    $html .= '<p class="description">NOTE: If you change the episode assignment, any other changes made at the same time will be discarded.</p>';
    
    $html .= '</td></tr>';


    foreach ( $field_data as $k => $v ) {
      if ( $k == '_coveam_cove_player_id' || $k == '_coveam_video_asset_guid' ) {
        $v['type'] = 'readonly';
        $v['description'] = '';
      }
      $data = $v['default'];
		  if ( isset( $fields[$k] ) && isset( $fields[$k][0] ) ) {
				$data = $fields[$k][0];
			}
      if ( $v['suppress'] == true ) {
        continue;
      }
      // automated formatting switches 
  	  if( $v['type'] == 'checkbox' ) {
        $html .= '<tr valign="top" class="' . $v['section'] . '"><th scope="row">' . $v['name'] . '</th><td><input name="' . esc_attr( $k ) . '" type="checkbox" value="1" id="' . esc_attr( $k ) . '" ' . checked( 'on' , $data , false ) . ' /> <label for="' . esc_attr( $k ) . '"><span class="description">' . $v['description'] . '</span></label>' . "\n";
  		 	$html .= '</td></tr>' . "\n";
      } else if( $v['type'] == 'radio' ) {
        $html .= '<tr valign="top" class="' . $v['section'] . '"><th scope="row">' . $v['name'] . '</th><td>';
        foreach ( $v['options'] as $option ) {
          $html .= '<input name="' . esc_attr( $k ) . '" type="radio" id="' . esc_attr( $k ) . '_' . esc_attr( $option['value'] ) . '" value="' . esc_attr( $option['value'] ) . '" ';
          if ($data == $option['value']) $html .= ' checked="checked"';
            $html .= ' /><label for="' . esc_attr( $option['value'] ) . '">' . $option['label'] . '</label>&nbsp;  ' . "\n";
        }
			  $html .= '<p class="description">' . $v['description'] . '</p></td></tr>' . "\n";
      } else if( $v['type'] == 'textarea' ) {
        $maxinput = '';
        if ($v['maxlength']) { $maxinput = ' data-limit-input="' . $v['maxlength'] . '" '; }
          $html .= '<tr valign="top" class="' . $v['section'] . '"><th scope="row"><label for="' . esc_attr( $k ) . '">' . $v['name'] . '</label></th><td><textarea class="widefat" name="' . esc_attr( $k ) . '" id="' . esc_attr( $k ) . '"' . $maxinput . '>' . esc_textarea( $data ) . '</textarea>' . "\n";
          $html .= '<span></span><p class="description">' . $v['description'] . '</p>' . "\n";
		  	  $html .= '</td></tr>' . "\n";
      } else if( $v['type'] == 'datetime' ) {
  		  $html .= '<tr valign="top" class="' . $v['section'] . '"><th scope="row"><label for="' . esc_attr( $k ) . '">' . $v['name'] . '</label></th><td><input name="' . esc_attr( $k ) . '" type="datetime" class="datetimepicker" id="' . esc_attr( $k ) . '" value="' . esc_attr( $data ) . '" />' . "\n";
  		  $html .= '<p class="description">' . $v['description'] . '</p>' . "\n";
	  	 	$html .= '</td></tr>' . "\n";
      } else if( $v['type'] == 'readonly' ) {
        $html .= '<tr valign="top" class="' . $v['section'] . '"><th scope="row"><label for="' . esc_attr( $k ) . '">' . $v['name'] . '</label></th><td><input name="' . esc_attr( $k ) . '" type="hidden" id="' . esc_attr( $k ) . '" value="' . esc_attr( $data ) . '" />' . esc_attr( $data ) . "\n";
        $html .= '<p class="description">' . $v['description'] . '</p>' . "\n";
        $html .= '</td></tr>' . "\n";
      } else if( $v['type'] == 'spanonly' ) {
        $html .= '<tr valign="top" class="' . $v['section'] . '"><th scope="row"><label for="' . esc_attr( $k ) . '">' . $v['name'] . '</label></th><td><span id="' . esc_attr( $k ) . '">' . esc_attr( $data ) . '</span>' . "\n";
        $html .= '<p class="description">' . $v['description'] . '</p>' . "\n";
        $html .= '</td></tr>' . "\n";
  		} else {
        if ($v['maxlength']) { $maxinput = ' data-limit-input="' . $v['maxlength'] . '" '; }
	  	  $html .= '<tr valign="top" class="' . $v['section'] . '"><th scope="row"><label for="' . esc_attr( $k ) . '">' . $v['name'] . '</label></th><td><input name="' . esc_attr( $k ) . '" type="text" id="' . esc_attr( $k ) . '" class="regular-text" value="' . esc_attr( $data ) . '"' . $maxinput . ' />' . "\n";
        $html .= '<span></span><p class="description">' . $v['description'] . '</p>' . "\n";
			  $html .= '</td></tr>' . "\n";
  	  } // end formatting switches
    } // end foreach

    // inline replacement for 'build s3 section' function 
    $currentvideourl = !empty($fields['_coveam_video_url'][0]) ? $fields['_coveam_video_url'][0] : '';
    $currentimageurl = !empty($fields['_coveam_video_image'][0]) ? $fields['_coveam_video_image'][0] : '';
    $currentcaptionurl = !empty($fields['_coveam_video_caption'][0]) ? $fields['_coveam_video_caption'][0] : '';

    $html .= '<tr valign="top" style="display:none;"><th></th><td><span id="plugin_assets_url">' .  $this->assets_url . '</span><span id="s3_bucket">' . get_option( 'coveam_s3_bucket' ) . '</span><span id="s3_bucket_dir">' . get_option( 'coveam_s3_bucket_dir' ) . '</span><span id="s3_proxy">' . get_option( 'coveam_s3_proxy' ) . '</span><</td></tr>' . "\n";
    $html .= '<tr valign="top" class="cove-ingest-fields"><th scope="row">';
    if ( $currentvideourl) {
      $html .= 'Archive Video URL</th><td>' . $currentvideourl . '<p class="description">Current asset status: <b>' . $fields['_coveam_covestatus'][0] . '</b><br />NOTE: Don\'t delete an in-progress deletion.  Deletion may take a few minutes to complete, and will unpublish a published asset. <br /> Save/update the post to get the most recent asset status.</p><label for="delete_current_video">Submitting a new video file requires deleting the current video file. Delete file?</label> <input type="checkbox" name="delete_current_video" value="true" /></td></tr>';
    } else {
      $html .= '<tr valign="top" class="cove-ingest-fields coverequired"><th scope="row"><label for="_coveam_video_url">Video URL to submit for ingest</label></th><td><input name="_coveam_video_url" type="text" id="_coveam_video_url" class="widefat" value=""/>' . "\n";
      $html .= '<br /><label for="video_file_to_upload">Upload a new mezzanine video file to AWS:</label> <input name="video_file_to_upload" type="file" id="video_file_to_upload" /><p class="description"><a id="s3-upload-video"><button class="button">Click to upload the <b>video</b> file you selected to AWS</button></a>  Save or update the post to submit the file to COVE for ingestion.</p></td></tr>' . "\n";
      $html .= '<tr valign="top" class="video-during-s3-upload" style="display:none;"><th scope="row">Upload status: <span id="video-s3-post-upload-status"></span></th><td><span id="video-s3-percent-transferred"></span>% done <progress id="video-s3-upload-progress" max="1" value="0"></progress></td></tr>' . "\n";
    }
    //image file
    $html .= '<tr valign="top" class="cove-ingest-fields coverequired"><th scope="row"><label for="_coveam_video_image">COVE Image URL</label></th><td>';
    if ($currentimageurl) {
      $html .= 'Current Image URL: ' . $currentimageurl . '<br />Entering a value below to replace the current image<br />';
    } 
    $html .= '<input name="_coveam_video_image" type="text" id="_coveam_video_image" class="widefat" value=""/><p class="description"><button id="_coveam_image_mediamanager" class="button">Click to open the Wordpress Media Library to select or upload an <b>image</b> </button> Select a JPG file (at least 1920x1080) but NO LARGER THAN 5MB.  Save/update the post to update the asset in COVE.</p></td></tr>' . "\n";


    //caption file
    $html .= '<tr valign="top" class="cove-ingest-fields"><th scope="row">';
    if ( $currentcaptionurl) {
      $html .= 'Archive Caption URL</th><td>' . $currentcaptionurl . '<br /><label for="delete_current_caption">Submitting a new caption file requires deleting the current caption file. Delete file?</label> <input type="checkbox" name="delete_current_caption" value="true" /><p class="description">NOTE: Deletion may take a few minutes to complete, and will unpublish a published asset. <br />Current asset status: <b>' . $fields['_coveam_covestatus'][0] . '</b> Save/update the post to get the most recent asset status.</td></tr>';
    } else {
      $html .= '<tr valign="top" class="cove-ingest-fields coverequired"><th scope="row"><label for="_coveam_video_caption">Caption URL to submit for ingest</label></th><td><input name="_coveam_video_caption" type="text" id="_coveam_video_caption" class="widefat" value=""/>' . "\n";
      $html .= '<br /><label for="caption_file_to_upload">Upload a new caption file to AWS:</label> <input name="caption_file_to_upload" type="file" id="caption_file_to_upload" /><p class="description"><a id="s3-upload-caption"><button class="button">Click to upload the <b>caption</b> file you selected to AWS</button></a>  Save or update the post to submit the file for ingestion.</p></td></tr>' . "\n";
      $html .= '<tr valign="top" class="caption-during-s3-upload" style="display:none;"><th scope="row">Upload status: <span id="caption-s3-post-upload-status"></span></th><td><span id="caption-s3-percent-transferred"></span>% done <progress id="caption-s3-upload-progress" max="1" value="0"></progress></td></tr>' . "\n";
    }



    if ( !empty($fields['_coveam_video_asset_guid'][0]) ) {
      $html .= '<tr valign="top"><th scope="row"><label for="media_manager_action">Import but don\'t update Media Manager</label></th><td><p><input type="checkbox" name="media_manager_action" value="import">Check this box and update/save the post to <b>import</b> the current data from the PBS Media Manager console and <b>ignore</b> the fields above</p><input name="media_manager_import_content_id" type="hidden" value="' . $fields['_coveam_video_asset_guid'][0]  . '" /><p class="description">Why might I do this?  If you have a custom expiration date setup, or an error in this form. Every time this form is submitted it first overwrites all of the data for the record in the PBS Media Manager with all of the field data on this page, and then imports the latest data from the PBS Media Manager.  Checking this box disables the update part of the process for this one time.  Do not check this box when trying to submit files for ingest.</p></td></tr>';
    }


    return $html;

  }


	public function meta_box_content() {
		global $post_id;
		$fields = get_post_custom( $post_id );
		$field_data = $this->get_custom_fields_settings();
    add_thickbox();
		$html = '<span id="this_post_id" style="display:none;">' . $post_id . '</span>';
		
		$html .= '<input type="hidden" name="' . $this->token . '_nonce" id="' . $this->token . '_nonce" value="' . wp_create_nonce( plugin_basename( $this->dir ) ) . '" />';
    

		if ( 0 < count( $field_data ) ) {
			$html .= '<table class="form-table">' . "\n";
			$html .= '<tbody>' . "\n";
      if (!empty($fields['_coveam_last_error'])) {
        $html .= '<tr valign="top"><th scope="row"><div class="error">The most recent attempt to update this asset in the Media Manager returned this error: ' . $fields['_coveam_last_error'][0] . ' </div></td></tr>';
      }

      // display a shortcode for this video asset 
      $html .= '<tr valign="top"><th scope="row">Shortcode for this video asset:</th><td>[covevideoasset id=' . $post_id . ']</td></tr>' . "\n";

      $html .= $this->build_media_manager_api_form_section($fields, $field_data);

      $html .= $this->build_youtube_upload_form($post_id, $fields['_coveam_youtube_id'][0], $fields['_coveam_youtubestatus'][0]);
   
      // always put the video override field at the end.
      $html .= '<tr valign="top" class="other-asset-details"><th scope="row"><label for="_coveam_video_override_url">Alternative Video URL</label></th><td><input type="url" id="_coveam_video_override_url" name="_coveam_video_override_url" class="widefat" value="' . $fields['_coveam_video_override_url'][0] . '" /><p class="description">The URL to a Vimeo or UStream video to fall back to in case of ingest problems. <br />oEmbed will automatically convert this URL to embed HTML</p></td></tr>' . "\n";

      $html .= '<tr valign="top" style="display:none;"><th></th><td>'. $s3titletext . '<span id="plugin_assets_url">' .  $this->assets_url . '</span><span id="s3_bucket">' . get_option( 'coveam_s3_bucket' ) . '</td></tr>' . "\n";
			$html .= '</tbody>' . "\n";
			$html .= '</table>' . "\n";
		}
		
		echo $html;	
	}

	public function meta_box_save( $post_id ) {
    
		global $post, $messages;
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST[ $this->token . '_nonce'], plugin_basename( $this->dir ) ) ) {  
			return $post_id;  
		}

		// Verify user permissions
		if ( ! current_user_can( 'edit_post', $post_id ) ) { 
			return $post_id;
		}
    //save the google access token to the user metadata
    if( isset( $_POST['_coveam_googleaccesstoken'])) {
      update_user_meta( get_current_user_id(), '_coveam_googleaccesstoken', $_POST['_coveam_googleaccesstoken']);
    }

    $coveid = '';		
		// Handle custom fields
		$field_data = $this->get_custom_fields_settings();
		$fields = array_keys( $field_data );
		
		foreach ( $fields as $f ) {
			
			if( isset( $_POST[$f] ) ) {
      // only operate on fields that were submitted
				${$f} = strip_tags( trim( $_POST[$f] ) );

  			// Escape the URLs.
  			if ( 'url' == $field_data[$f]['type'] ) {
  				${$f} = esc_url( ${$f} );
  			}
		
  		 	if ( ${$f} == '' ) { 
  		 		delete_post_meta( $post_id , $f , get_post_meta( $post_id , $f , true ) );
  		 	} else {
  		 		update_post_meta( $post_id , $f , ${$f} );
  		 	}
      }
      if ($f == '_coveam_cove_player_id' && ${$f} != '') {
        $coveid = ${$f};
      }
      if ($f == '_coveam_video_asset_guid' && ${$f} != '') {
        $assetid = ${$f};
      }

      if ($f == '_coveam_youtube_id' && ${$f} != '') {
        $youtubeid = ${$f};
      }
    }

    if ( !empty($_POST['media_manager_action'] )) {
      $importid = !empty($_POST['media_manager_import_content_id']) ? $_POST['media_manager_import_content_id'] : false;
      if ( $_POST['media_manager_action'] == 'import' && $importid ) {
        $assetid = $importid;
      } else if ($_POST['media_manager_action'] == 'create' && !empty($_POST['_pbs_media_manager_episode_cid'])) {
        $returnval = $this->plugin_obj->create_media_manager_asset($post_id, $_POST['_pbs_media_manager_episode_cid'], $_POST);
        if (!empty($returnval['errors'])) { 
          error_log(json_encode($returnval));
          $assetid = false;
        } else {
          $assetid = $returnval;
        }
      }
    } else {
      if ( $assetid ) {
        $returnval = $this->plugin_obj->update_media_manager_asset($post_id, $assetid, $_POST);
        if (!empty($returnval['errors'])) { 
          error_log(json_encode($returnval));
        }
        // if updating something that may've caused an error clear the flag for being messaged previsouly
        if ( !empty($_POST['delete_current_video']) || !empty($_POST['delete_current_caption']) || !empty($_POST['_coveam_video_image']) ) {
          delete_post_meta($post_id, '_coveam_notice_sent_ts');
        }
      }
    }

    // always get the latest data from the API
    if ($assetid) {
      $returnval = $this->plugin_obj->import_media_manager_asset($post_id, $assetid);
      if (!empty($returnval['errors'])) { 
        error_log(json_encode($returnval));
      }
    }

    // episode stuff
    if ( !empty($_POST['media_manager_episode_action'] )) {
      $episode_id = !empty($_POST['_pbs_media_manager_episode_cid']) ? $_POST['_pbs_media_manager_episode_cid'] : false;
      $ep_importid = !empty($_POST['media_manager_import_episode_id']) ? $_POST['media_manager_import_episode_id'] : false;
      if ( $_POST['media_manager_episode_action'] == 'import' && $ep_importid ) {
        $episode_id = $ep_importid;
      } else if ($_POST['media_manager_episode_action'] == 'create' && !empty($_POST['_pbs_media_manager_season_cid'])) {
        $returnval = $this->plugin_obj->create_media_manager_episode($post_id, $_POST['_pbs_media_manager_season_cid'], $_POST);
        if (!empty($returnval['errors'])) { 
          error_log(json_encode($returnval));
          $episode_id = false;
        } else {
          $episode_id = $returnval;
        }
      }

      if ( $episode_id && $_POST['media_manager_episode_action'] == 'update') {
        $returnval = $this->plugin_obj->update_media_manager_episode($post_id, $episode_id, $_POST);
        if (!empty($returnval['errors'])) { 
          error_log(json_encode($returnval));
        }
      }
      // always get the latest data from the API
      if ($episode_id) {
        $returnval = $this->plugin_obj->import_media_manager_episode($post_id, $episode_id);
        if (!empty($returnval['errors'])) { 
          error_log(json_encode($returnval));
        }
      }
    }

    if ($youtubeid != '') {
      $youtube_oauth= new WNET_Google_oAuth(__FILE__);
      $youtube_oauth->update_youtube_status_from_youtube($post_id);
    }
	}

	public function get_custom_fields_settings() {
		$fields = array();
    $fields['_coveam_cove_player_id'] = array(
        'name' => __( 'COVE Player ID:' , 'cove_asset_manager' ),
        'type' => 'text',
        'default' => '',
        'description' => '<a id="show-ingest-form">Enter a COVE ID above and save the post to get the latest title etc from COVE or <b>Click to enable disabled fields and ingest a COVE video</b></a> <a id="hide-ingest-form">Fill in the fields below to submit an ingest job to COVE, or <b>Click to enter a COVE Player ID for a video already in the system</b></a><span style="display:none;" id="coveam_cove_video_id_temp"></span>',
        'section' => 'cove-player-id-selector'
    );

		$fields['_coveam_video_title'] = array(
		    'name' => __( 'Video Title:' , 'cove_asset_manager' ),
         'description' => __( 'This field must be present and saved before ingesting to either YouTube or COVE' , 'cove_asset_manager' ),
		    'type' => 'text',
		    'default' => wp_kses_post(get_the_title()),
        'maxlength' => '60',
		    'section' => 'cove-asset-details coverequired youtuberequired'
		);
		$fields['_coveam_description'] = array(
		    'name' => __( 'Long Description:' , 'cove_asset_manager' ),
		    'description' => __( 'COVE Long Description and YouTube Description. REQUIRED. This field must be present and saved before ingesting to either YouTube or COVE' , 'cove_asset_manager' ),
		    'type' => 'textarea',
		    'default' => '',
        'maxlength' => '400',
		    'section' => 'cove-asset-details coverequired youtuberequired'
		);
		$fields['_coveam_shortdescription'] = array(
		    'name' => __( 'Short Description:' , 'cove_asset_manager' ),
		    'description' => __( 'Required for COVE only.' , 'cove_asset_manager' ),
		    'type' => 'text',
		    'default' => '',
        'maxlength' => '90',
		    'section' => 'cove-ingest-fields coverequired'
		);
    $fields['_coveam_airdate'] = array(
		  'name' => 'Available Datetime',
		  'type' => 'datetime',
		  'default' => '',
      'description' => 'Time before which this vid is not available.  All times Eastern',
		  'section' => 'cove-asset-details coverequired youtuberequired'
		);
    $fields['_coveam_video_status'] = array(
       'name' => __( 'Video Status:' , 'cove_asset_manager' ),
       'description' => __( 'Based on rights, date, ingest status.  ' , 'cove_asset_manager' ),
       'type' => 'spanonly',
       'default' => '',
       'section' => 'cove-asset-details'
    );
    $fields['_coveam_covestatus'] = array(
        'name' => __( 'COVE status:' , 'cove_asset_manager' ),
        'type' => 'spanonly',
        'default' => '',
        'description' => '<span id="_coveam_covestatus_long"></span><a id="check-cove-status-from-guid" style="display:none;" >Get the latest status</a>',
        'section' => 'cove-asset-details'
    );

    $fields['_coveam_video_asset_guid'] = array(
        'name' => 'PBS Content ID:',
        'type' => 'spanonly',
        'default' => '',
        'description' => 'Unique ID for this asset in COVE and the Media Manager',
        'section' => 'cove-asset-details'
    );


		$fields['_coveam_rights'] = array(
		    'name' => __( 'Rights:' , 'cove_asset_manager' ),
		    'type' => 'radio',
        'options' => array (
          'public' => array (
            'label' => 'Public',
            'value' => 'Public'
          ),
          'limited' => array (
           'label' => 'Limited',
           'value' => 'Limited'
          )
        ),
		    'default' => 'Public',
        'description' => 'Videos with limited rights will become unavailable 30 days after airdate.',
		    'section' => 'cove-asset-details coverequired youtuberequired'
		);

    $fields['_coveam_video_fullprogram'] = array(
      'name' => 'Asset type:',
	    'type' => 'radio',
      'options' => array (
        'episode' => array (
          'label' => 'full_length',
          'value' => '0'
        ),
        'promotion' => array (
          'label' => 'preview',
          'value' => '1'
          ),
        'clip' => array (
          'label' => 'clip',
          'value' => '4'
        ),
      ),
      'default' => '4',
      'section' => 'cove-asset-details coverequired youtuberequired'
    );

    
		$fields['_coveam_video_url'] = array(
		    'name' => __( 'Uploaded S3 Video Asset File:' , 'cove_asset_manager' ),
		    'type' => 'url',
		    'default' => '',
        'suppress' => true,
		    'section' => 'cove-ingest-fields coverequired'
		);
		$fields['_coveam_video_image'] = array(
		    'name' => __( 'Uploaded Mezzanine Image File:' , 'cove_asset_manager' ),
		    'type' => 'url',
		    'default' => '',
        'suppress' => true,
		    'section' => 'cove-ingest-fields coverequired'
		);

    $fields['_coveam_caption_file'] = array(
      'name' => 'Uploaded Caption File:',
		  'type' => 'url',
		  'default' => '',
      'suppress' => true,
		  'section' => 'cove-ingest-fields coverequired'
		);

 		$fields['_coveam_premiere_date'] = array(
		  'name' => 'Premiere date',
		  'type' => 'readonly',
		  'default' => '',
      'description' => 'Displayed date for the video, derived by stripping the time from the available datetime.',
		  'section' => 'cove-asset-details coverequired youtuberequired'
		);

    $fields['_coveam_notice_sent_ts'] = array(
      'name' => 'Timestamp of last admin notice',
      'type' => 'readonly',
      'default' => '',
      'description' => 'If there was an error message sent about this asset before, another one wont be sent until 24 hours after this timestamp',
      'section' => 'cove-asset-details'
    );

    $fields['_coveam_ingest_task'] = array(
        'name' => __( 'Cove Ingest Task SUPPRESSED:' , 'cove_asset_manager' ),
        'type' => 'url',
        'default' => '',
        'suppress' => true,
        'section' => 'cove-ingest-fields'
    );
    $fields['_coveam_cove_process_status'] = array(
        'name' =>__( 'Cove Ingest Status: ' , 'cove_asset_manager' ),
        'type' => 'readonly',
        'default' => '',
        'suppress' => true,
        'description' => 'preparing, readytosubmit, submitted',
        'section' => 'cove-ingest-fields'
    );

    $fields['_coveam_youtube_id'] = array(
        'name' => __( 'YouTube ID:' , 'cove_asset_manager' ),
        'type' => 'text',
        'default' => '',
        'suppress' => true,
        'description' => 'Paste in a YouTube ID or login below to ingest a new video to YouTube.',
        'section' => 'youtube-asset-details'
    );
    $fields['_coveam_youtubestatus'] = array(
        'name' =>__( 'YouTube Video Status: ' , 'cove_asset_manager' ),
        'type' => 'readonly',
        'default' => '',
        'suppress' => true,
        'section' => 'youtube-asset-details'
    );
    $fields['_coveam_video_override_url'] = array(
       'name' => __( 'Alternative Video URL:' , 'cove_asset_manager' ),
       'description' => __( 'The URL to a Vimeo or UStream video to use in case of ingest problems.  oEmbed will automatically convert to embed HTML' , 'cove_asset_manager' ),
       'type' => 'url',
       'default' => '',
       'suppress' => true,
       'section' => 'other-asset-details'
    );
    $fields['_coveam_legacy_id'] = array(
        'name' => __( 'Legacy ID:' , 'cove_asset_manager' ),
        'type' => 'text',
        'default' => '',
        'suppress' => true,
        'section' => 'youtube-asset-details'
    );


		return $fields;
	}



	public function get_episode_fields_settings() {
		$fields = array();
 
 		$fields['_pbs_media_manager_episode_cid'] = array(
		    'name' => 'PBS Media Manager Episode CID',
		    'type' => 'readonly',
        'default' => '',
        'description' => 'The content ID of this episode.' 
		);

 
 		$fields['_pbs_media_manager_season_cid'] = array(
		    'name' => 'Season',
		    'type' => 'select',
        'options' => get_option('coveam_mm_season_id_list'),
        'description' => 'The content ID of the season this episode is part of.  Defaults to the current season' 
		);
    $fields['_pbs_media_manager_episode_title'] = array(
        'name' => 'Episode title',
        'type' => 'text',
        'maxlength' => '60',
        'default' => wp_kses_post(get_the_title()) 
    );
		$fields['_pbs_media_manager_episode_desc_long'] = array(
		    'name' => 'Long Description',
		    'type' => 'textarea',
		    'default' => '',
        'maxlength' => '400'
		);
		$fields['_pbs_media_manager_episode_desc_short'] = array(
		    'name' => 'Short Description',
		    'type' => 'text',
		    'default' => '',
        'maxlength' => '90'
		);
		$fields['_pbs_media_manager_episode_airdate'] = array(
		    'name' => 'Air Date',
		    'type' => 'text',
		    'default' => '',
        'description' => 'All times Eastern, using 24hr clock. Format as YYYY-MM-DD. Will default to todays date' 
		);
 		$fields['_pbs_media_manager_episode_ordinal'] = array(
		    'name' => 'Ordinal',
		    'type' => 'text',
		    'default' => '',
        'description' => 'What order this episode appears in relative to others in the season. Will default to 1 greater than the most recent episode' 
		);

    return $fields;
  }


  /* youtube stuff */


  public function build_youtube_upload_form($postid, $youtubeid, $youtubestatus) {
    if (! $youtubeid) {
      $youtubestatus = '';
    }
    $html = '<tr><td colspan=2><div style="border:1px solid #330000;"><table><tr><th><b>YouTube</b></th><td>';
    $youtube_account_login = get_option( 'coveam_youtube_channel_email' ) ? get_option( 'coveam_youtube_channel_email' ) : get_option( 'coveam_youtube_username' ); 
    if ($youtube_account_login && get_option( 'coveam_google_backend_key' )) {
      // only should include the login form if we have the settings we need
      $wnet_youtube_obj= new WNET_Google_oAuth(__FILE__);
      $google_access_token = $wnet_youtube_obj->get_google_access_token();
      if (!$google_access_token) {
        $html .= 'The server is not currently logged into Google/YouTube.  Someone with the username/password for the YouTube channel will need to go to Settings/COVE Asset Manager, scroll to the bottom of the page, and log the server in.';
      } else {
        $html .= '<div style="display:none;"><span id="coveam_youtube_username">' . $youtube_account_login . '</span><span id="coveam_google_apikey">' . get_option( 'coveam_google_backend_key') . '</span><span id="wp_siteurl">' . get_option( 'siteurl' ) . '</span><span id="coveam_google_redirect_uri">' . get_option("coveam_google_redirect_uri") . '</span><span id="_coveam_googleaccesstoken">' . $google_access_token . '</span><script language="javascript">var _coveam_youtube_default_text = "' .  get_option("coveam_youtube_default_text") . '"; </script></div>';
      }
    }
    $html .= '</td></tr>';
    $html .= '<tr valign="top" class="youtube-asset-details"><th scope="row"><label for="_coveam_youtube_id">YouTube Video ID</label></th><td><input type="text" id="_coveam_youtube_id" name="_coveam_youtube_id" value="' . $youtubeid . '" /><p class="description">Paste in a YouTube ID or login below to upload a new video <a href="https://www.youtube.com/embed/' . $youtubeid . '?rel=0&TB_iframe=true&width=600&height=400" class="thickbox" style="display:none;" id="youtube-video-preview">Preview the current video <i>(opens new window)</i></a></p></td></tr>' . "\n";

    $youtubecheckstatuslink = '<div class="google-post-sign-in" style="display:none;"><a id="check-youtube-status">Check the status of the current video</a></div>';
    if ($youtubestatus == 'public' || $youtubestatus == '') {
      $youtubecheckstatuslink = '';
    }
    $html .= '<tr valign="top" class="youtube-asset-details"><th scope="row"><label for="_coveam_youtubestatus">YouTube Video Status</label></th><td><span id="post-upload-youtube-status">' . $youtubestatus . '</span><p class="description">Only "public" means "ready".</p>'. $youtubecheckstatuslink .'</td></tr>' . "\n";
   
    if ($youtube_account_login && get_option( 'coveam_google_backend_key' )) {
    // only should include this form if we have the settings we need
      // get the post tags for inclusion on this form
      $terms = get_the_terms( $postid, 'post_tag' );
      $theseterms = array();
      if ( $terms && ! is_wp_error( $terms ) ) { 
        foreach ( $terms as $term ) {
        $theseterms[] = $term->name;
        }
      }
      $youtube_tag_array = implode( ",", $theseterms );
      $html .= '<tr valign="top" class="google-post-sign-in" style="display:none;"><th scope="row">Title and Description</th><td>These are automatically set from the "Video Title" and "Long Description" fields above.</td></tr>' . "\n";
      $html .= '<tr valign="top" class="google-post-sign-in" style="display:none;"><th scope="row"><label for="youtube_tag_array">YouTube Tags</label></th><td><input type="text" id="youtube_tag_array" name="youtube_tag_array" value="' . $youtube_tag_array . '" /><p class="description">These are automatically set from the main post tags, but you may add or change them</p></td></tr>' . "\n";
      $html .= '<tr valign="top" class="google-post-sign-in" style="display:none;"><th scope="row"><label for="youtube_video_file_to_upload">Video file for YouTube upload/ingest</label></th><td><input name="youtube_video_file_to_upload" type="file" id="youtube_video_file_to_upload" /><p class="description">Pick an mp4 file. NOTE: Leaving this page during file upload will abort the upload.</p></td></tr>' . "\n";
      $html .= '<tr valign="top" class="google-post-sign-in" style="display:none;"><th scope="row"></th><td><a id="youtube-upload-submit"><button class="button">Submit Video to YouTube</button></a>' . "\n";
      $html .= '<div class="during-youtube-upload" style="display:none;"><p><span id="percent-transferred"></span>% done (<span id="bytes-transferred"></span>/<span id="total-bytes"></span> bytes)</p><progress id="upload-progress" max="1" value="0"></progress></div></td></tr>';
      $html .= '<tr valign="top" class="post-youtube-upload" style="display:none;"><th scope="row"><label for="youtube_video_thumbnail_to_upload">Thumbnail image for YouTube</label></th><td><img class="youtube-thumbnail-preview" align=right src="https://i.ytimg.com/vi/' . $youtubeid . '/default.jpg" /><input name="youtube_video_thumbnail_to_upload" type="file" id="youtube_video_thumbnail_to_upload" /><p class="description">Pick a JPG or PNG, max size 2MB.</p></td></tr>' . "\n";
      $html .= '<tr valign="top" class="post-youtube-upload" style="display:none;"><th scope="row"></th><td><a id="youtube-thumbnail-upload-submit"><button class="button">Upload Custom Thumbnail to YouTube</button></a>' . "\n";
      $html .= '<div id="youtube-thumbnail-response"></div></td></tr>';
      $html .= '</table></div></td></tr>';
      return $html;
    }
  }

  public function get_episode_option_list($monthnum = 0, $year = 0) {
    $html = "";
    $args = array('post_type' => 'episodes', 'meta_key' => '_pbs_media_manager_episode_cid', 'orderby' => 'date', 'order' => 'desc', 'posts_per_page' => 40);
    if ($monthnum > 0) {
      $args['monthnum'] = $monthnum;
    }
    if ($year > 0) {
      $args['year'] = $year;
    }
    $my_query = new WP_Query($args);
    while ($my_query->have_posts()) : $my_query->the_post();
      $thiscid = get_post_meta(get_the_ID(), '_pbs_media_manager_episode_cid', true);
      $html .= "<option value='". $thiscid . "'>".get_the_title(get_the_ID())."</option>";
    endwhile;
     wp_reset_query();
    return $html;
  }

  public function ajax_get_episode_option_list() {
    $html = $this->get_episode_option_list($monthnum = $_GET['month'], $year = $_GET['year']);
    if (empty($html)) {
      $html .= "<option value=''>sorry, no results</option>";
    } else {
      $html = "<option value=''>select one</option>" . $html;
    }
    wp_die($html);
  }

}
