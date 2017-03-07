<?php

// helper functions from kkleinman
// ******************************************************************
// flip ET to UTC input and return (YY-MM-DD HH:II:SS) 
// ******************************************************************
function COVEMakeUTC($d) {
  $new_date = $d;
  $new_date = strtotime($new_date);
  $new_date = strtotime("+4 hour", $new_date);
  $new_date = date('Y-m-d H:i:s', $new_date);
  return ($new_date);
}
function COVEUTCToEastern($d) {
  $new_date = $d;
  $new_date = strtotime($new_date);
  $new_date = strtotime("-4 hours", $new_date);
  $new_date = date('Y-m-d H:i:s', $new_date);
  return ($new_date);
}

function COVETranslateTypeToNumber($str) {
  if (strtolower($str) == 'episode') {
    return '0';
  } elseif (strtolower($str) == 'promotion') {
    return '1';
  } elseif (strtolower($str) == 'interstitial') {
    return '2';
  } elseif (strtolower($str) == 'clip') {
    return '4';
  } elseif (strtolower($str) == 'other') {
    return '5';
  } elseif (strtolower($str) == 'segment') {
    return '6';
  } 
}

function COVETranslateNumberToType($num) {
  if ($num == 0) {
    return 'EPISODE'; 
  } elseif ($num == 1) {
    return 'PROMOTION';
  } elseif ($num == 2) {
    return 'INTERSTITIAL';
  } elseif ($num == 4) {
    return 'CLIP';
  } elseif ($num == 5) {
    return 'OTHER';
  } elseif ($num == 6) {
    return 'SEGMENT';
  }
}




// ******************************************************************
// make text safe COVE
// ******************************************************************
function SafeforCOVE($text) {
  $text = stripslashes($text);
  return $text;
}
// ****************************************************************** 

function COVEslugify($text) { 
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

  if (empty($text))
  {
    return 'n-a';
  }

  return $text;
}

