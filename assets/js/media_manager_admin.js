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
	
});
