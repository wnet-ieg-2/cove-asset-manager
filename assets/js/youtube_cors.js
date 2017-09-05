// helper functions and variables to make youtube/google client-side upload and polling work
jQuery(document).ready(function($) {
  // variables specific to this server
  // these Google/YouTube variables pulled from the passing page
  var GOOGLECLIENTID = $('#coveam_google_apikey').text();
  var GOOGLECLIENTREDIRECT = $('#coveam_google_redirect_uri').text();
  var GOOGLEUSERNAME = $('#coveam_youtube_username').text();


  // constants 
  var GoogleAccessToken = $('#_coveam_googleaccesstoken').text();
  var googleTokenType;
  var GoogleTokenExpiresIn;
  var loggedInToGoogle    =   false;
  var youtube_tag_array;
    
  function validateGoogleToken(GoogleAccessToken) {
    $.ajax({
      url: 'https://www.googleapis.com/oauth2/v1/tokeninfo?access_token=' + GoogleAccessToken,
      data: null,
      dataType: "jsonp",
      success: function(responseText){ 
        getGoogleUserInfo();
      },
      error: function(responseText){
        console.log(responseText);
        $('#google-login-block').show();
        $('#google-logout-block').hide();
        $('.google-pre-sign-in').show();
        $('.google-post-sign-in').hide();
        loggedInToGoogle = false;
        $('#googleUserName').text('');
        $('#_coveam_googleaccesstoken').val('');
      } 
    });
  }

  function getGoogleUserInfo() {
    $.ajax({
      url: 'https://www.googleapis.com/plus/v1/people/me/openIdConnect?access_token=' + GoogleAccessToken,
      data: null,
      success: function(resp) {
        var user = resp;
        console.log(user);
        if (user.email == GOOGLEUSERNAME) {
          $('#googleUserName').text('You are logged in to YouTube as ' + user.name);
          loggedInToGoogle = true;
          $('#_coveam_googleaccesstoken').val(GoogleAccessToken);
          $('#google-login-block').hide();
          $('#google-logout-block').show();
          $('.google-pre-sign-in').hide();
          $('.google-post-sign-in').show();
          makeYouTubeFieldInputsWritable();
        } else {
          $('#google-login-block').hide();
          $('#google-logout-block').show();
          $('#googleUserName').text('You have logged in as ' + user.email + ' but you must logout and login as ' + GOOGLEUSERNAME + ' to upload to our YouTube channel.');
        }
      },
      dataType: "jsonp"
    });
  }

	function getYouTubeChannelInfo() {
    $.ajax({
  		url: 'https://www.googleapis.com/youtube/v3/channels',
      method: 'GET',
      headers: {
      	Authorization: 'Bearer ' + GoogleAccessToken
      },
      data: {
      	part: 'snippet',
       	mine: true
      }
  	}).done(function(response) {
     	$('#channel-name').text(response.items[0].snippet.title);
     	$('#channel-thumbnail').attr('src', response.items[0].snippet.thumbnails.default.url);
  	});
	}

  function initiateYoutubeUpload(event) {
    event.preventDefault();
    var file = $('#youtube_video_file_to_upload').get(0).files[0];
    var thistitle = $('#_coveam_video_title').val();
    var thisdescription = $('#_coveam_description').val();
    //if ($('#_coveam_youtube_default_text')) {
    if (typeof('_coveam_youtube_default_text' !== 'undefined')) {
      thisdescription = thisdescription + _coveam_youtube_default_text;
    }
    var thesetags = $('#youtube_tag_array').val();
    if (file && thistitle && thisdescription && thesetags) {
      $('#youtube-upload-submit').hide();
      var metadata = {
        snippet: {
          title: thistitle,
          description: thisdescription,
          tags: thesetags,
          categoryId: 22
        },
        status: {
          privacyStatus: "public"
        }
      };
      var formdata = new FormData();
      var params = JSON.stringify(metadata);
      var jsonBlob = new Blob([ params ], { "type" : "application\/json" });
      formdata.append("snippet", jsonBlob, "file.json");
      formdata.append("file", file);
      var ajax = $.ajax({
        url: 'https://www.googleapis.com/upload/youtube/v3/videos?part=snippet,status',
        method: 'POST',
        headers: {
          Authorization: 'Bearer ' + GoogleAccessToken
        },
        xhr: function() {
          var xhr = $.ajaxSettings.xhr();

          if (xhr.upload) {
            xhr.upload.addEventListener( 'progress', uploadProgressHandler, false);
          }
          return xhr;
        },
        processData: false,
        contentType: false,
        data: formdata
      });
      ajax.done(function(response) {
        var videoId = response.id;
        $('#youtube-uploaded-video-id').text(videoId);
        $('#_coveam_youtube_id').val(videoId);
        $('.youtube-thumbnail-preview').hide();
        $('#youtube-video-preview').hide();
        $('.post-youtube-upload').show();
        checkVideoStatus(videoId, 30 * 1000);
        updateYouTubePostMeta(videoId,"uploaded");
        $('#upload-progress').after('<p><b>YouTube video ID updated: ' + videoId + '</b></p>');
      });

      ajax.fail(function(response) {
        $('#youtube-upload-submit button').text('Retry Upload');
        $('#youtube-upload-submit').show();
      });
    } else {
      window.alert('You must select a file, and enter title and description and tags for the video before you submit to YouTube.');
    }
  }

  function uploadProgressHandler(e) {
    if(e.lengthComputable) {
      var bytesTransferred = e.loaded;
      var totalBytes = e.total;
      var percentage = Math.round(100 * bytesTransferred / totalBytes);
      $('#upload-progress').attr({
        value: bytesTransferred,
        max: totalBytes
      });
      $('#percent-transferred').text(percentage);
      $('#bytes-transferred').text(bytesTransferred);
      $('#total-bytes').text(totalBytes);
      $('.during-youtube-upload').show();
      $('.youtube-asset-details').show();
    }
  }

  function initiateYoutubeThumbnailUpload(event) {
    event.preventDefault();
    var videoId = $('#_coveam_youtube_id').val();
    var file = $('#youtube_video_thumbnail_to_upload').get(0).files[0];
    if (file && videoId) {
      $('#youtube-thumbnail-upload-submit').hide();
      $('#youtube-thumbnail-response').html('<p>Working...</p>');
      var formdata = new FormData();
      formdata.append("file", file);
      var ajax = $.ajax({
        url: 'https://www.googleapis.com/upload/youtube/v3/thumbnails/set?videoId=' + videoId,
        method: 'POST',
        headers: {
          Authorization: 'Bearer ' + GoogleAccessToken
        },
        xhr: function() {
          var xhr = $.ajaxSettings.xhr();
          return xhr;
        },
        processData: false,
        contentType: false,
        data: formdata
      });
      ajax.done(function(response) {
        console.log(response);
        $('.youtube-thumbnail-preview').attr('src', 'https://i.ytimg.com/vi/' + videoId + '/default.jpg?timestamp=' + new Date().getTime());
        $('.youtube-thumbnail-preview').show();
        $('#youtube-thumbnail-response').html('<p>Upload successful!<br><i>Note: reuploading a different image may take a while to take effect due to caching.</i></p>');
        $('#youtube-thumbnail-upload-submit button').text('Re-upload thumbnail image');
        $('#youtube-thumbnail-upload-submit').show();

      });

      ajax.fail(function(response) {
        $('#youtube-thumbnail-response').html('<p><b>Error!  Raw error message: '+ response.responseText +'</b></p>');
        $('#youtube-thumbnail-upload-submit button').text('Retry Upload');
        $('#youtube-thumbnail-upload-submit').show();
      });
    } else {
      window.alert('You must select a thumbnail image and the video itself must be uploaded and have recieved a YouTube videoid before you submit to YouTube.');
    }
  }



  function checkVideoStatus(videoId, waitForNextPoll) {
    $('#check-youtube-status').hide();
    $.ajax({
      url: 'https://www.googleapis.com/youtube/v3/videos',
      method: 'GET',
      headers: {
        Authorization: 'Bearer ' + GoogleAccessToken
      },
      data: {
        part: 'status,snippet',
        id: videoId
      }
    }).done(function(response) {
      console.log(response);
      var uploadStatus = response.items[0].status.uploadStatus;

      $('#post-upload-youtube-status').html('status: ' + uploadStatus);

      if (uploadStatus == 'uploaded') {
        setTimeout(function() {
          checkVideoStatus(videoId, waitForNextPoll * 2);
        }, waitForNextPoll);
      } else {
        var finalStatus = uploadStatus;
        if (finalStatus == 'processed') {
          finalStatus = response.items[0].status.privacyStatus;
          $('#youtube-video-preview').attr('src', 'https://www.youtube.com/embed/' + videoId + '?rel=0&TB_iframe=true&width=600&height=400');
          $('#youtube-video-preview').show();
        } else if (finalStatus == 'rejected') {
          finalStatus = finalStatus + '-' + response.items[0].status.rejectionReason;
        } else if (finalStatus == 'failed') {
          finalStatus = finalStatus + '-' + response.items[0].status.failureReason;
        }
        updateYouTubePostMeta(videoId,finalStatus);
        $('#post-upload-youtube-status').append(' <b>Final status: ' + finalStatus + '</b>');
        $('.youtube-thumbnail-preview').attr('src', 'https://i.ytimg.com/vi/' + videoId + '/default.jpg?timestamp=' + new Date().getTime());
        $('.youtube-thumbnail-preview').show();
      }
    });
  }


  function updateYouTubePostMeta( $videoid, $status ) {
    var $this_post_id = $('#this_post_id').text();
    $.ajax({
      type: "post",
      url: ajaxurl,
      data:{
        'action': 'coveam_update_youtube_postmeta',
        'postid': $this_post_id,
        'videoid': $videoid,
        'statusmessage': $status
      },
      dataType:'json',
      success: function(response){
        console.log(response);
      },
      error: function(XMLHttpRequest, textStatus, errorThrown){
        alert("There was an error: " + errorThrown);
      }
    });
  }



  function setYoutubeVideoInfo() {
    $('#youtube_title').val($('#_coveam_video_title').val()); 
    $('#youtube_description').val($('#_coveam_description').val());
  }
  function makeYouTubeFieldInputsWritable() {
    $('#_coveam_video_title').prop('disabled', false);
    $('#_coveam_description').prop('disabled', false);
    $('#_coveam_airdate').prop('disabled', false);
    $('.youtube-ingest-fields').show();
    if($('#_coveam_youtube_id').val()) {
      $('#youtube-upload-submit button').text('Replace YouTube Video and YouTube Video ID');
      $('#check-youtube-status').show();
      $('.post-youtube-upload').show();
    }
  }
  function checkInProgressVideoStatus() {
    var videoId = $('#_coveam_youtube_id').val();
    checkVideoStatus(videoId, 30 * 1000);
  }
  $(function() {
    $('#youtube-upload-submit').click(initiateYoutubeUpload);
    $('#youtube-thumbnail-upload-submit').click(initiateYoutubeThumbnailUpload);
    $('#check-youtube-status').click(checkInProgressVideoStatus);
    $('#check-youtube-status').hide();
    if($('#_coveam_youtube_id').val()) {
      $('#youtube-video-preview').show();
    }
    setYoutubeVideoInfo();
    if(GoogleAccessToken) {
      validateGoogleToken(GoogleAccessToken);
    }    
  });
});
