jQuery(document).ready(function($) {

var COVEAMASSETURL = $('#plugin_assets_url').text();
var COVEAMFILETYPE;

function AWScreateCORSRequest(method, url) {
  var xhr = new XMLHttpRequest();
  if ("withCredentials" in xhr) 
  {
    xhr.open(method, url, true);
  } 
  else if (typeof XDomainRequest != "undefined") 
  {
    xhr = new XDomainRequest();
    xhr.open(method, url);
  } 
  else 
  {
    xhr = null;
  }
  return xhr;
}



/**
 * Execute the given callback with the signed response.
 */
function AWSexecuteOnSignedUrl(file, callback) {
  $.ajax({
    type: "GET",
    url: ajaxurl,
    data:{
      'action': 'coveam_sign_aws_request',
      'slug': file.name,
      'fileinfo': file.type
    },
    dataType:'text',
    success: function(response){
      console.log(response);
      callback(decodeURIComponent(response));
    },
    error: function(response){
      console.log(response);
    }
  });
}


function AWSuploadFile() {
  var whichfile = COVEAMFILETYPE;
  var file = false;
  if (whichfile == "video") {
    file = $('#video_file_to_upload').get(0).files[0];
  } else if (whichfile == "image") {
    file = $('#image_file_to_upload').get(0).files[0];
  } else if (whichfile == "caption") {
    file = $('#caption_file_to_upload').get(0).files[0];
  }
  AWSexecuteOnSignedUrl(file, function(signedURL){
    AWSuploadToS3(file, signedURL);
  });
}

/**
 * Use a CORS call to upload the given file to S3. Assumes the url
 * parameter has been signed and is accessible for upload.
 */
function AWSuploadToS3(file, url) {
  var slug = file.name;
  var xhr = AWScreateCORSRequest('PUT', url);
  if (!xhr) {
    AWSsetProgress(0, 'CORS not supported');
  }
  else {
    xhr.onload = function() {
      if(xhr.status == 200)
      {
        AWSsetProgress(100, 'Upload completed.');
        var uploadedfile = 'https://s3.amazonaws.com/' + $('#s3_bucket').text();
        if ($('#s3_proxy').text()) {
          uploadedfile = $('#s3_proxy').text();
        }
        if ($('#s3_bucket_dir').text()) {
          uploadedfile = uploadedfile + '/' + $('#s3_bucket_dir').text();     
        } 
        uploadedfile = uploadedfile + '/' + slug;
        if (COVEAMFILETYPE == 'image') {
          $('#_coveam_video_image').val(uploadedfile);
        } else if  (COVEAMFILETYPE == 'caption') {
          $('#_coveam_video_caption').val(uploadedfile);
        } else {
          $('#_coveam_video_url').val(uploadedfile);
        }
        $('#submit_cove_ingest').hide();
        $('#submit_cove_ingest').append('<p>You must save your changes before submitting to COVE</p>');
        $('#s3-upload-image').show();
        $('#s3-upload-video').show();
        $('#s3-upload-caption').show();
      }
      else
      {
        AWSsetProgress(0, 'Upload error: ' + xhr.status);
      }
    };

    xhr.onerror = function() {
      AWSsetProgress(0, 'XHR error:' + xhr.statusText);
    };

    xhr.upload.onprogress = function(e) {
      if (e.lengthComputable) 
      {
        var percentLoaded = Math.round((e.loaded / e.total) * 100);
        $('#' + COVEAMFILETYPE + '-s3-upload-progress').attr({
        value: e.loaded,
        max: e.total
        });
        AWSsetProgress(percentLoaded, percentLoaded == 100 ? 'Finalizing.' : 'Uploading.');
      }
    };

    xhr.setRequestHeader('Content-Type', file.type);
    xhr.setRequestHeader('x-amz-acl', 'public-read');
  //  var formdata = new FormData();
   // formdata.append("file", file, slug);
    xhr.send(file);
  }
}

function AWSsetProgress(percentage, statusLabel) {
  $('#' + COVEAMFILETYPE + '-s3-percent-transferred').text(percentage);
  $('.' + COVEAMFILETYPE + '-during-s3-upload').show();
  $('#' + COVEAMFILETYPE + '-s3-post-upload-status').text(statusLabel);
}


  $(function() {
    $('#s3-upload-video').click(function(event) {
       event.preventDefault();
       COVEAMFILETYPE = "video";
       AWSuploadFile();
       $('#s3-upload-image').hide();
       $('#s3-upload-video').hide();
       $('#s3-upload-caption').hide();
      });
   $('#s3-upload-image').click(function(event) {
       event.preventDefault();
       COVEAMFILETYPE = "image";
       AWSuploadFile();
       $('#s3-upload-image').hide();
       $('#s3-upload-video').hide();
       $('#s3-upload-caption').hide();
      });
    $('#s3-upload-caption').click(function(event) {
       event.preventDefault();
       COVEAMFILETYPE = "caption";
       AWSuploadFile();
       $('#s3-upload-image').hide();
       $('#s3-upload-video').hide();
       $('#s3-upload-caption').hide(); 
      });
  });
});

