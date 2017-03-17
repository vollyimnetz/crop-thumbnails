/**
 * Create Listener for click-events with element-class ".cropThumbnailsLink".
 * Open the modal box.
 */
jQuery(document).ready(function($) {
	$(document).on('click', '.cropThumbnailsLink', function(e) {
		e.preventDefault();

		//get the data from the link
		var data = $(this).data('cropthumbnail');
		console.log('cropthumbnail data',data);

		var title = $(this).attr('title');
		
		var modal = new CROP_THUMBNAILS_VUE.modal();
		modal.open(data.image_id, title);
	});
});
