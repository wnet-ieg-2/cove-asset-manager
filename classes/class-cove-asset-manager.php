<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class COVE_Asset_Manager {
	private $dir;
	private $file;
	private $assets_dir;
	private $assets_url;
  public  $use_media_manager;

	public function __construct( $file ) {
		$this->dir = dirname( $file );
		$this->file = $file;
		$this->assets_dir = trailingslashit( $this->dir ) . 'assets';
		$this->assets_url = esc_url( trailingslashit( plugins_url( '/assets/', $file ) ) );
    $this->use_media_manager = (get_option('coveam_use_mm_ingest') == 'true') ? true : false;

		// Handle localisation
		$this->load_plugin_textdomain();
		add_action( 'init', array( $this, 'load_localisation' ), 0 );

		// setup the tazonomy
    if ($this->use_media_manager) {
		  add_action( 'init', array( $this, 'add_cove_topics_taxonomy' ), 0 );
    }

    // setup the post types
    add_action( 'init', array( $this, 'register_post_types' ), 0 );  

		// Load public-facing style sheet and JavaScript.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

    // Setup the shortcode
    add_shortcode( 'covevideoasset', array($this, 'cove_player_shortcode') );

	}
	public function enqueue_scripts () {
        $scriptPath = $this->assets_url . 'js/jquery.cove-videoplayer-1.2.js';
	  wp_register_script( 'coveam_video-player', $scriptPath,  array('jquery'), 1.8, true );
    wp_enqueue_script( 'coveam_video-player' );
	}
	public function load_localisation () {
		load_plugin_textdomain( 'cove_asset_manager' , false , dirname( plugin_basename( $this->file )  . '/lang/' ) );
	}
	
	public function load_plugin_textdomain () {
	    $domain = 'cove_asset_manager';
	    
	    $locale = apply_filters( 'plugin_locale' , get_locale() , $domain );
	 
	    load_textdomain( $domain , WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
	    load_plugin_textdomain( $domain , FALSE , dirname( plugin_basename( $this->file ) ) . '/lang/' );
	}

	public function add_cove_topics_taxonomy () {
    if (!taxonomy_exists('cove_topics')) {
	    $labels = array(
		    'name'              => _x( 'COVE Topics', 'taxonomy general name' ),
		    'singular_name'     => _x( 'COVE Topic', 'taxonomy singular name' ),
		    'search_items'      => __( 'Search COVE Topics' ),
		    'all_items'         => __( 'All COVE Topics' ),
		    'parent_item'       => __( 'Parent COVE Topic' ),
		    'parent_item_colon' => __( 'Parent COVE Topic:' ),
		    'edit_item'         => __( 'Edit COVE Topic' ),
		    'update_item'       => __( 'Update COVE Topic' ),
		    'add_new_item'      => __( 'Add New COVE Topic' ),
		    'new_item_name'     => __( 'New COVE Topic Name' ),
		    'menu_name'         => __( 'COVE Topics' ),
	    );
      register_taxonomy(
        'cove_topics',
        array( 'post' ),
        array(
          'labels' => $labels,
          'hierarchical' => true,
          'public' => true,
          'show_ui' => false,
          'query_var' => false,
          'rewrite' => false
        )
      );
    }
  }

  public function register_post_types() {
    if (!post_type_exists('episodes')) {
		  register_post_type('episodes', array(
        'labels' => array(
            'name' => __('Full Episodes'),
            'singular_name' => __('Full Episode'),
            'search_items' => __('Search Episodes'),
            'add_new_item' => __('Add New Episode'),
            'edit_item' => __('Edit Episode')
        ),
        'public' => true,
        'has_archive' => true,
        'rewrite' => array(
            'slug' => 'episode'
        ),
        'query_var' => true,
        'exclude_from_search' => true,
        'menu_position' => 5,
        'menu_icon' => 'dashicons-format-video',
        'supports' => array(
            'title',
            'editor',
            'author',
            'excerpt',
            'thumbnail',
            'custom-fields',
            'comments'
        ),
        'taxonomies' => array('post_tag')
      ));
    }
    if (!post_type_exists('videos')) {
	    register_post_type('videos', array(
        'labels' => array(
            'name' => __('Videos'),
            'singular_name' => __('Video'),
            'search_items' => __('Search Videos'),
            'add_new_item' => __('Add New Video'),
            'edit_item' => __('Edit Video')
        ),
        'public' => true,
        'has_archive' => true,
        'rewrite' => array(
            'slug' => 'videos'
        ),
        'query_var' => true,
        'exclude_from_search' => true,
        'menu_position' => 5,
        'menu_icon' => 'dashicons-video-alt2',
        'supports' => array(
            'title',
            'thumbnail',
            'author',
            'custom-fields'
        ),
        'taxonomies' => array(
            'post_tag',
            'topic'
        )
      ));
    }
  }

  public function cove_player_shortcode( $atts ) {
	if (is_single() || is_post_type_archive(array('rundown','making_sense','arts','poetry_series')) || (is_feed())) {
	global $post;
    extract(shortcode_atts(array(
      'id' => $post->ID,
      'legacy_id' => null,
      'player_chrome' => 'show'), 
      $atts));
    if ($legacy_id) {
      $thisid = '';
      /*
      $args = array(
        'post_type' => 'videos',
        'post_status' => 'publish',
        'meta_key' => '_coveam_legacy_id',
        'meta_value' => $legacy_id,
        'posts_per_page' => 1
      );
      $theseposts = new WP_Query($args);
      if ($theseposts->have_posts()){
        while ( $theseposts->have_posts() ) : $theseposts->the_post();
          $thisid = $post->ID;
        endwhile;
      }
      wp_reset_postdata();
      */

      global $wpdb;
      $query = sprintf("SELECT pm.post_id FROM $wpdb->postmeta as pm where pm.meta_key = '_coveam_legacy_id' AND pm.meta_value = %d limit 1",$legacy_id);
      $thisid = $wpdb->get_var($query);
      
      $id = $thisid;
    }
    $theseargs = array('player_chrome' => $player_chrome); 
    $player = coveam_render_player( $id, $theseargs );
    return $player;
   } 
  }
  
  public function get_media_manager_client( $api_key=false, $api_secret=false, $api_endpoint=false ) {
    if (!class_exists('PBS_Media_Manager_API_Client')) {
      return array('errors' => 'Media Manager API Client not present');
    }
    $client_key = !empty(get_option('coveam_mm_api_key')) ? get_option('coveam_mm_api_key') : false;
    $client_secret = !empty(get_option('coveam_mm_api_secret')) ? get_option('coveam_mm_api_secret') : false;
    $client_endpoint = !empty(get_option('coveam_mm_api_endpoint')) ? get_option('coveam_mm_api_endpoint') : false;
    if ($api_key && $api_secret && $api_endpoint) {
      $client_key = $api_key;
      $client_secret = $api_secret;
      $client_endpoint = $api_endpoint;
    }
    if (!$client_key || !$client_secret || !$client_endpoint) {
      return array('errors' => 'Missing key, secret, or endpoint');
    }
    $client = new PBS_Media_Manager_API_Client($client_key, $client_secret, $client_endpoint);
    return $client;
  }

  public function COVEslugify($text) { 
    // replace non letter or digits by -
    $text = preg_replace('~[^\\pL\d]+~u', '-', $text);
    // trim
    $text = trim($text, '-');
    // transliterate
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    // lowercase
    $text = strtolower($text);
    // remove unwanted characters
    $text = preg_replace('~[^-\w]+~', '', $text);
    if (empty($text)) {
      return 'n-a';
    }
    return $text;
  }

  public function MediaManagerTranslateTypeToNumber($str) {
    /* this is a stupid system that is left over for translating between our old data and new */
    if (strtolower($str) == 'full_length') {
      return '0';
    } elseif (strtolower($str) == 'preview') {
      return '1';
    } elseif (strtolower($str) == 'clip') {
      return '4';
    } 
  }

  public function COVETranslateNumberToType($num) {
    /* this is a stupid system that is left over for translating between our old data and new */
    if ($num == 0) {
      return 'full_length'; 
    } elseif ($num == 1) {
      return 'preview';
    } else { 
      return 'clip';
    }
  }


  public function create_media_manager_episode( $post_id = false, $season_id = false, $attribs = array() ) {
    /* function can be called either saving an episode post or via wp_cron.
     * defaults to creating a new episode with today's date in the current season 
     * function saves the returned cid as a postmeta field for the given post */
    if (!$post_id) {
      return array('errors' => 'no post_id' );
    }
    $season_id = !$season_id ? get_option('coveam_mm_season_id') : false;
    if (!$season_id) {
      return array( 'errors' => 'no season_id' ); 
    }
    // default values for the episode
    $datestring = get_the_date('M j, Y');
    if (empty($attribs['title'])) {
       $attribs['title'] = 'Full Episode for ' . $datestring;
    }
    if (empty($attribs['slug'])) {
      $attribs['slug'] = $this->COVEslugify($attribs['title']);
    }
    if (empty($attribs['description_short'])) {
      $attribs['description_short'] = $attribs['title'];
    } 
    if (empty($attribs['description_long'])) {
      $attribs['description_long'] = $attribs['title'];
    } 

    $client = $this->get_media_manager_client();
    $result = $client->create_child($season_id, 'season', 'episode', $attribs);
    if (!empty($result['errors'])) {
      return $result;
    }
    // note that update_post_meta returns false on failure and also on an unchanged value
    // this will give me a literal true if an update, and a meta id if a new field
    return update_post_meta($post_id, 'pbs_media_manager_episode_cid', $result);
  }

  public function import_media_manager_asset( $postid = false, $asset_id = '') {
    /* function imports data based on the PBS Content ID and saves it to postmeta.  Returns the retrieved object or 'errors' array
     */
    if (!$postid) {
      return array('errors' => 'no post_id');
    }
    if (!$asset_id ) {
      return array('errors' => 'no asset_id');
    }
    $client = $this->get_media_manager_client();
    if (!empty($client->errors)) { return $client; }
    $asset = $client->get_asset($asset_id, true);
    if (!empty($asset['errors'])) { return $asset; }
    update_post_meta($postid, '_coveam_video_asset_guid', $asset_id);
    $temp_obj = $asset['data'];
    update_post_meta($postid, '_coveam_video_title', $temp_obj['attributes']['title']); 
    update_post_meta($postid, '_coveam_description', $temp_obj['attributes']['description_long']);
    update_post_meta($postid, '_coveam_shortdescription', $temp_obj['attributes']['description_short']);
    update_post_meta($postid, '_coveam_video_slug', $temp_obj['attributes']['slug']);
    update_post_meta($postid, '_coveam_cove_player_id', $temp_obj['attributes']['legacy_tp_media_id']);
    update_post_meta($postid, '_coveam_airdate', $temp_obj['attributes']['premiered_on'] . ' 19:00:00');

    //translate to our system
    update_post_meta($postid, '_coveam_video_fullprogram', $this->MediaManagerTranslateTypeToNumber($temp_obj['attributes']['object_type']));

    //update_post_meta($postid, '_coveam_covestatus', $temp_obj[availability]);


    return $asset;
  }

  private function map_metadata_fields_to_asset_array($postid) {
    $fields = get_post_custom($postid);
    $attribs = array();
    // required fields first
    $attribs['title'] = maybe_unserialize($fields['_coveam_video_title'][0]);
    $attribs['description_long'] =  maybe_unserialize($fields['_coveam_description'][0]);
    $attribs['description_short'] =  maybe_unserialize($fields['_coveam_shortdescription'][0]);
    $attribs['object_type'] = $this->COVETranslateNumberToType($fields['_coveam_video_fullprogram']);
    // much more tk
    return $attribs;
  }


  public function update_media_manager_asset( $postid = false, $asset_id = '' ) {
    error_log('updating ' . $asset_id);
    if (!$postid) {
      return array('errors' => 'no post_id');
    }
    if (!$asset_id ) {
      return array('errors' => 'no asset_id');
    }
    $client = $this->get_media_manager_client();
    if (!empty($client->errors)) { return $client; }
    $attribs = $this->map_metadata_fields_to_asset_array($postid); 
    $response = $client->update_object($asset_id, 'asset', $attribs);
    return $response;
  }


}