function RemoveResizerImage($image, $suffix) {
  
  $file_extention_size = 4;
  $s = 0;
  if (substr($image, -5) == ".jpeg") {
    //print "<BR>JPEG";
    $file_extention_size = 5;
    $s = 1;
  }
  
  //print "<hr><BR>image =$image";
  //print "<BR>suffix =$suffix";
  $needle = "http://newshour.s3.amazonaws.com";
  $pos = strpos($image,$needle);
  if ($pos === false) {
    //print "<BR>string $needle NOT found in haystack";
    // if not photo resizr, leave it alone
    //$str_return = $image;
    // changed 1/10/13 to return an empty string to signify it is not resizer   
    $str_return = "";   
  } else {
    //print "<BR>string $needle found in haystack";

    $needle = "_homepage_lede";
    $pos = strpos($image,$needle);
    if($pos === false) {
      //print "<BR>string $needle NOT found in haystack";
    } else {
      //print "<BR>string $needle found in haystack";
      $ext = substr($image, -$file_extention_size);
      //print "<BR>ext = $ext";
      $str_return = substr($image, 0, (-18 - $s));
    }
    
    $needle = "_homepage_slot_1";
    $pos = strpos($image,$needle);
    if($pos === false) {
      //print "<BR>string $needle NOT found in haystack";
    } else {
      //print "<BR>string $needle found in haystack";
      $ext = substr($image, -$file_extention_size);
      //print "<BR>ext = $ext";
      $str_return = substr($image, 0, (-20 - $s));
    } 
    
    $needle = "_homepage_feature";
    $pos = strpos($image,$needle);
    if($pos === false) {
      //print "<BR>string $needle NOT found in haystack";
    } else {
      //print "<BR>string $needle found in haystack";
      $ext = substr($image, -$file_extention_size);
      //print "<BR>ext = $ext";
      $str_return = substr($image, 0, (-21 - $s));
    }

    $needle = "_homepage_blog_horizontal";
    $pos = strpos($image,$needle);
    if($pos === false) {
      //print "<BR>string $needle NOT found in haystack";
    } else {
      //print "<BR>string $needle found in haystack";
      $ext = substr($image, -$file_extention_size);
      //print "<BR>ext = $ext";
      $str_return = substr($image, 0, (-29 - $s));
    }
    
    $needle = "_homepage_square_thumbnail";
    $pos = strpos($image,$needle);
    if($pos === false) {
      //print "<BR>string $needle NOT found in haystack";
    } else {
      //print "<BR>string $needle found in haystack";
      $ext = substr($image, -$file_extention_size);
      //print "<BR>ext = $ext";
      $str_return = substr($image, 0, (-30 - $s));
    }

    $needle = "_blog_main_horizontal";
    $pos = strpos($image,$needle);
    if($pos === false) {
      //print "<BR>string $needle NOT found in haystack";
    } else {
      //print "<BR>string $needle found in haystack";
      $ext = substr($image, -$file_extention_size);
      //print "<BR>ext = $ext";
      $str_return = substr($image, 0, (-25 - $s));
    } 

    $needle = "_utility_small_horizontal";
    $pos = strpos($image,$needle);
    if($pos === false) {
      //print "<BR>string $needle NOT found in haystack";
    } else {
      //print "<BR>string $needle found in haystack";
      $ext = substr($image, -$file_extention_size);
      //print "<BR>ext = $ext";
      $str_return = substr($image, 0, (-29 - $s));
    } 
    
    $needle = "_utility_thumb";
    $pos = strpos($image,$needle);
    if($pos === false) {
      //print "<BR>string $needle NOT found in haystack";
    } else {
      //print "<BR>string $needle found in haystack";
      $ext = substr($image, -$file_extention_size);
      //print "<BR>ext = $ext";
      $str_return = substr($image, 0, (-18 - $s));
    } 

    $needle = "_video_embed";
    $pos = strpos($image,$needle);
    if($pos === false) {
      //print "<BR>string $needle NOT found in $image";
    } else {
      //print "<BR>string $needle found in $image";
      $ext = substr($image, -$file_extention_size);
      //print "<BR>ext = $ext";
      $str_return = substr($image, 0, (-16 - $s));
    } 
    
    $needle = "_video_large";
    $pos = strpos($image,$needle);
    if($pos === false) {
      //print "<BR>string $needle NOT found in haystack";
    } else {
      //print "<BR>string $needle found in haystack";
      $ext = substr($image, -$file_extention_size);
      //print "<BR>ext = $ext";
      $str_return = substr($image, 0, (-16 - $s));
    }
      
    $needle = "_video_small";
    $pos = strpos($image,$needle);
    if($pos === false) {
      //print "<BR>string $needle NOT found in haystack";
    } else {
      //print "<BR>string $needle found in haystack";
      $ext = substr($image, -$file_extention_size);
      //print "<BR>ext = $ext";
      $str_return = substr($image, 0, (-16 - $s));
    }   

    $needle = "_video_thumbwide";
    $pos = strpos($image,$needle);
    if($pos === false) {
      //print "<BR>string $needle NOT found in haystack";
    } else {
      //print "<BR>string $needle found in haystack";
      $ext = substr($image, -$file_extention_size);
      //print "<BR>ext = $ext";
      $str_return = substr($image, 0, (-20 - $s));
    }   

    $needle = "_video_covestack";
    $pos = strpos($image,$needle);
    if($pos === false) {
      //print "<BR>string $needle NOT found in haystack";
    } else {
      //print "<BR>string $needle found in haystack";
      $ext = substr($image, -$file_extention_size);
      //print "<BR>ext = $ext";
      $str_return = substr($image, 0, (-20 - $s));
    }   

    $needle = "_ipad";
    $pos = strpos($image,$needle);
    if($pos === false) {
      //print "<BR>string $needle NOT found in haystack";
    } else {
      //print "<BR>string $needle found in haystack";
      $ext = substr($image, -$file_extention_size);
      //print "<BR>ext = $ext";
      $str_return = substr($image, 0, (-9 - $s));
    }   

    $needle = "_pbs_homepage";
    $pos = strpos($image,$needle);
    if($pos === false) {
      //print "<BR>string $needle NOT found in haystack";
    } else {
      //print "<BR>string $needle found in haystack";
      $ext = substr($image, -$file_extention_size);
      //print "<BR>ext = $ext";
      $str_return = substr($image, 0, (-17 - $s));
    }   

    $needle = "_transcript_pullout";
    $pos = strpos($image,$needle);
    if($pos === false) {
      //print "<BR>string $needle NOT found in haystack";
    } else {
      //print "<BR>string $needle found in haystack";
      $ext = substr($image, -$file_extention_size);
      //print "<BR>ext = $ext";
      $str_return = substr($image, 0, (-23 - $s));
    }   

    $needle = "_big_world";
    $pos = strpos($image,$needle);
    if($pos === false) {
      //print "<BR>string $needle NOT found in haystack";
    } else {
      //print "<BR>string $needle found in haystack";
      $ext = substr($image, -$file_extention_size);
      //print "<BR>ext = $ext";
      $str_return = substr($image, 0, (-14 - $s));
    }   

    $needle = "_small_world";
    $pos = strpos($image,$needle);
    if($pos === false) {
      //print "<BR>string $needle NOT found in haystack";
    } else {
      //print "<BR>string $needle found in haystack";
      $ext = substr($image, -$file_extention_size);
      //print "<BR>ext = $ext";
      $str_return = substr($image, 0, (-16 - $s));
    } 
    
    $needle = "_extra_big";
    $pos = strpos($image,$needle);
    if($pos === false) {
      //print "<BR>string $needle NOT found in haystack";
    } else {
      //print "<BR>string $needle found in haystack";
      $ext = substr($image, -$file_extention_size);
      //print "<BR>ext = $ext";
      $str_return = substr($image, 0, (-14 - $s));
    } 
    
    $needle = "_extra_medium";
    $pos = strpos($image,$needle);
    if($pos === false) {
      //print "<BR>string $needle NOT found in haystack";
    } else {
      //print "<BR>string $needle found in haystack";
      $ext = substr($image, -$file_extention_size);
      //print "<BR>ext = $ext";
      $str_return = substr($image, 0, (-17 - $s));
    } 
    
    $needle = "_extra_small";
    $pos = strpos($image,$needle);
    if($pos === false) {
      //print "<BR>string $needle NOT found in haystack";
    } else {
      //print "<BR>string $needle found in haystack";
      $ext = substr($image, -$file_extention_size);
      //print "<BR>ext = $ext";
      $str_return = substr($image, 0, (-16 - $s));
    } 
    
    $needle = "_extra_news";
    $pos = strpos($image,$needle);
    if($pos === false) {
      //print "<BR>string $needle NOT found in haystack";
    } else {
      //print "<BR>string $needle found in haystack";
      $ext = substr($image, -$file_extention_size);
      //print "<BR>ext = $ext";
      $str_return = substr($image, 0, (-15 - $s));
    } 
    
    $needle = "_extra_coolpick";
    $pos = strpos($image,$needle);
    if($pos === false) {
      //print "<BR>string $needle NOT found in haystack";
    } else {
      //print "<BR>string $needle found in haystack";
      $ext = substr($image, -$file_extention_size);
      //print "<BR>ext = $ext";
      $str_return = substr($image, 0, (-19 - $s));
    } 
    
    $needle = "_srl";
    $pos = strpos($image,$needle);
    if($pos === false) {
      //print "<BR>string $needle NOT found in haystack";
    } else {
      //print "<BR>string $needle found in haystack";
      $ext = substr($image, -$file_extention_size);
      //print "<BR>ext = $ext";
      $str_return = substr($image, 0, (-8 - $s));
    } 
    
    $needle = "_topics";
    $pos = strpos($image,$needle);
    if($pos === false) {
      //print "<BR>string $needle NOT found in $image";
    } else {
      //print "<BR>string $needle found in $image";
      $ext = substr($image, -$file_extention_size);
      //print "<BR>ext = $ext";
      $str_return = substr($image, 0, (-11 - $s));
    }     
    
    $needle = "_slideshow";
    $pos = strpos($image,$needle);
    if($pos === false) {
      //print "<BR>string $needle NOT found in $image";
    } else {
      //print "<BR>string $needle found in $image";
      $ext = substr($image, -$file_extention_size);
      //print "<BR>ext = $ext";
      $str_return = substr($image, 0, (-14 - $s));
    } 

    $needle = "_art_beat";
    $pos = strpos($image,$needle);
    if($pos === false) {
      //print "<BR>string $needle NOT found in $image";
    } else {
      //print "<BR>string $needle found in $image";
      $ext = substr($image, -$file_extention_size);
      //print "<BR>ext = $ext";
      $str_return = substr($image, 0, (-13 - $s));
    } 
    
    $needle = "_business_desk";
    $pos = strpos($image,$needle);
    if($pos === false) {
      //print "<BR>string $needle NOT found in $image";
    } else {
      //print "<BR>string $needle found in $image";
      $ext = substr($image, -$file_extention_size);
      //print "<BR>ext = $ext";
      $str_return = substr($image, 0, (-18 - $s));
    } 

    $needle = "_mobileapp";
    $pos = strpos($image,$needle);
    if($pos === false) {
      //print "<BR>string $needle NOT found in $image";
    } else {
      //print "<BR>string $needle found in $image";
      $ext = substr($image, -$file_extention_size);
      //print "<BR>ext = $ext";
      $str_return = substr($image, 0, (-14 - $s));
    } 
    
    $needle = "_mobileapp@2";
    $pos = strpos($image,$needle);
    if($pos === false) {
      //print "<BR>string $needle NOT found in $image";
    } else {
      //print "<BR>string $needle found in $image";
      $ext = substr($image, -$file_extention_size);
      //print "<BR>ext = $ext";
      $str_return = substr($image, 0, (-16 - $s));
    } 

    $needle = "_mobileapp-l";
    $pos = strpos($image,$needle);
    if($pos === false) {
      //print "<BR>string $needle NOT found in $image";
    } else {
      //print "<BR>string $needle found in $image";
      $ext = substr($image, -$file_extention_size);
      //print "<BR>ext = $ext";
      $str_return = substr($image, 0, (-16 - $s));
    } 
    
    $needle = "_mobileapp-l@2";
    $pos = strpos($image,$needle);
    if($pos === false) {
      //print "<BR>string $needle NOT found in $image";
    } else {
      //print "<BR>string $needle found in $image";
      $ext = substr($image, -$file_extention_size);
      //print "<BR>ext = $ext";
      $str_return = substr($image, 0, (-18 - $s));
    }   

    $needle = "_mobileapp-s";
    $pos = strpos($image,$needle);
    if($pos === false) {
      //print "<BR>string $needle NOT found in $image";
    } else {
      //print "<BR>string $needle found in $image";
      $ext = substr($image, -$file_extention_size);
      //print "<BR>ext = $ext";
      $str_return = substr($image, 0, (-16 - $s));
    } 
    
    $needle = "_mobileapp-s@2";
    $pos = strpos($image,$needle);
    if($pos === false) {
      //print "<BR>string $needle NOT found in $image";
    } else {
      //print "<BR>string $needle found in $image";
      $ext = substr($image, -$file_extention_size);
      //print "<BR>ext = $ext";
      $str_return = substr($image, 0, (-18 - $s));
    }     
  
    $str_return .= $suffix . $ext;
  }

  return $str_return;
}


