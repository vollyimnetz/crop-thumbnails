
var CROP_THUMBNAILS_VUE = {
	app:null,//will be initialized in modal/modal.js
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
		console.log('cropthumbnail data',data);

		var title = $(this).attr('title');
		
		var modal = new CROP_THUMBNAILS_VUE.modal();
		modal.open(data.image_id, title);
	});
});

CROP_THUMBNAILS_VUE.components.cropeditor = {
	template: "<div class=\"cpteditorInner\" v-if=\"cropData.lang\"><div class=\"waitingWindow hidden\" v-if=\"loading\">{{ cropData.lang.waiting }}</div><div class=\"mainWindow\"><div class=\"selectionArea cptLeftPane\"><h3>{{ cropData.lang.rawImage }}: {{cropData.fullSizeImage.width}} {{ cropData.lang.pixel }} x {{cropData.fullSizeImage.height}} {{ cropData.lang.pixel }} ({{cropData.fullSizeImage.print_ratio}})</h3><div class=\"cropContainer\"> <img id=\"cpt-croppingImage\" :src=\"cropData.fullSizeImage.url\"/></div> <button id=\"cpt-generate\" class=\"button\">{{ cropData.lang.label_crop }}</button><h4>{{ cropData.lang.instructions_header }}</h4><ul class=\"step-info\"><li>{{ cropData.lang.instructions_step_1 }}</li><li>{{ cropData.lang.instructions_step_2 }}</li><li>{{ cropData.lang.instructions_step_3 }}</li></ul></div><div class=\"cptRightPane\"> <input type=\"checkbox\" name=\"cpt-same-ratio\" v-model=\"selectSameRatio\"/> <label for=\"cpt-same-ratio\" class=\"lbl-cpt-same-ratio\">{{cropData.lang.label_same_ratio}}</label> <button id=\"cpt-deselect\" class=\"button\">{{cropData.lang.label_deselect_all}}</button><ul class=\"thumbnail-list\"><li v-for=\"i in cropData.imageSizes\"> <strong>{{i.name}} <span class=\"lowResWarning\">{{cropData.lang.lowResWarning}}</span></strong> <span class=\"dimensions\">{{cropData.lang.dimensions}} {{i.width}} {{ cropData.lang.pixel }} x {{i.height}} {{ cropData.lang.pixel }}</span> <span class=\"ratio\">{{ cropData.lang.ratio }} {{i.imageData.print_ratio}}</span> <img :src=\"i.imageData.url\"/></li></ul></div></div></div>",
	props:[
		'imageId'
	],
	data:function() {
		return {
			cropData : '',
			loading : false,
			selectSameRatio : true
		};
	},
	mounted:function() {
		var that = this;
		
		that.cropData = axios.get('http://localhost/wordpress-dev/wp-admin/admin-ajax.php?action=cpt_cropdata&imageId='+this.imageId)
			.then(function(response) {
				that.cropData = response.data;
				console.log('data loaded',response.data,that.cropData);
			});
	}
};

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
		CROP_THUMBNAILS_VUE.app.$destroy();
		CROP_THUMBNAILS_VUE.app = null;
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
	
	that.open = function(imageId,title) {	
		
		var id = imageId;
		var modalHtml = '';
		modalHtml+= '<div id="cpt_Modal" class="cpt_Modal">';
		modalHtml+= '<div class="cpt_ModalDialog">';
		modalHtml+= '<div class="cpt_ModalHeader"><div class="cpt_ModalTitle">'+title+'</div><span class="cpt_ModalClose">&times;</span></div>';
		
		modalHtml+= '<div class="cpt_ModalContent" id="cpt_crop_editor">';
		modalHtml+= '<cropeditor image-id="'+id+'"></cropeditor>'
		modalHtml+= '</div>';//end cpt_ModalContent
		modalHtml+= '</div>';//end cpt_ModalDialog
		modalHtml+= '</div>';//end cpt_Modal;
		
		
		$('body').prepend(modalHtml).addClass('cpt_ModalIsOpen');
		$('#cpt_Modal .cpt_ModalClose').click(that.close);
		$('#cpt_Modal').click(that.closeByBackground);
		
		
		CROP_THUMBNAILS_VUE.app = new Vue({
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
