<?php
if ( ! defined( 'ABSPATH' ) ) exit;
class WNET_Google_oAuth { 
  private $dir;
  private $file;
  private $token;

  public function __construct( $file ) {
    $this->dir = dirname( $file );
    $this->file = $file;
    $this->token = 'wnet_google_oauth';

    // setup the wp ajax action for oAuth code exchange
    add_action( 'wp_ajax_coveam_finish_code_exchange', array($this, 'finish_code_exchange') );
    // setup the wp ajax action to logout from oAuth
    add_action( 'wp_ajax_coveam_logout_from_google', array($this, 'logout_from_google') );

    add_action( 'wp_ajax_coveam_update_youtube_postmeta', array($this, 'update_youtube_postmeta') );
 
  }

  // wrapper for wp_ajax to point to reusable function
  public function finish_code_exchange() {
    $auth_code = ( isset( $_POST['auth_code'] ) ) ? $_POST['auth_code'] : '';
    echo $this->set_google_oauth2_token($auth_code, 'auth_code');
    wp_die(); 
  }

  private function set_google_oauth2_token($grantCode, $grantType) {
   /* based on code written by Jennifer L Kang that I found here
   * http://www.jensbits.com/2012/01/09/google-api-offline-access-using-oauth-2-0-refresh-token/
   * and modified to integrate with WordPress and to calculate and store the expiration date.
    */  
    $success = true;	
    $oauth2token_url = "https://accounts.google.com/o/oauth2/token";
    $clienttoken_post = array(
      "client_id" => get_option('coveam_google_backend_key', true),
      "client_secret" => get_option('coveam_google_backend_secret', true)
    );

    if ($grantType === "auth_code"){
      $clienttoken_post["code"] = $grantCode;	
      $clienttoken_post["redirect_uri"] = get_option('coveam_google_redirect_uri', true);
      $clienttoken_post["grant_type"] = "authorization_code";
    }
    if ($grantType === "refresh_token"){
      $clienttoken_post["refresh_token"] = get_option('coveam_google_refresh_token', true);
      $clienttoken_post["grant_type"] = "refresh_token";
    }
    $postargs = array(
      'body' => $clienttoken_post
     );
    $response = wp_remote_post($oauth2token_url, $postargs );
    $authObj = json_decode(wp_remote_retrieve_body( $response ), true);
    if (isset($authObj[refresh_token])){
      $refreshToken = $authObj[refresh_token];
      $success = update_option('coveam_google_refresh_token', $refreshToken, false); 
      // the final 'false' is so we don't autoload this value into memory on every page load
    }
    if ($success) {
      $success = update_option('coveam_google_access_token_expires',  strtotime("+" . $authObj[expires_in] . " seconds"));
    }
    if ($success) {
      $success = update_option('coveam_google_access_token', $authObj[access_token], false);
      if ($success) {
        $success = $authObj[access_token];
      }
    }
    // if there were any errors $success will be false, otherwise it'll be the access token
    if (!$success) { $success=false; }
    return $success;
  }

  public function get_google_access_token() {
    $expiration_time = get_option('coveam_google_access_token_expires', true);
    if (! $expiration_time) {
      return false;
    }
    // Give the access token a 5 minute buffer (300 seconds)
    $expiration_time = $expiration_time - 300;
    if (time() < $expiration_time) {
      return get_option('coveam_google_access_token', true);
    }
    // at this point we have an expiration time but it is in the past or will be very soon
    return $this->set_google_oauth2_token(null, 'refresh_token');
  }

  public function revoke_google_tokens() {
    /* This function finds either the access token or refresh token
     * revokes them with google (revoking the access token does the refresh too)
     * then deletes the data from the options table
    */
    $return = '';
    $token = get_option('coveam_google_access_token', true);
    $expiration_time = get_option('coveam_google_access_token_expires', true);
    if (!$token || (time() > $expiration_time)){
      $token = get_option('coveam_google_refresh_token', true);
    }
    if ($token) {
      $return = wp_remote_retrieve_response_code(wp_remote_get("https://accounts.google.com/o/oauth2/revoke?token=" . $token));
    } else {
      $return = "no tokens found";
    }
    if ($return == 200) {
      delete_option('coveam_google_access_token');
      delete_option('coveam_google_refresh_token');
      delete_option('coveam_google_access_token_expires');
      return true;
    } else {
      return $return; 
    }
  }

  // wrapper for wp_ajax to point to reusable function
  public function logout_from_google() {
    $response = $this->revoke_google_tokens();
    if ($response === true) {
      $response = "success";
    }
    echo $response;
    wp_die(); 
  }


