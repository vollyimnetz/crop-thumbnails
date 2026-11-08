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
		//the post that is currently edited (null outside of the post-edit-screens) - the server derives the post-type from it
		let postId = data.post_id!==undefined ? data.post_id : window.CROP_THUMBNAILS_CURRENT_POST_ID;
		if(postId===undefined || isNaN(parseInt(postId))) postId = null;

		modal.open(data.image_id, posttype, title, postId);
	});
});