if (!function_exists(parse_cove_extended_response)){
  function parse_cove_extended_response($entry) {
    $thumbnail = '';
    $thumbnail_large = '';
    $filtered_data = array();
    if(isset ($entry[associated_images])){
      foreach ( $entry[associated_images] as $image ) {
        if ( $image[type][usage_type] == "iPhone-Medium" ) {
          $thumbnail = $image[url];
        } elseif ( $image[type][usage_type] == "COVEStackCard" ) {
          $thumbnail = $image[url];
        } elseif ( $image[type][width] >= 278 && $image[type][height] >= 156 ) {
          $thumbnail = $image[url];
        }
        $filtered_data[thumbnail] = $thumbnail;
        //secondary if/else to get Mezzanine if it exist then as followed
        if ( $image[type][usage_type] == 'Mezzanine' ){
          $thumbnail_large = $image[url];
          break;
        } elseif ( $image[type][usage_type] == 'iPad-Large' ){
          $thumbnail_large = $image[url];
        } elseif ( $image[type][usage_type] == 'PartnerPlayer' ){
          $thumbnail_large = $image[url];
        } elseif ( $image[type][usage_type] == 'SD16x9' ){
          $thumbnail_large = $image[url];
        }
      }
      $filtered_data[thumbnail_large] = $thumbnail_large;
    }
    //check to see available encodings
    $mp4_hd = null;
    $mp4_sd = null;
    $hls = null;
    unset($encodings);
    $encodings = array();
    if (isset ($entry[mediafiles]) ){
      foreach ( $entry[mediafiles] as $mtype ) {
        if ( $mtype[video_encoding][eeid] == 'hls-2500k-16x9') {
          $hls = $mtype[video_data_url];
        }
        elseif ( $mtype[video_encoding][eeid] == 'mp4-2500k-16x9') {
          if (isset($mtype[video_download_url])) {
            $mp4_hd = $mtype[video_download_url];
          }
        }
        elseif ( $mtype[video_encoding][eeid] == 'mp4-800k-16x9') {
          if (isset($mtype[video_download_url])) {
            $mp4_sd = $mtype[video_download_url];
          }
        }
        $profile = $mtype[video_encoding][eeid]; 
        if (isset($mtype[video_download_url])) {
          $encodings[$profile] = $mtype[video_download_url];
        } else {
          $encodings["dataurl"] = $profile;
        }
      }
    }
    $filtered_data[hls] = $hls;
    if (is_null($mp4_hd)) {
      if ($encodings['mp4-1200k-16x9']){
         $mp4_hd = $encodings['mp4-1200k-16x9'];
      }
      elseif ($encodings['mp4-2500k-4x3']){
         $mp4_hd = $encodings['mp4-2500k-4x3'];
      }
      elseif ($encodings['mp4-1200k-4x3']){
         $mp4_hd = $encodings['mp4-1200k-4x3'];
      }
    }
    $filtered_data[mp4_hd] = $mp4_hd;
    if (is_null($mp4_sd)) {
      if ($encodings['mp4-400k-16x9']){
         $mp4_sd = $encodings['mp4-400k-16x9'];
      }
      elseif ($encodings['mp4-baseline-16x9']){
         $mp4_sd = $encodings['mp4-baseline-16x9'];
      }
      elseif ($encodings['mp4-800k-4x3']){
         $mp4_sd = $encodings['mp4-800k-4x3'];
      }
      elseif ($encodings['mp4-400k-4x3']){
         $mp4_sd = $encodings['mp4-400k-4x3'];
      }
      elseif ($encodings['mirror-mpeg-4-500kbps']){
         $mp4_sd = $encodings['mirror-mpeg-4-500kbps'];
      }
    }
    $filtered_data[mp4_sd] = $mp4_sd;
    unset($encodings);


    //check if the duration is set
    if ($entry[mediafiles][0][length_mseconds]) {
      $init = $entry[mediafiles][0][length_mseconds] / 1000;
      $hours = floor($init / 3600);
      $minutes = floor(($init / 60) % 60);
      $seconds = $init % 60;
      $duration = $hours . ":" . ( $minutes < 10 ? "0" : "" ) . $minutes . ":" . ( $seconds < 10 ? "0" : "" ) . $seconds;
      $filtered_data[duration] = $duration;
    }
 
  return $filtered_data;
  }
}
 