  public function submit_youtube_expire_request($videoid){
    $access_token = $this->get_google_access_token();
    if (! $access_token) {
      error_log("no access token for $videoid");
      return false;
    }
    $bodyargs = array(
        "id" => $videoid,
        "kind" => "youtube#video",
        "status" => array(
          "privacyStatus" => "private"
        )
      );
    $body = json_encode($bodyargs);
    $url = "https://www.googleapis.com/youtube/v3/videos?part=status&fields=status";
    $args = array(
      "method" => "PUT",
      "headers" => array(
        "Authorization" => "Bearer " . $access_token,
        "Content-Type" => "application/json"
      ),
      "body" => $body
    );
    $request = wp_remote_request($url, $args);
    if (wp_remote_retrieve_response_code($request) != 200){
      return "privacy set failed : " . wp_remote_retrieve_body($request);
    }
    return true;
  }


  public function get_youtube_object_from_google($videoid){
    $accessToken = $this->get_google_access_token();
    if (! $accessToken) {
      return false;
    }
    $url = 'https://www.googleapis.com/youtube/v3/videos?id=' . $videoid . '&part=status,snippet,contentDetails';
    $args = array(
      "headers" => array(
        "Authorization" => "Bearer " . $accessToken
      )
    );
    $request = wp_remote_request($url, $args);
    $responseObj = json_decode(wp_remote_retrieve_body($request),true);
    $obj = $responseObj[items][0];	
    return $obj;
  }

  
  public function update_youtube_status_from_youtube($postid) {
    $videoid = get_post_meta($postid, '_coveam_youtube_id', true);
    if (! $videoid) {
      return false;
    }
    $videoobj = $this->get_youtube_object_from_google($videoid);
    $uploadstatus = $videoobj['status']['uploadStatus'];
    $privacystatus = $videoobj['status']['privacyStatus'];
    $livebroadcast = $videoobj['snippet']['liveBroadcastContent'];
    $statusmessage = get_post_meta($postid, '_coveam_youtubestatus', true);
    if ($livebroadcast == 'live' || $livebroadcast == 'upcoming') {
      $statusmessage = $livebroadcast;
    } else if ($uploadstatus) {
      if ($uploadstatus == 'processed') {
        $statusmessage = $privacystatus;
      } else {
        $statusmessage = $uploadstatus;
      }
    }
    update_post_meta($postid, '_coveam_youtubestatus', $statusmessage);

    // get the mezz image if not set
    if (! get_post_meta($postid, '_coveam_video_image', true)) {
      $youtubeurl = 'https://i3.ytimg.com/vi/' . $videoid . '/maxresdefault.jpg';
      update_post_meta($postid, '_coveam_video_image', $youtubeurl);
      // sideload it to the media library and set as thumbnail if no thumb set
      if (! get_post_thumbnail_id( $post_id )) {
        $new_thumb_response = $this->sideload_image_into_post_as_thumbnail($post_id, $youtubeurl);
        if ($new_thumb_response !== TRUE ) {
          error_log('failed to sideload youtube image ' . json_encode($new_thumb_response));
        }
      } 
    }


    // get the duration if not set
    if (empty(get_post_meta($postid, '_coveam_duration', true)) && !empty($videoobj['contentDetails']['duration'])) {
      $tstr = $videoobj['contentDetails']['duration'];
      $duration = new DateInterval($tstr);
      $calculated_seconds = ($duration->format('%h')*3600) + ($duration->format('%i')*60) + $duration->format('%s');
      update_post_meta($postid, '_coveam_duration', $calculated_seconds*1000);
    }

    coveam_update_video_status($postid);
    return $statusmessage;
  }

  public function update_youtube_postmeta() {
    $postid = ( isset( $_POST['postid'] ) ) ? $_POST['postid'] : '';
    $videoid = ( isset( $_POST['videoid'] ) ) ? $_POST['videoid'] : '';
    $statusmessage = ( isset( $_POST['statusmessage'] ) ) ? $_POST['statusmessage'] : '';
    update_post_meta($postid, '_coveam_youtube_id', $videoid);
    update_post_meta($postid, '_coveam_youtubestatus', $statusmessage);
    $finalstatus= coveam_update_video_status($postid);
    echo json_encode($finalstatus);
    //called by wp-admin-ajax so lets die
    exit;
  }

  public function sideload_image_into_post_as_thumbnail($post_id = false, $url) { 
    // adapted from https://codex.wordpress.org/Function_Reference/media_handle_sideload
    if (!function_exists('download_url')) {
      return FALSE;
    }
    $tmp = download_url( $url );

    preg_match('/[^\?]+\.(jpg|jpe|jpeg|gif|png)/i', $url, $matches);
    $file_array['name'] = basename($matches[0]);
    $file_array['tmp_name'] = $tmp;

    // If error storing temporarily, unlink
    if ( is_wp_error( $tmp ) ) {
      @unlink($file_array['tmp_name']);
      $file_array['tmp_name'] = '';
    }

    $id = media_handle_sideload( $file_array, $post_id );

    // If error storing permanently, unlink
    if ( is_wp_error($id) ) {
      @unlink($file_array['tmp_name']);
      return $id;
    }
    $response = set_post_thumbnail($post_id, $id);
    // will return either post_id on success or an error message/false on failure
    if ($response == $post_id) {
      return TRUE;
    }
    return $response;
  }


}

/* END OF FILE */
?>
