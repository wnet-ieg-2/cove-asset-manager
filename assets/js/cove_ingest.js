// helper functions and variables to make youtube/google client-side upload and polling work
jQuery(document).ready(function($) {

  function batchExpireCoveAssets() {
    $.ajax({
      type: "post",
      url: ajaxurl,
      data:{
        'action': 'coveam_check_inprogress_ingest_videos'
      },
      dataType:'json',
      success: function(response){
        console.log(response)
      },
      error: function(XMLHttpRequest, textStatus, errorThrown){
        console.log(errorThrown);
      }
    });
  }
  
  function initiateCoveBatchJob(event) {
    event.preventDefault();
    var $this_post_id = $('#this_post_id').text();
    $.ajax({
      type: "post",
      url: ajaxurl,
      data:{
        'action': 'coveam_submit_cove_batch_ingest_task',
        'postid': $this_post_id
      },
      dataType:'json',
      success: function(response){
        var task_url = response.Location;
        $('#cove-ingest-status').text("Job successfully submitted for ingest");
        $('#_coveam_ingest_task').text(task_url);
        $('#submit_cove_ingest').hide();
        $('.cove_process_status').hide();
        $('.cove-post-ingest-submit').show();
      },
      error: function(XMLHttpRequest, textStatus, errorThrown){  
        $('#cove-ingest-status').text("Ingest submit failed:" +errorThrown);  
      } 
    });
  }

  function checkCoveBatchJobStatus() {
    var $this_task_url = $('#_coveam_ingest_task').text();
    var $this_post_id = $('#this_post_id').text();
    $.ajax({
      type: "post",
      url: ajaxurl,
      data:{
        'action': 'coveam_ajax_get_batch_ingest_task_status',
        'task_url': $this_task_url,
        'postid': $this_post_id
      },
      dataType:'json',
      success: function(response){
        console.log(response);
        try {
          if (response.ingestion_response) {
            var responsetext = JSON.stringify(response.ingestion_response);
            if (response.ingestion_response.video_asset_status) {
              responsetext = "Status: " + response.ingestion_response.video_asset_status + " GUID: " + response.ingestion_response.video_asset_guid;
              setTimeout(checkCoveBatchJobStatus, 10000);
            }
            if (response.ingestion_response.video_asset_status == "Ok") {
              responsetext = responsetext + "<br>Ingest successful at " + response.ingestion_response.video_asset_modified_date + "(UTC).  Video will be available in COVE approximately 10 minutes later.  Update/save the post to check final status.";
            }
            $('#cove-ingest-status').text(responsetext);
          } else {
            if (response.results[0].tp_media_object_id) {
              $('#cove-ingest-status').text("Ingest complete and cove player ready. Update this post to see the results.");
              $('#_coveam_ingest_task').text('');
              $('#_coveam_ingest_task').hide();
            } 
          }
        } catch(e) {
          $('#cove-ingest-status').text("Unable to get the status");
        }
      },
      error: function(XMLHttpRequest, textStatus, errorThrown){
         alert("There was an error: " + errorThrown);
      }
    });
  }

  function updateCoveStatusFromGUID() {
    var $this_guid = $('#_coveam_video_asset_guid').text();
    var $this_post_id = $('#this_post_id').text();
    $.ajax({
      type: "post",
      url: ajaxurl,
      data:{
        'action': 'coveam_ajax_update_status_from_guid',
        'guid': $this_guid,
        'postid': $this_post_id
      },
      dataType:'json',
      success: function(response){
        console.log(response);
        try {
          if (response.ingestion_response) {
            var responsetext = JSON.stringify(response.ingestion_response);
            if (response.ingestion_response.video_asset_status) {
              responsetext = "Status: " + response.ingestion_response.video_asset_status + " GUID: " + response.ingestion_response.video_asset_guid;
              if (response.ingestion_response.video_asset_status != "Ok") {
                setTimeout(updateCoveStatusFromGUID, 10000);
              } else {
                responsetext = responsetext +  "<br>Ingest successful at " + response.ingestion_response.video_asset_modified_date + "(UTC).  Video will be available in COVE approximately 10 minutes after that.";
              }
            }
            $('#_coveam_covestatus_long').text(responsetext);
          } else {
            if (response.results[0].tp_media_object_id) {
              $('#_coveam_covestatus_long').text("Ingest complete and cove player ready. COVE ID: " + response.results[0].tp_media_object_id);
              $('#_coveam_cove_player_id').val(response.results[0].tp_media_object_id);
              $('.cove-player-id-selector').show();
              $('#_coveam_cove_player_id').show();
            }
          }
        } catch(e) {
          if (response) {
            $('#_coveam_covestatus_long').text(response);
            setTimeout(updateCoveStatusFromGUID, 10000);
          } else {
            $('#_coveam_covestatus_long').text("Unable to get the status");
            console.log(e.message);
          }
        }
      },
      error: function(XMLHttpRequest, textStatus, errorThrown){
         alert("There was an error: " + errorThrown);
      }
    });
  }

  function showCoveIngestForm() {
    $('.cove-ingest-fields').show();
    $('.cove-asset-details input').prop('disabled', false);
    $('.cove-asset-details textarea').prop('disabled', false);
    $('#_coveam_cove_player_id').hide();
    $('#cove-preview-link').remove();
    var coveam_cove_video_id_temp = $('#_coveam_cove_player_id').val();
    $('#coveam_cove_video_id_temp').text(coveam_cove_video_id_temp);
    $('#_coveam_cove_player_id').val('');
    $('#show-ingest-form').hide();
    $('#hide-ingest-form').show();
    $('#_coveam_preparing').prop('checked', true); 
    $('#submit_cove_ingest').hide();
  } 

  function hideCoveIngestForm() {
    $('.cove-ingest-fields').hide();
    $('.cove-asset-details input').prop('disabled', 'true');
    $('.cove-asset-details textarea').prop('disabled', 'true');
    $('#_coveam_cove_player_id').show();
    var coveam_cove_video_id_temp = $('#coveam_cove_video_id_temp').text();
    if (coveam_cove_video_id_temp != '') { 
      $('#_coveam_cove_player_id').val(coveam_cove_video_id_temp);
    }
    if ($('#_coveam_video_asset_guid').text() != '') {
       $('#check-cove-status-from-guid').show();
    }
    $('#show-ingest-form').show();
    $('#hide-ingest-form').hide();
    $('#_coveam_useid').prop('checked', true);
    $('#submit_cove_ingest').hide();
    if ($('#_coveam_cove_player_id').val() != ''){
      covepreviewlink = 'http://player.pbs.org/widget/partnerplayer/' + $('#_coveam_cove_player_id').val() + '/?start=0&end=0&chapterbar=false&endscreen=false&topbar=true&autoplay=false&TB_iframe=true&width=600&height=400';
      $('#show-ingest-form').before('<div id="cove-preview-link"><a href="' + covepreviewlink + '" class="thickbox">Preview COVE video <i>(opens new window)</i></a></div>');
    }
  }
  
  function showCoveIngestSubmit() {
    $('.cove-ingest-fields').show();
    $('.cove-player-id-selector').hide();
    $('.cove-ingest-fields input').prop('disabled', true);
    $('.cove-asset-details input').prop('disabled', true);
    $('.cove-asset-details textarea').prop('disabled', true);
    $('.cove_process_status input').prop('disabled', false);
    $('.cove-ingest-hide').hide();
    $('#submit_cove_ingest').show();
  } 

  $(function() {
    $('#submit_cove_ingest').click(initiateCoveBatchJob);
    $('#check-cove-ingest-status').click(checkCoveBatchJobStatus);
    $('#check-cove-status-from-guid').click(updateCoveStatusFromGUID);
    $('#show-ingest-form').click(showCoveIngestForm);
    $('#hide-ingest-form').click(hideCoveIngestForm);
    if ($('#_coveam_preparing').is(':checked')) {
      showCoveIngestForm();
    } else if ($('#_coveam_readytosubmit').is(':checked')){
      showCoveIngestSubmit(); 
    } else if ($('#_coveam_cove_player_id').val()){
      hideCoveIngestForm();
    } else {
      showCoveIngestForm();
    }
  });

  // validator taken from http://stackoverflow.com/users/352705/tbleckert
  $("textarea[data-limit-input], input[data-limit-input]").keyup(function (e) {
    var $this      = $(this),
        charLength = $this.val().length,
        charLimit  = $this.attr("data-limit-input");
        //Displays count
    $this.next("span").html(charLength + " of " + charLimit + " characters used");
    // Alert when max is reached
    if ($this.val().length > charLimit) {
      $this.next("span").html("<strong>You may only have up to " + charLimit + " characters.</strong>");
    }
  });

  $("textarea[data-limit-input], input[data-limit-input]").keydown(function (e) {
    var $this      = $(this),
        charLength = $this.val().length,
        charLimit  = $this.attr("data-limit-input");
                                         
    if ($this.val().length > charLimit && e.keyCode !== 8 && e.keyCode !== 46) {
      return false;
    }
  });

});
