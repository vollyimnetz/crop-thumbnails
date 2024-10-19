/**
 * Create Listener for click-events with element-class ".cropThumbnailsLink".
 * Open the modal box.
 */
import jQuery from "jquery";
jQuery(function($) {
	$(document).on('click', '.cropThumbnailsLink', (e, elem) => {
		e.preventDefault();

		//get the data from the link
		const data = JSON.parse( e.target.dataset.cropthumbnail );
		if(!data) return;
		const title = e.target.title
		const posttype = data.posttype!==undefined ? data.posttype : null;

		const modal = new window.CROP_THUMBNAILS_VUE.modal();
		modal.open(data.image_id, posttype, title);
	});
});
