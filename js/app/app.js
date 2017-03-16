
var CROP_THUMBNAILS_VUE = {
	components : {}
};


/**
 * Provide a global accessable cache-break-function (only available on backend-pages where crop-thumbnail is active --> post-editor, mediathek)
 * Calling this function will add a timestamp on the provided Image-Element.
 * ATTENTION: using this will also delete all other parameters on the images src-attribute.
 * @param {dom-element / jquery-selection} elem
 */
CROP_THUMBNAILS_DO_CACHE_BREAK = function(elem) {
	var $ = jQuery;
	var images = $(elem);
	for(var i = 0; i<images.length; i++) {
		var img = $(images[i]);//select image
		var imageUrl = img.attr('src');
		var imageUrlArray = imageUrl.split("?");
		
		img.attr('src',imageUrlArray[0]+'?&cacheBreak='+(new Date()).getTime());
	}
};

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




CROP_THUMBNAILS_VUE.modal = function() {
	var $ = jQuery;
	var that = this;
	
	function removeModal() {
		$('#cpt_Modal .cpt_ModalClose, #cpt_Modal').unbind('click');
		$('#cpt_Modal').remove();
		$('body').removeClass('cpt_ModalIsOpen');
	}
	
	/**
	 * Should be called when the close-button is clicked.
	 * Will trigger the "cropThumbnailModalClosed"-event to the body on close,
	 * so everyone that is up to, could build a cache-breaker on their images.
	 * HOW-TO cache-break:
	 * $('body').on('cropThumbnailModalClosed',function() {
	 *     CROP_THUMBNAILS_DO_CACHE_BREAK( $('.your-image-selector') );
	 * });
	 * @var Event
	 */
	that.close = function(event) {
		removeModal();
		$('body').trigger('cropThumbnailModalClosed');
	};
	
	/**
	 * Should be called when the background is clicked
	 * @var Event
	 */
	that.closeByBackground = function(event) {
		if(event.target==document.getElementById('cpt_Modal')) {
			that.close(event);
		}
	};
	
	that.open = function(url,title) {
		var modalHtml = '';
		modalHtml+= '<div id="cpt_Modal" class="cpt_Modal">';
		modalHtml+= '<div class="cpt_ModalDialog">';
		modalHtml+= '<div class="cpt_ModalHeader"><div class="cpt_ModalTitle">'+title+'</div><span class="cpt_ModalClose">&times;</span></div>';
		
		modalHtml+= '<div class="cpt_ModalContent" id="cpt_crop_editor">';
		modalHtml+= '<cropeditor></cropeditor>'
		modalHtml+= '</div>';//end cpt_ModalContent
		modalHtml+= '</div>';//end cpt_ModalDialog
		modalHtml+= '</div>';//end cpt_Modal;
		
		
		$('body').prepend(modalHtml).addClass('cpt_ModalIsOpen');
		$('#cpt_Modal .cpt_ModalClose').click(that.close);
		$('#cpt_Modal').click(that.closeByBackground);
		
		
		var app = new Vue({
			el:'#cpt_crop_editor',
			mounted:function() {
				console.log('cpt_crop_editor mounted');
			},
			components: CROP_THUMBNAILS_VUE.components,
			data: {
				test: [
					{ text: 'test 1' },
					{ text: 'test 2' },
					{ text: 'test 3' }
				]
			}
		});
	};
};

CROP_THUMBNAILS_VUE.components.cropeditor = {
	template: '<div>hallo welt</div>',
	mounted:function() {
		console.log('cropeditor mounted');
	}
};