if( !function_exists( 'http_parse_headers' ) ) {
     function http_parse_headers( $header )
     {
         $retVal = array();
         $fields = explode("\r\n", preg_replace('/\x0D\x0A[\x09\x20]+/', ' ', $header));
         foreach( $fields as $field ) {
             if( preg_match('/([^:]+): (.+)/m', $field, $match) ) {
                 $match[1] = preg_replace('/(?<=^|[\x09\x20\x2D])./e', 'strtoupper("\0")', strtolower(trim($match[1])));
                 if( isset($retVal[$match[1]]) ) {
                     $retVal[$match[1]] = array($retVal[$match[1]], $match[2]);
                 } else {
                     $retVal[$match[1]] = trim($match[2]);
                 }
             }
         }
         return $retVal;
     }
}


function coveam_submit_cove_batch_ingest_task() {
  //TODO: have to tweak this later to handle multiple videos per post.
  $postid = ( isset( $_POST['postid'] ) ) ? $_POST['postid'] : '';
  update_post_meta($postid, '_coveam_cove_process_status', 'preparing');
  update_post_meta($postid, '_coveam_covestatus', 'Processing');
  //in case there was a different video here earlier
  delete_post_meta($postid, '_coveam_cove_video_asset_guid');
  //failsafe return in case nothing works
  $returnvalue = '';
  $coveam_key_covebatch = get_option( 'coveam_cove_batch_key' );
  $coveam_secret_covebatch = get_option( 'coveam_cove_batch_secret' );
  $coveam_ingester_url = "http://merlin.pbs.org/batchingester/api/1.0/ingestiontask/";
  $coveam_cove_taxonomy = get_option( 'coveam_cove_taxonomy_name');
    // ********************************************************
    // Prep variables for COVE
    // ********************************************************
  if (function_exists('coveam_get_video') && $postid) {
    $video = coveam_get_video( $postid );
    $airdate = COVEMakeUTC($video["airdate"]);
    $video_expire_date = $airdate;
    $video_expire_date = strtotime($video_expire_date);
    $video_expire_date = strtotime("+30 day", $video_expire_date);
    $video_expire_date = date('Y-m-d H:i:s', $video_expire_date);
    $slug = COVEslugify($video["title"]) . "-" . mktime(date("Y-m-d H:i:s"));
    $contenttype = COVETranslateNumberToType($video["fullprogram"]);

    $expiration_date = "";
    if ($video["rights"] == "Public") {
      if ($contenttype == 'EPISODE') { // just a failsafe, any full program has limited rights
        $expiration_date = $video_expire_date;
      } 
    } else {
      $expiration_date = $video_expire_date;
    } 
   

    
    $temp_topics = $video["cove_topics"];
    $array_topics = Array();
    foreach ($temp_topics as $topic) {
      //WordPress appends a -2 etc to topic slugs used elsewhere, we'll just remove them here
      $topic = preg_replace("/-\d$/", "", $topic);
      $tiny = array(
        "namespace" => $coveam_cove_taxonomy,
        "topic_slug" => $topic
      );
      array_push($array_topics, $tiny);
    }

    
    // ********************************************************
    // set COVE payload
    // COVE Batch API documentation http://docs.pbs.org/confluence/display/covecms/Batch+Video+Ingestion
    // ********************************************************
    $options = array( 'consumer_key' => $coveam_key_covebatch, 'consumer_secret' => $coveam_secret_covebatch );
    $contentchannel = get_option( 'coveam_cove_channel' );
    $autopublish = "TRUE";

    $method = "POST";
    $headers = array( "Content-Type" => "application/json");
    $highresimage = $video["raw_image_url"]; 
    if (RemoveResizerImage($video["raw_image_url"],"_srl") != '') {
      $highresimage = RemoveResizerImage($video["raw_image_url"],"_srl");
    }


    if ($expiration_date) {
      $payload = array(
        "core_data" => array(
          "auto_published" => $autopublish,
          "contenttype" => $contenttype,
          "contentchannel" => $contentchannel,
          "encore_date" => $airdate,
          "expiration_date" => $expiration_date,
          "long_description" => SafeforCOVE($video["description"]),
          "premiere_date" => $airdate,
          "short_description" => SafeforCOVE($video["short_description"]),
          "slug" => $slug,
          "tags" => join(',' , $video["tags"]),
          "title" => SafeforCOVE($video["title"]),
          "topics" => $array_topics
        ),
        "files" => array(
          "hd_mezzanine_video_file" => array(
            "profile" => "hd-mezzanine-16x9",
            "location" => $video["raw_video_url"]
          ),
          "high_resolution_image_file" =>  array(
              "profile" => "Mezzanine",
              "location" => $highresimage
          )
        )
      );  
    } else {
      $payload = array(
        "core_data" => array(
          "auto_published" => $autopublish,
          "contenttype" => $contenttype,
          "contentchannel" => $contentchannel,
          "encore_date" => $airdate,
          "long_description" => SafeforCOVE($video["description"]),
          "premiere_date" => $airdate,
          "short_description" => SafeforCOVE($video["short_description"]),
          "slug" => $slug,
          "tags" => join(',' , $video["tags"]),
          "title" => SafeforCOVE($video["title"]),
          "topics" => $array_topics
        ),
        "files" => array(
          "hd_mezzanine_video_file" => array(
            "profile" => "hd-mezzanine-16x9",
            "location" => $video["raw_video_url"]
          ),
          "high_resolution_image_file" =>  array(
              "profile" => "Mezzanine",
              "location" => $highresimage
          )
        )
      );    
    }
    //PrintArray($payload, "payload");

    // ********************************************************
    // send payload
    // ******************************************************** 
    $body = json_encode($payload);
    $task_url = NULL;
   
    try
    {
      
      $oauth = new OAuth($coveam_key_covebatch, $coveam_secret_covebatch,OAUTH_SIG_METHOD_HMACSHA1);    
      
      //server clock is off 5/17/13
      $t = time();
      $t += 10000;
      $oauth->setTimestamp($t);     
      
      $oauth->fetch($coveam_ingester_url, $body, OAUTH_HTTP_METHOD_POST, $headers);
      
      $info = $oauth->getLastResponseInfo();
      //echo "=========== getLastResponseInfo()\n";
      //print_r($info);

      if ($info['http_code'] == 201) {
        //echo "Success! We created the batch ingest task\n";

        // Now extract the location from the response
        $resp_headers_str=$oauth->getLastResponseHeaders();
        $resp_headers = http_parse_headers($resp_headers_str);
        //echo "=========== getLastResponseHeaders()\n";
        $task_url = $resp_headers['Location'];
        update_post_meta($postid, '_coveam_ingest_task', $task_url);
        update_post_meta($postid, '_coveam_covestatus', 'Processing');      
        $returnvalue = json_encode($resp_headers);
      }

    }
    catch(OAuthException $e) {
      //echo "got error <pre>$e</pre>";
      $str_e = $e->getMessage();
      $returnvalue = json_encode($str_e);
    }
    if ($task_url == NULL) {
      $str_payload = htmlentities(var_export($payload, true));
      
      $returnvalue .= json_encode($str_payload);
    }
 
  }
  echo $returnvalue;
  exit; 
}

