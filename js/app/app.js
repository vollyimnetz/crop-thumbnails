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

CROP_THUMBNAILS_VUE.components.loadingcontainer = {
	template: "<div> {{status}}<slot></slot></div>",
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
			console.log('image value changed',this.image);
			this.setup();
		}
	},
	mounted:function() {
		this.setup();
	},
	methods:{
		setup : function() {
			var that = this;
			this.setStart();
			imagesLoaded( this.$el, { background: true }, function() { that.setComplete(); } );
		},
		setComplete : function() {
			console.log('complete');
			this.status = 'completed';
		},
		setStart : function() {
			this.status = 'started';
		}
	}
};

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

//polyfill for URLSearchParams support
/*! (C) WebReflection Mit Style License */
var URLSearchParams=URLSearchParams||function(){"use strict";function e(e){var n,r,i,s,o,u,c=Object.create(null);this[a]=c;if(!e)return;if(typeof e=="string"){e.charAt(0)==="?"&&(e=e.slice(1));for(s=e.split("&"),o=0,u=s.length;o<u;o++)i=s[o],n=i.indexOf("="),-1<n?f(c,l(i.slice(0,n)),l(i.slice(n+1))):i.length&&f(c,l(i),"")}else if(t(e))for(o=0,u=e.length;o<u;o++)i=e[o],f(c,i[0],i[1]);else for(r in e)f(c,r,e[r])}function f(e,n,r){n in e?e[n].push(""+r):e[n]=t(r)?r:[""+r]}function l(e){return decodeURIComponent(e.replace(i," "))}function c(e){return encodeURIComponent(e).replace(r,o)}function h(){try{return!!Symbol.iterator}catch(e){return!1}}var t=Array.isArray,n=e.prototype,r=/[!'\(\)~]|%20|%00/g,i=/\+/g,s={"!":"%21","'":"%27","(":"%28",")":"%29","~":"%7E","%20":"+","%00":"\0"},o=function(e){return s[e]},u=h(),a="__URLSearchParams__:"+Math.random();n.append=function(t,n){f(this[a],t,n)},n.delete=function(t){delete this[a][t]},n.get=function(t){var n=this[a];return t in n?n[t][0]:null},n.getAll=function(t){var n=this[a];return t in n?n[t].slice(0):[]},n.has=function(t){return t in this[a]},n.set=function(t,n){this[a][t]=[""+n]},n.forEach=function(t,n){var r=this[a];Object.getOwnPropertyNames(r).forEach(function(e){r[e].forEach(function(r){t.call(n,r,e,this)},this)},this)},n.keys=function(){var t=[];this.forEach(function(e,n){t.push(n)});var n={next:function(){var e=t.shift();return{done:e===undefined,value:e}}};return u&&(n[Symbol.iterator]=function(){return n}),n},n.values=function(){var t=[];this.forEach(function(e){t.push(e)});var n={next:function(){var e=t.shift();return{done:e===undefined,value:e}}};return u&&(n[Symbol.iterator]=function(){return n}),n},n.entries=function(){var t=[];this.forEach(function(e,n){t.push([n,e])});var n={next:function(){var e=t.shift();return{done:e===undefined,value:e}}};return u&&(n[Symbol.iterator]=function(){return n}),n},u&&(n[Symbol.iterator]=n.entries),n.toJSON=function(){return{}},n.toString=function w(){var e=this[a],t=[],n,r,i,s;for(r in e){i=c(r);for(n=0,s=e[r];n<s.length;n++)t.push(i+"="+c(s[n]))}return t.join("&")};var p=Object.defineProperty,d=Object.getOwnPropertyDescriptor,v=function(e){function t(t,r){n.append.call(this,t,r),t=this.toString(),e.set.call(this._usp,t?"?"+t:"")}function r(t){n.delete.call(this,t),t=this.toString(),e.set.call(this._usp,t?"?"+t:"")}function i(t,r){n.set.call(this,t,r),t=this.toString(),e.set.call(this._usp,t?"?"+t:"")}return function(e,n){return e.append=t,e.delete=r,e.set=i,p(e,"_usp",{configurable:!0,writable:!0,value:n})}},m=function(e){return function(t,n){return p(t,"_searchParams",{configurable:!0,writable:!0,value:e(n,t)}),n}},g=function(t){var r=t.append;t.append=n.append,e.call(t,t._usp.search.slice(1)),t.append=r},y=function(e,t){if(!(e instanceof t))throw new TypeError("'searchParams' accessed on an object that does not implement interface "+t.name)},b=function(t){var n=t.prototype,r=d(n,"searchParams"),i=d(n,"href"),s=d(n,"search"),o;!r&&s&&s.set&&(o=m(v(s)),Object.defineProperties(n,{href:{get:function(){return i.get.call(this)},set:function(e){var t=this._searchParams;i.set.call(this,e),t&&g(t)}},search:{get:function(){return s.get.call(this)},set:function(e){var t=this._searchParams;s.set.call(this,e),t&&g(t)}},searchParams:{get:function(){return y(this,t),this._searchParams||o(this,new e(this.search.slice(1)))},set:function(e){y(this,t),o(this,e)}}}))};return b(HTMLAnchorElement),/^function|object$/.test(typeof URL)&&URL.prototype&&b(URL),e}();

CROP_THUMBNAILS_VUE.components.cropeditor = {
	template: "<div class=\"cpteditorInner\" v-if=\"cropData && lang\" :class=\"{loading:loading,cropEditorActive:croppingApi!==null}\"><div class=\"cptWaitingWindow\" v-if=\"loading\"><div>{{ lang.waiting }}</div></div><div class=\"mainWindow\"><div class=\"cptSelectionPane\"> <input type=\"checkbox\" :id=\"\'cptSameRatio_\'+_uid\" v-model=\"selectSameRatio\"/> <label :for=\"\'cptSameRatio_\'+_uid\" class=\"cptSameRatioLabel\">{{lang.label_same_ratio}}</label> <button type=\"button\" class=\"button\" @click=\"makeAllInactive()\">{{lang.label_deselect_all}}</button><ul class=\"cptImageSizelist\"><li class=\"wp-core-ui attachment\" v-for=\"i in filteredImageSizes\" :class=\"{details : i.active}\" @click=\"toggleActive(i)\"><div class=\"cptImageSizeInner\"> <strong>{{i.nameLabel}} <span class=\"lowResWarning\">{{lang.lowResWarning}}</span></strong> <span class=\"dimensions\">{{ lang.dimensions }} {{i.width}} {{ lang.pixel }} x {{i.height}} {{ lang.pixel }}</span> <span class=\"ratio\">{{ lang.ratio }} {{i.printRatio}}</span><loadingcontainer :image=\"i.url+\'?cacheBreak=\'+i.cacheBreak\"><div class=\"cptImageBgContainer\" :style=\"{\'background-image\': \'url(\'+i.url+\'?cacheBreak=\'+i.cacheBreak+\')\'}\"></div></loadingcontainer></div></li></ul></div><div class=\"cptCropPane\"><h3>{{ lang.rawImage }}: {{cropData.fullSizeImage.width}} {{ lang.pixel }} x {{cropData.fullSizeImage.height}} {{ lang.pixel }} ({{cropData.fullSizeImage.print_ratio}})</h3><div class=\"cropContainer\"> <img id=\"cpt-croppingImage\" :src=\"cropData.fullSizeImage.url\"/></div> <button type=\"button\" class=\"button cptGenerate\" @click=\"cropThumbnails()\" :disabled=\"!croppingApi\">{{ lang.label_crop }}</button><h4>{{ lang.instructions_header }}</h4><ul class=\"step-info\"><li>{{ lang.instructions_step_1 }}</li><li>{{ lang.instructions_step_2 }}</li><li>{{ lang.instructions_step_3 }}</li></ul></div></div></div>",
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
			axios.get(ajaxurl,{ params : getParameter })
				.then(function(response) {
					that.makeAllInactive(response.data.imageSizes);
					that.addCacheBreak(response.data.imageSizes);
					that.cropData = response.data;
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
					if(i.printRatio == image.printRatio) {
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
			this.deactivateCropArea();
			
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
			this.activeImageSizes.forEach(function(i) {
				if(options.aspectRatio === 0) {
					options.aspectRatio = i.ratio;//initial
				}
				if(options.aspectRatio !== i.ratio) {
					console.info('Crop Thumbnails: print ratio is different from normal ratio on image size "'+i.name+'".');
				}
			});

			//debug
			if(this.cropData.debug_js) {
				console.info('Cropping options',options);
			}
			
			var cropElement = jQuery(this.$el).find('.cropContainer img');
			this.croppingApi = new Cropper(cropElement[0], options);
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
				var rawValues = {
					id : that.cropData.imageObj.ID,
					parentId:that.cropData.imageObj.post_parent,
					width:that.cropData.fullSizeImage.width,
					height:that.cropData.fullSizeImage.height
				};
				
				var params = new URLSearchParams();
				params.append('action', 'cptSaveThumbnail');
				params.append('_ajax_nonce', that.cropData.nonce);
				params.append('cookie', encodeURIComponent(document.cookie));
				params.append('selection', JSON.stringify(selectionData));
				params.append('raw_values', JSON.stringify(rawValues));
				params.append('active_values', JSON.stringify(getDataOfActiveImageSizes()));
				params.append('same_ratio_active', that.selectSameRatio);
				
				axios.post(ajaxurl,params)
					.then(function(response) {
						console.log(response);
						if(that.cropData.debug_js) {
							console.log('Save Function Debug',result.debug);
						}
						if(response.data.error!==undefined) {
							alert(response.data.error);
							that.loading = false;
							return;
						}
						if(response.data.changed_image_format!==undefined && response.data.changed_image_format) {
							that.loadCropData();
							that.loading = false;
							return;
						}
						if(response.data.success!==undefined) {
							that.loading = false;
							that.addCacheBreak(that.activeImageSizes);
							return;
						}
						that.loading = false;
					})
					.catch(function (error) {
						that.loading = false;
						console.error(error);
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
