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

/**
 * Waiting x milliseconds for a final event than call the callback.
 * @see http://stackoverflow.com/a/4541963
 */
var CPT_waitForFinalEvent = (function () {
	var timers = {};
	return function (callback, ms, uniqueId) {
		if (!uniqueId) {
			uniqueId = "Don't call this twice without a uniqueId";
		}
		if (timers[uniqueId]) {
			clearTimeout (timers[uniqueId]);
		}
		timers[uniqueId] = setTimeout(callback, ms);
	};
})();


/** USAGE ******************
$(window).resize(function () {
	CPT_waitForFinalEvent(function(){
		alert('Resize...');
	}, 500, "some unique string");
});
***************************/

CROP_THUMBNAILS_VUE.components.loadingcontainer = {
	template: "<div class=\"loadingcontainer\" :class=\"status\"> <img :src=\"image\" style=\"display:none;\"/><slot></slot><transition name=\"fade\"><div class=\"loadingMsg\" v-if=\"status===\'loading\'\"><div class=\"cptLoadingSpinner\"></div></div></transition></div>",
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
			this.status = 'loading';
		},
		setFailed : function() {
			this.status = 'failed';
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
			components: CROP_THUMBNAILS_VUE.components
		});
	};
};

CROP_THUMBNAILS_VUE.components.cropeditor = {
	template: "<div class=\"cptEditorInner\" v-if=\"cropData && lang\" :class=\"{loading:loading,cropEditorActive:croppingApi}\"><div class=\"cptWaitingWindow\" v-if=\"loading\"><div class=\"msg\"> {{ lang.waiting }}<div><div class=\"cptLoadingSpinner\"></div></div></div></div><div class=\"mainWindow\"><div class=\"cptSelectionPane\"><div class=\"cptSelectionPaneInner\"><p> <input type=\"checkbox\" :id=\"\'cptSameRatio_\'+_uid\" v-model=\"selectSameRatio\"/> <label :for=\"\'cptSameRatio_\'+_uid\" class=\"cptSameRatioLabel\">{{lang.label_same_ratio}}</label> <button type=\"button\" class=\"button\" @click=\"makeAllInactive()\">{{lang.label_deselect_all}}</button></p><ul class=\"cptImageSizelist\"><li v-for=\"i in filteredImageSizes\" :class=\"{active : i.active}\" @click=\"toggleActive(i)\"><section class=\"cptImageSizeInner\"><header>{{i.nameLabel}}</header><div class=\"dimensions\">{{ lang.dimensions }} {{i.width}} x {{i.height}} {{ lang.pixel }}</div><div class=\"ratio\">{{ lang.ratio }} {{i.printRatio}}</div><loadingcontainer :image=\"i.url+\'?cacheBreak=\'+i.cacheBreak\"><div class=\"cptImageBgContainer\" :style=\"{\'background-image\': \'url(\'+i.url+\'?cacheBreak=\'+i.cacheBreak+\')\'}\"></div></loadingcontainer></section></li></ul></div></div><div class=\"cptCropPane\"><div class=\"info\"><h3>{{ lang.rawImage }}</h3><div class=\"dimensions\">{{ lang.dimensions }} {{cropData.sourceImage.full.width}} x {{cropData.sourceImage.full.height}} {{ lang.pixel }}</div><div class=\"ratio\">{{ lang.ratio }} {{cropData.sourceImage.full.printRatio}}</div></div> <button type=\"button\" class=\"button cptGenerate\" :class=\"{\'button-primary\':croppingApi}\" @click=\"cropThumbnails()\" :disabled=\"!croppingApi\">{{ lang.label_crop }}</button><div class=\"cropContainer\"> <img class=\"cptCroppingImage\" :src=\"cropImage.url\"/></div><div> <button type=\"button\" class=\"button\" v-if=\"cropData.options.debug_js\" @click=\"showDebugClick(\'js\')\">show JS-Debug</button> <button type=\"button\" class=\"button\" v-if=\"cropData.options.debug_data && dataDebug!==null\" @click=\"showDebugClick(\'data\')\">show Data-Debug</button><pre v-if=\"showDebugType===\'data\'\">{{ dataDebug }}</pre><pre v-if=\"showDebugType===\'js\'\"><br/>{{cropImage}}<br/>{{ cropData }}</pre></div><h4>{{ lang.instructions_header }}</h4><ul class=\"step-info\"><li>{{ lang.instructions_step_1 }}</li><li>{{ lang.instructions_step_2 }}</li><li>{{ lang.instructions_step_3 }}</li></ul></div></div></div>",
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
			lang : null,
			nonce:null,
			showDebugType: null,
			dataDebug:null
		};
	},
	mounted:function() {
		this.loadCropData();
	},
	computed:{
		cropImage : function() {
			if(this.cropData!==undefined && this.cropData.sourceImage.large!==null && this.cropData.sourceImage.large.width>745) {
				this.cropData.sourceImage.large.scale = this.cropData.sourceImage.full.width / this.cropData.sourceImage.large.width;
				return this.cropData.sourceImage.large;
			} else {
				this.cropData.sourceImage.full.scale = 1;
				return this.cropData.sourceImage.full;
			}
		},
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
				that.makeAllInactive(responseData.imageSizes);
				that.addCacheBreak(responseData.imageSizes);
				that.cropData = responseData;
				that.lang = that.cropData.lang;
				that.nonce = that.cropData.nonce;
				delete that.cropData.nonce;
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
		activateCropArea : function() {
			var that = this;
			that.deactivateCropArea();
			
			function getPreselect(width,height,targetRatio) {
				var x0 = 0;
				var y0 = 0;
				var x1 = width;
				var y1 = height;
				var sourceRatio = width/height;
				
				if(sourceRatio <= targetRatio) {
					y0 = (height / 2) - ((width / targetRatio) / 2);
					y1 = height-y0;
				} else {
					x0 = (width / 2) - ((height * targetRatio) / 2);
					x1 = width-x0;
				}
				var result = [x0,y0,x1,y1];
				return result;
			}
			
			var options = {
				trueSize: [ that.cropData.sourceImage.full.width , that.cropData.sourceImage.full.height ],
				aspectRatio: 0,
				setSelect: []
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
			
			options.setSelect = getPreselect(that.cropData.sourceImage.full.width , that.cropData.sourceImage.full.height, options.aspectRatio);

			//debug
			if(that.cropData.options.debug_js) {
				console.info('Cropping options',options);
			}
			
			jQuery(that.$el).find('img.cptCroppingImage').Jcrop(options,function(){
				that.croppingApi = this;
			});
		},
		deactivateCropArea : function() {
			if(this.croppingApi!==null) {
				this.croppingApi.destroy();
				this.croppingApi = null;
			}
		},
		showDebugClick : function(type) {
			if(this.showDebugType === type) {
				this.showDebugType = null;
			} else {
				this.showDebugType = type;
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
				
				var params = {
					action : 'cptSaveThumbnail',
					_ajax_nonce : that.nonce,
					cookie : encodeURIComponent(document.cookie),
					crop_thumbnails : JSON.stringify({
						'selection' : that.croppingApi.tellSelect(),
						'sourceImageId' : that.cropData.sourceImageId,
						'activeImageSizes' : getDataOfActiveImageSizes()
					})
				};
				
				var request = jQuery.post(ajaxurl,params,null,'json');
				request
					.done(function(responseData) {
						if(that.cropData.options.debug_data) {
							that.dataDebug = responseData.debug;
							console.log('Save Function Debug',responseData.debug);
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