function coveam_ajax_get_batch_ingest_task_status() {
  $task_url = ( isset( $_POST['task_url'] ) ) ? $_POST['task_url'] : '';
  $postid = ( isset( $_POST['postid'] ) ) ? $_POST['postid'] : '';
  $response = coveam_get_batch_ingest_task_status($task_url, $postid);
  $resp_json = json_encode($response);
  echo $resp_json;
  die;
}

function coveam_get_batch_ingest_task_status($task_url, $postid) {
  $coveam_ingester_url = "http://merlin.pbs.org/batchingester/api/1.0/ingestiontask/";

  $coveam_key_covebatch = get_option( 'coveam_cove_batch_key' );
  $coveam_secret_covebatch = get_option( 'coveam_cove_batch_secret' );

  $options = array( 'consumer_key' => $coveam_key_covebatch, 'consumer_secret' => $coveam_secret_covebatch );
  $method = "POST";
  $headers = array( "Content-Type" => "application/json");

  $status = "";
  try
  {
    $oauth = new OAuth($coveam_key_covebatch, $coveam_secret_covebatch,OAUTH_SIG_METHOD_HMACSHA1);
    $oauth->fetch($task_url);
    $info = $oauth->getLastResponseInfo();
    $resp_json = $oauth->getLastResponse();
    $resp_obj = json_decode($resp_json, true);
  }
  catch(OAuthException $e)
  {
    //echo "got error $e";
    update_post_meta($postid, '_coveam_cove_process_status', 'useid');
    update_post_meta($postid, '_coveam_covestatus', 'Unable to get ingest task status');
    delete_post_meta($postid, '_coveam_ingest_task');
    return $e;
  }
  if ($resp_obj[ingestion_response][video_asset_published]) {
    // the ingestion worked and is complete 
    $video_asset_guid = $resp_obj[ingestion_response][video_asset_guid];
    $success_timestamp = $resp_obj[ingestion_response][video_asset_modified_date];
    update_post_meta($postid, '_coveam_video_asset_guid', $video_asset_guid);
    update_post_meta($postid, '_coveam_ingest_success_timestamp', $success_timestamp);
    update_post_meta($postid, '_coveam_cove_process_status', 'useid');
    update_post_meta($postid, '_coveam_covestatus', $resp_obj[ingestion_response][video_asset_status]);
    delete_post_meta($postid, '_coveam_ingest_task');
    $resp_obj['success_timestamp'] = $success_timestamp;
  } else {
    // ingestion in progress or failed
    if ($resp_obj[ingestion_response][video_asset_guid]) {
      //$resp_obj['asset_guid_flag'] = $resp_obj[ingestion_response][video_asset_guid];
      update_post_meta($postid, '_coveam_video_asset_guid', $resp_obj[ingestion_response][video_asset_guid]);
      update_post_meta($postid, '_coveam_cove_process_status', 'useid');
      update_post_meta($postid, '_coveam_covestatus', $resp_obj[ingestion_response][video_asset_status]);
    } else {
      update_post_meta($postid, '_coveam_covestatus', $resp_obj[status]);
    }
    // if the ingest failed for some reason lets delete the ingest task
    if ($resp_obj[status] == 'FAILED' || $resp_obj[ingestion_response][video_asset_status] == 'Ingestion Failed') {
      delete_post_meta($postid, '_coveam_ingest_task');
    }
  }

  return $resp_obj;
}

