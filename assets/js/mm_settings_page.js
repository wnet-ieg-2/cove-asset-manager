jQuery(document).ready(function($) {

  var batchpage = 0;

  function controlBatchMediaManagerImport() {
    if (batchpage < 1) {
      $('#initiate_batch_import button').text("Processing ");
      $('#initiate_batch_import .success').text("Post IDs updated: ");
      $('#initiate_batch_import .failed').text("Post IDs failed to update: ");
      batchMediaManagerImport();
    } else {
      $('#initiate_batch_import button').append(" STOP CLICKING.  Import can't be restarted unless page is reloaded. ");
    }
  }

  function batchMediaManagerImport() {
    batchpage++;
    $.ajax({
      type: "post",
      url: ajaxurl,
      data:{
        'action': 'bulk_import_media_manager_asset_and_episode_ids',
        'pagenum': batchpage
      },
      dataType:'json',
      success: function(response){
        console.log(response);
        if (response.updated.length || response.failed.length) {
          $('#initiate_batch_import .success').append(response.updated.join(", ") + ", ");
          $('#initiate_batch_import .status').text(" page " + batchpage + " complete");
          $('#initiate_batch_import .failed').append(response.failed.join(", ") + ", ");
          if (response.errors) {
            $('#initiate_batch_import .status').append("error :" + JSON.stringify(response.errors));
          } else {
            batchMediaManagerImport();
          }
        } else {
          $('#initiate_batch_import .status').text( " Batch Import COMPLETE" );
        }
      },
      error: function(XMLHttpRequest, textStatus, errorThrown){
        console.log(errorThrown);
        $('#initiate_batch_import .status').append("ERROR " + textStatus + " : The import can be retried if you reload this page." );
      }
    });
  }

  var epbatchpage = 0;

  function controlMediaManagerEpisodeMatch() {
    if (epbatchpage < 1 && $('#initiate_episode_match input').val()) {
      $('#initiate_episode_match button').text("Processing ");
      $('#initiate_episode_match .success').text("Post IDs updated: ");
      $('#initiate_episode_match .failed').text("Post IDs failed to update: ");
      batchMediaManagerEpisodeMatch();
    } else {
      $('#initiate_episode_match button').append(" STOP CLICKING.  Import can't be restarted unless page is reloaded. ");
    }
  }

  function batchMediaManagerEpisodeMatch() {
    epbatchpage++;
    $.ajax({
      type: "post",
      url: ajaxurl,
      data:{
        'action': 'bulk_match_media_manager_episodes',
        'pagenum': epbatchpage,
        'season_id': $('#initiate_episode_match input').val()
      },
      dataType:'json',
      success: function(response){
        console.log(response);
        if ((typeof response.updated !== 'undefined') && (response.updated.length || response.failed.length )) {
          $('#initiate_episode_match .success').append(response.updated.join(", ") + ", ");
          $('#initiate_episode_match .status').text(" page " + epbatchpage + " complete");
          $('#initiate_episode_match .failed').append(response.failed.join(", ") + ", ");
          if (response.errors) {
            $('#initiate_episode_match .status').append("error :" + JSON.stringify(response.errors));
          } else {
            batchMediaManagerEpisodeMatch();
          }
        } else {
          $('#initiate_episode_match .status').text( " Batch Import COMPLETE" );
          $('#initiate_episode_match button').text("Match another season ");
          epbatchpage = 0;
        }
      },
      error: function(XMLHttpRequest, textStatus, errorThrown){
        console.log(errorThrown);
        $('#initiate_episode_match .status').append("ERROR " + textStatus + " : The import can be retried if you reload this page." );
      }
    });
  }




  $(function() {
    $('#initiate_batch_import button').click(controlBatchMediaManagerImport);
    $('#initiate_episode_match button').click(controlMediaManagerEpisodeMatch);
  });

	
});
