import { createApp } from 'vue'
import cropeditor from './../components/cropeditor.vue';
window.CROP_THUMBNAILS_VUE.modal = function() {
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
		window.CROP_THUMBNAILS_VUE.app.unmount();
		window.CROP_THUMBNAILS_VUE.app = null;
		removeModal();
		$('body').trigger('cropThumbnailModalClosed');
		document.removeEventListener('keydown', that.closeByEscKey, true);
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
	
	that.closeByEscKey = function(event) {
		if ( !event.keyCode || event.keyCode === 27 ) {
			event.stopPropagation();
			that.close(event);
		}
	}
	
	that.open = function(imageId,posttype,title) {	
		
		
		var id = imageId;
		var modalHtml = '';
		modalHtml+= '<div id="cpt_Modal" class="cpt_Modal">';
		modalHtml+= '<div class="cpt_ModalDialog" role="dialog" aria-label="'+$('<div>').text(title).html()+'">';
		modalHtml+= '<button type="button" class="cpt_ModalClose" aria-label="close">&times;</button>';
		modalHtml+= '<div class="cpt_ModalHeader"><div class="cpt_ModalTitle">'+title+'</div></div>';
		
		modalHtml+= '<div class="cpt_ModalContent" id="cpt_crop_editor">';
		modalHtml+= '<cropeditor :image-id="'+id+'"';
		if(typeof posttype === 'string') {
			modalHtml+= ' posttype="'+posttype+'"';
		}
		modalHtml+= '></cropeditor>'
		modalHtml+= '</div>';//end cpt_ModalContent
		modalHtml+= '</div>';//end cpt_ModalDialog
		modalHtml+= '</div>';//end cpt_Modal;
		
		
		$('body').prepend(modalHtml).addClass('cpt_ModalIsOpen');
		$('#cpt_Modal .cpt_ModalClose').click(that.close);
		$('#cpt_Modal').on('touchstart mousedown',that.closeByBackground);
		document.addEventListener('keydown', that.closeByEscKey, true);
		
		window.CROP_THUMBNAILS_VUE.app = createApp();

		window.CROP_THUMBNAILS_VUE.app
			.component('cropeditor',cropeditor)
			.mount('#cpt_crop_editor')
	};
};