//prep these for AJAX
add_action( 'wp_ajax_coveam_ajax_get_batch_ingest_task_status', 'coveam_ajax_get_batch_ingest_task_status' );
add_action( 'wp_ajax_coveam_submit_cove_batch_ingest_task', 'coveam_submit_cove_batch_ingest_task' );

function coveam_update_status_from_guid($postid, $guid) {
  //check that it's been 10 minutes since the ingest success, just to make sure we don't screw things up
  $success_timestamp = trim(get_post_meta($postid, '_coveam_ingest_success_timestamp', true));
  $resp_json;
  if ( $success_timestamp ) {
    $video_ready_time = strtotime($success_timestamp);
    $video_ready_time = strtotime("+10 min", $video_ready_time);
    if ($video_ready_time > strtotime("now")) {
      $resp_json = json_encode("Video should be available at " . COVEUTCToEastern(date('Y-m-d H:i:s',$video_ready_time)));
    } else {     
      $resp_json = coveam_get_coveid_from_guid($guid);
      $resp_obj = json_decode($resp_json, true);
      $resp_obj['guid_found'] = $guid;
      $resp_json = json_encode($resp_obj);
      if ($resp_obj['count'] == 1) { 
        if (function_exists('coveam_update_asset_metafields_from_cove')) {
          $coveid = $resp_obj[results][0][tp_media_object_id];
          $resp_obj['found_coveid'] = $coveid;
          $resp_json = json_encode($resp_obj);
          if($coveid) {
            update_post_meta($postid, '_coveam_cove_player_id', $coveid);
            $resp_json = coveam_update_asset_metafields_from_cove($postid, $coveid);
          }
        }
      }
      return $resp_json;
    }
  } else {
    return json_encode("No success timestamp.");
  }
}

