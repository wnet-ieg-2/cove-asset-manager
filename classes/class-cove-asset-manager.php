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
    $client_key = !empty(get_option('coveamm_mm_api_key')) ? get_option('coveamm_mm_api_key') : false;
    $client_secret = !empty(get_option('coveamm_mm_api_secret')) ? get_option('coveamm_mm_api_secret') : false;
    $client_endpoint = !empty(get_option('coveamm_mm_api_endpoint')) ? get_option('coveamm_mm_api_endpoint') : false;
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
}
