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
          $('#initiate_batch_import .status').append(" page " + batchpage + " complete");
          $('#initiate_batch_import .failed').append(response.failed);
          if (response.errors) {
            $('#initiate_batch_import .status').append("error :" + JSON.stringify(response.errors));
          } else {
            batchMediaManagerImport();
          }
        } else {
          $('#initiate_batch_import .status').append( " Batch Import COMPLETE" );
        }
      },
      error: function(XMLHttpRequest, textStatus, errorThrown){
        console.log(errorThrown);
        $('#initiate_batch_import .status').append("ERROR " + textStatus + " : The import can be retried if you reload this page." );
      }
    });
  }

  $(function() {
    $('#initiate_batch_import button').click(controlBatchMediaManagerImport);
  });

	
});
