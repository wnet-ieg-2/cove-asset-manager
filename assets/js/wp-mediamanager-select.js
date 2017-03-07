/* adapted from Tomasz Dziuda's article 
* https://www.gavick.com/blog/use-wordpress-media-manager-plugintheme
* 
* uses the wordpress media manager to select an image and return the url
*/
jQuery(document).ready(function($) {

  var selector = $("#_coveam_video_image");
  var button = $("#_coveam_image_mediamanager");
  var clicked_button = false;
 
  button.click(function (event) {
    event.preventDefault();
    var selected_img;
    clicked_button = $(this);
 
    // check for media manager instance
    if(wp.media.frames.mm_select_frame) {
      wp.media.frames.mm_select_frame.open();
      return;
    }
    // configuration of the media manager new instance
    wp.media.frames.mm_select_frame = wp.media({
      title: 'Select image',
      multiple: false,
      library: {
        type: 'image'
      },
      button: {
        text: 'Use selected image'
      }
    });
 
    // Function used for the image selection and media manager closing
    var mm_select_media_set_image = function() {
      var selection = wp.media.frames.mm_select_frame.state().get('selection');

      // no selection
      if (!selection) {
        return;
      }
 
      // iterate through selected elements
      selection.each(function(attachment) {
        var url = attachment.attributes.url;
        selector.val(url);
      });
    };
 
    // closing event for media manger
    wp.media.frames.mm_select_frame.on('close', mm_select_media_set_image);
    // image selection event
    wp.media.frames.mm_select_frame.on('select', mm_select_media_set_image);
    // showing media manager
    wp.media.frames.mm_select_frame.open();
  });
});
