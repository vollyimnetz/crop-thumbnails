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

		var title = $(this).attr('title');
		var posttype = null;
		if(data.posttype!==undefined) {
			posttype = data.posttype;
		}
		
		var modal = new CROP_THUMBNAILS_VUE.modal();
		modal.open(data.image_id, posttype, title);
	});
});

if (!Array.prototype.filter) {
	Array.prototype.filter = function(fun/*, thisArg*/) {
		'use strict';

		if (this === void 0 || this === null) {
			throw new TypeError();
		}

		var t = Object(this);
		var len = t.length >>> 0;
		if (typeof fun !== 'function') {
			throw new TypeError();
		}

		var res = [];
		var thisArg = arguments.length >= 2 ? arguments[1] : void 0;
		for (var i = 0; i < len; i++) {
			if (i in t) {
				var val = t[i];

				// NOTE: Technically this should Object.defineProperty at
				//	 the next index, as push can be affected by
				//	 properties on Object.prototype and Array.prototype.
				//	 But that method's new, and collisions should be
				//	 rare, so use the more-compatible alternative.
				if (fun.call(thisArg, val, i, t)) {
					res.push(val);
				}
			}
		}

		return res;
	};
}

if (!Array.prototype.find) {
	Array.prototype.find = function(predicate) {
		'use strict';
		if (this == null) {
			throw new TypeError('Array.prototype.find called on null or undefined');
		}
		if (typeof predicate !== 'function') {
			throw new TypeError('predicate must be a function');
		}
		var list = Object(this);
		var length = list.length >>> 0;
		var thisArg = arguments[1];
		var value;

		for (var i = 0; i < length; i++) {
			value = list[i];
			if (predicate.call(thisArg, value, i, list)) {
				return value;
			}
		}
		return undefined;
	};
}

CROP_THUMBNAILS_VUE.components.loadingcontainer = {
	template: "<div class=\"loadingcontainer\" :class=\"status\"> <img :src=\"image\" style=\"display:none;\"/><slot></slot><div class=\"loading\"><div class=\"cptLoadingSpinner\"></div></div></div>",
	props:{
		image : {
			required: true,
			type:String
		}
	},
	data:function() {
		return {
			status:null
		};
	},
	watch:{
		image:function() {
			this.setup();
		}
	},
	mounted:function() {
		this.setup();
	},
	methods:{
		setup : function() {
			var that = this;
			that.setStart();
			setTimeout(function() {
				var imgLoad = imagesLoaded( that.$el );
				imgLoad
					.once('done',function() {
						if(that.status!=='failed') {
							that.setComplete();
						}
					})
					.once('fail',function() {
						that.setFailed();
					})
					;
			},300);
		},
		setComplete : function() {
			this.status = 'completed';
		},
		setStart : function() {
			this.status = 'started';
		},
		setFailed : function() {
			this.status = 'failed';
		}
	}
};

