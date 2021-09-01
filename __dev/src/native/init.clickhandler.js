/**
 * Create Listener for click-events with element-class ".cropThumbnailsLink".
 * Open the modal box.
 */
jQuery(function($) {
	$(document).on('click', '.cropThumbnailsLink', function(e) {
		e.preventDefault();

		//get the data from the link
		var data = $(this).data('cropthumbnail');

		var title = $(this).attr('title');
		var posttype = null;
		if(data.posttype!==undefined) {
			posttype = data.posttype;
		}
		
		var modal = new CROP_THUMBNAILS_VUE.modal();
		modal.open(data.image_id, posttype, title);
	});
});
