jQuery(document).ready(function($) {

	$('body').on('change', '#epmonthselect, #epyearselect', function(e) {
		month = $('#epmonthselect option:selected').val();
		year = $('#epyearselect option:selected').val();
		console.log(year);
		if (month && year) {
			$('#_pbs_media_manager_episode_cid').html('<option>loading</option>');
			$.get( ajaxurl+'?action=coveam_get_episode_option_list&month='+month+'&year='+year+'', function( data ) {
				$('#_pbs_media_manager_episode_cid').html(data);
			});
		}
		e.preventDefault();
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

  if ($('#_coveam_cove_player_id').val() != '' && $('#_coveam_covestatus').text().toLowerCase() == 'available') {
    covepreviewlink = '//player.pbs.org/widget/partnerplayer/' + $('#_coveam_cove_player_id').val() + '/?start=0&end=0&chapterbar=false&endscreen=false&topbar=true&autoplay=false&TB_iframe=true&width=600&height=400';
    $('.cove-player-id-selector p.description').before('<div id="cove-preview-link"><a href="' + covepreviewlink + '" class="thickbox">Preview COVE video <i>(opens new window)</i></a></div>');
  }
  
  function toggle_asset_fields() {
    var fullprogram = $('input[type=radio][name=_coveam_video_fullprogram]:checked').val();
    var createupdate = $('input[type=radio][name=media_manager_action]:checked').val();
    var toggleaction = 'on';
    if (fullprogram === '0' && createupdate !== 'noaction') {
      toggleaction = 'off'; 
    } 
    var assetfields = ["_coveam_video_title", "_coveam_shortdescription", "_coveam_description"];
    for (i = 0; i < 3; i++) {
      var thisinput = '#' + assetfields[i];
      if (toggleaction == 'off') {
        $(thisinput).prop('readonly', true);
        if (!$('span.readonly-warning.' + assetfields[i]).length) {
          $(thisinput).after('<span class="readonly-warning ' + assetfields[i] +'">This value will come from the episode and cannot be edited here.</span>');
        }
      } else {
        $(thisinput).prop('readonly', false);
        $('span.readonly-warning.' + assetfields[i]).remove();
      }
    }
  }

  $('input[type=radio][name=_coveam_video_fullprogram], input[type=radio][name=media_manager_action]').change(function() {
    toggle_asset_fields();
  });

  // on page load check too 
  toggle_asset_fields();
  
});
