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
});
