<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class COVE_Asset_Manager {
	private $dir;
	private $file;
	private $assets_dir;
	private $assets_url;

	public function __construct( $file ) {
		$this->dir = dirname( $file );
		$this->file = $file;
		$this->assets_dir = trailingslashit( $this->dir ) . 'assets';
		$this->assets_url = esc_url( trailingslashit( plugins_url( '/assets/', $file ) ) );

		// Handle localisation
		$this->load_plugin_textdomain();
		add_action( 'init', array( $this, 'load_localisation' ), 0 );


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
}
