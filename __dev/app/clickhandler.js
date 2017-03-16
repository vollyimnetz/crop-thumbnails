/**
 * Create Listener for click-events with element-class ".cropThumbnailsLink".
 * Open the modal box.
 */
jQuery(document).ready(function($) {
	$(document).on('click', '.cropThumbnailsLink', function(e) {
		e.preventDefault();

		//get the data from the link
		var data = $(this).data('cropthumbnail');

		//construct the thickbox-parameter
		var url = ajaxurl+'?action=croppostthumb_ajax';
		for(var v in data) {
			url+='&amp;'+v+'='+data[v];
		}
		var title = $(this).attr('title');
		
		
		var modal = new CROP_THUMBNAILS_VUE.modal();
		modal.open(url,title);
	});
});