function coveam_ajax_update_status_from_guid() {
  $postid = ( isset( $_POST['postid'] ) ) ? $_POST['postid'] : '';
  $guid = ( isset( $_POST['guid'] ) ) ? $_POST['guid'] : '';
  $resp_json = coveam_update_status_from_guid($postid, $guid);
  echo $resp_json;
  die;
}

add_action( 'wp_ajax_coveam_ajax_update_status_from_guid', 'coveam_ajax_update_status_from_guid' );


function coveam_get_coveid_from_guid($guid) { 
  $api_id = get_option( 'coveam_cove_key' );
  $api_secret = get_option( 'coveam_cove_secret' );
  $requestor = new COVE_API_Request($api_id, $api_secret);
  $request_url = "http://api.pbs.org/cove/v1/videos/?filter_guid=" . $guid;
  $response = $requestor->make_request($request_url);
  $response_obj = json_decode($response, true);
  $response_obj['coveam_get_coveid_from_guid'] = 'ran';
  $response = json_encode($response_obj);
  return $response;
}

function coveam_update_asset_metafields_from_cove($postid, $coveid) {
  if ($postid && $coveid) {
    $api_id = get_option( 'coveam_cove_key' );
    $api_secret = get_option( 'coveam_cove_secret' );
    $requestor = new COVE_API_Request($api_id, $api_secret);
    $request_url = "http://api.pbs.org/cove/v1/videos/?filter_tp_media_object_id=" . $coveid. '&fields=associated_images,mediafiles';
    $response = $requestor->make_request($request_url);
    $obj = json_decode($response, true);
    $temp_obj = $obj[results][0];
    $obj['coveam_update_asset_metafields_from_cove'] = $temp_obj[title];
    if ($temp_obj[title]) {
      //easy ones
      update_post_meta($postid, '_coveam_video_title', $temp_obj[title]); 
      update_post_meta($postid, '_coveam_description', $temp_obj[long_description]);
      update_post_meta($postid, '_coveam_shortdescription', $temp_obj[short_description]);
      update_post_meta($postid, '_coveam_covestatus', $temp_obj[availability]);
      update_post_meta($postid, '_coveam_video_slug', $temp_obj[slug]);
      update_post_meta($postid, '_coveam_video_asset_guid', $temp_obj[guid]);
      //translate to our system
      update_post_meta($postid, '_coveam_airdate', COVEUTCToEastern($temp_obj[airdate]));
      update_post_meta($postid, '_coveam_video_fullprogram', COVETranslateTypeToNumber($temp_obj[type]));
      $moredata = parse_cove_extended_response($temp_obj);
      $jsonable_metadata = Array();
      foreach ($moredata as $field => $value) {
        $jsonable_metadata[$field] = $value;
      }
      /*
      if (isset($moredata['duration'])){
        $jsonable_metadata[duration] = $moredata[duration];
      }
      if (isset($moredata['mp4_hd'])){
        $jsonable_metadata[mp4_hd] = $moredata[mp4_hd];
      }
      if (isset($moredata['mp4_sd'])){
        $jsonable_metadata[mp4_sd] = $moredata[mp4_sd];
      }
      if (isset($moredata['hls'])){
        $jsonable_metadata[hls] = $moredata[hls];
      }
      */
      if (!empty($jsonable_metadata)) {
        $metafields_json = wp_slash(json_encode($jsonable_metadata));
        update_post_meta($postid, '_coveam_metadata_json', $metafields_json);
      }
      //update the video post status
      delete_post_meta($postid, '_coveam_ingest_task');
      delete_post_meta($postid, '_coveam_ingest_success_timestamp');
      update_post_meta($postid, '_coveam_cove_process_status', 'useid');
      coveam_update_video_status($postid);
    }
    $response = json_encode($obj);
    return $response;
  }
}