CROP_THUMBNAILS_VUE.components.cropeditor = {
	template: "<div class=\"cptEditorInner\" v-if=\"cropData && lang\" :class=\"{loading:loading,cropEditorActive:croppingApi}\"><div class=\"cptWaitingWindow\" v-if=\"loading\"><div class=\"msg\"> {{ lang.waiting }}<div><div class=\"cptLoadingSpinner\"></div></div></div></div><div class=\"mainWindow\"><div class=\"cptSelectionPane\"><div class=\"cptSelectionPaneInner\"><p> <input type=\"checkbox\" :id=\"\'cptSameRatio_\'+_uid\" v-model=\"selectSameRatio\"/> <label :for=\"\'cptSameRatio_\'+_uid\" class=\"cptSameRatioLabel\">{{lang.label_same_ratio}}</label> <button type=\"button\" class=\"button\" @click=\"makeAllInactive()\">{{lang.label_deselect_all}}</button></p><ul class=\"cptImageSizelist\"><li v-for=\"i in filteredImageSizes\" :class=\"{active : i.active}\" @click=\"toggleActive(i)\"><section class=\"cptImageSizeInner\"><header>{{i.nameLabel}}</header><div class=\"dimensions\">{{ lang.dimensions }} {{i.width}} x {{i.height}} {{ lang.pixel }}</div><div class=\"ratio\">{{ lang.ratio }} {{i.printRatio}}</div><loadingcontainer :image=\"i.url+\'?cacheBreak=\'+i.cacheBreak\"><div class=\"cptImageBgContainer\" :style=\"{\'background-image\': \'url(\'+i.url+\'?cacheBreak=\'+i.cacheBreak+\')\'}\"></div></loadingcontainer></section></li></ul></div></div><div class=\"cptCropPane\"><h3>{{ lang.rawImage }}: {{cropData.fullSizeImage.width}} x {{cropData.fullSizeImage.height}} {{ lang.pixel }} ({{cropData.fullSizeImage.print_ratio}})</h3><div class=\"cropContainer\"> <img class=\"cptCroppingImage\" :src=\"cropData.fullSizeImage.url\"/></div> <button type=\"button\" class=\"button cptGenerate\" :class=\"{\'button-primary\':croppingApi}\" @click=\"cropThumbnails()\" :disabled=\"!croppingApi\">{{ lang.label_crop }}</button><h4>{{ lang.instructions_header }}</h4><ul class=\"step-info\"><li>{{ lang.instructions_step_1 }}</li><li>{{ lang.instructions_step_2 }}</li><li>{{ lang.instructions_step_3 }}</li></ul></div></div></div>",
	props:{
		imageId : {
			required: true,
			type:Number
		},
		posttype : {
			required:false,
			type:String,
			default:null
		}
	},
	components: {
		loadingcontainer : CROP_THUMBNAILS_VUE.components.loadingcontainer
	},
	data:function() {
		return {
			cropData : null,
			loading : false,
			selectSameRatio : true,
			croppingApi : null,
			lang : null
		};
	},
	mounted:function() {
		this.loadCropData();
	},
	computed:{
		filteredImageSizes : function() {
			return this.cropData.imageSizes
				.filter(function(elem) {
					return !elem.hideByPostType;
				});
		},
		activeImageSizes : function() {
			return this.cropData.imageSizes
				.filter(function(elem) {
					return elem.active;
				});
		}
	},
	methods:{
		loadCropData : function() {
			var that = this;
			var getParameter = {
				action : 'cpt_cropdata',
				imageId : this.imageId,
				posttype : this.posttype
			};
			jQuery.get(ajaxurl,getParameter,function(responseData) {
				that.prepareData(responseData);
				that.cropData = responseData;
				that.lang = that.cropData.lang;
			});
		},
		toggleActive : function(image) {
			var newValue = !image.active;
			
			if(image.active===false) {
				this.makeAllInactive();
			}
			
			if(this.selectSameRatio) {
				this.cropData.imageSizes.forEach(function(i) {
					if(i.printRatio === image.printRatio) {
						i.active = newValue;
					}
				});
			} else {
				image.active = newValue;
			}
			
			if(this.activeImageSizes.length>0) {
				this.activateCropArea();
			} else {
				this.deactivateCropArea();
			}
		},
		makeAllInactive : function(imageSizes) {
			if(imageSizes===undefined) {
				imageSizes = this.cropData.imageSizes;
			}
			imageSizes.forEach(function(i) {
				i.active = false;
			});
			this.deactivateCropArea();
		},
		addCacheBreak : function(imageSizes) {
			if(imageSizes===undefined) {
				imageSizes = this.cropData.imageSizes;
			}
			imageSizes.forEach(function(i) {
				i.cacheBreak = Date.now();
			});
		},
		/**
		 * will be called after getting the data
		 * @param  {object} data the response data of the initial request
		 */
		prepareData : function(data) {
			this.makeAllInactive(data.imageSizes);
			this.addCacheBreak(data.imageSizes);
		},
		activateCropArea : function() {
			var that = this;
			that.deactivateCropArea();
			
			var largestWidth = 0;
			var largestHeight = 0;

			var options = {
				aspectRatio: 0,
				viewMode:1,//for prevent negetive values
				checkOrientation:false,
				background:false, //do not show the grid background
				autoCropArea:1,
				zoomable:false,
				zoomOnTouch:false,
				zoomOnWheel:false
			};

			//get the options
			that.activeImageSizes.forEach(function(i) {
				if(options.aspectRatio === 0) {
					options.aspectRatio = i.ratio;//initial
				}
				if(options.aspectRatio !== i.ratio) {
					console.info('Crop Thumbnails: print ratio is different from normal ratio on image size "'+i.name+'".');
				}
			});

			//debug
			if(that.cropData.debug_js) {
				console.info('Cropping options',options);
			}
			
			var cropElement = jQuery(that.$el).find('.cropContainer img');
			that.croppingApi = new Cropper(cropElement[0], options);
		},
		deactivateCropArea : function() {
			if(this.croppingApi!==null) {
				this.croppingApi.destroy();
				this.croppingApi = null;
			}
		},
		cropThumbnails : function() {
			var that = this;
			
			function getDataOfActiveImageSizes() {
				var result = [];
				that.activeImageSizes.forEach(function(i) {
					if(i.active) {
						var tmp = {
							name: i.name,
							width:i.width,
							height:i.height,
							ratio:i.ratio,
							crop:i.crop
						};
						result.push(tmp);
					}
				});
				return result;
			}
			
			if(!that.loading && that.croppingApi!==null) {
				that.loading = true;
				
				
				var selection = that.croppingApi.getData();
				var selectionData = {//needed cause while changing from jcrop to cropperjs i do not want to change the api
					x:selection.x,
					y:selection.y,
					x2:selection.x + selection.width,
					y2:selection.y + selection.height,
					w:selection.width,
					h:selection.height
				};
				
				var params = {
					action : 'cptSaveThumbnail',
					_ajax_nonce : that.cropData.nonce,
					cookie : encodeURIComponent(document.cookie),
					crop_thumbnails : JSON.stringify({
						'selection' : selectionData,
						'sourceImageId' : that.cropData.imageObj.ID,
						'activeImageSizes' : getDataOfActiveImageSizes()
					})
				};
				
				var request = jQuery.post(ajaxurl,params,null,'json');
				request
					.done(function(responseData) {
						if(that.cropData.debug_js) {
							console.log('Save Function Debug',result.debug);
						}
						if(responseData.error!==undefined) {
							alert(responseData.error);
							return;
						}
						if(responseData.success!==undefined) {
							if(responseData.changedImageName!==undefined) {
								//update activeImageSizes with the new URLs
								that.activeImageSizes.forEach(function(value,key) {
									if(responseData.changedImageName[value.name]!==undefined) {
										value.url = responseData.changedImageName[value.name];
									}
								});
							}
							that.addCacheBreak(that.activeImageSizes);
							return;
						}
					})
					.fail(function(response) {
						console.error(error);
					})
					.always(function() {
						that.loading = false;
					});
			}
		}
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
	
	that.open = function(imageId,posttype,title) {	
		
		
		var id = imageId;
		var modalHtml = '';
		modalHtml+= '<div id="cpt_Modal" class="cpt_Modal">';
		modalHtml+= '<div class="cpt_ModalDialog">';
		modalHtml+= '<div class="cpt_ModalHeader"><div class="cpt_ModalTitle">'+title+'</div><span class="cpt_ModalClose">&times;</span></div>';
		
		modalHtml+= '<div class="cpt_ModalContent" id="cpt_crop_editor">';
		modalHtml+= '<cropeditor image-id="'+id+'"';
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
