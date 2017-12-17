/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, {
/******/ 				configurable: false,
/******/ 				enumerable: true,
/******/ 				get: getter
/******/ 			});
/******/ 		}
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "";
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = 0);
/******/ })
/************************************************************************/
/******/ ([
/* 0 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
Object.defineProperty(__webpack_exports__, "__esModule", { value: true });
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_0__style_style_less__ = __webpack_require__(1);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_0__style_style_less___default = __webpack_require__.n(__WEBPACK_IMPORTED_MODULE_0__style_style_less__);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_1__app__ = __webpack_require__(2);

 //do import javascript app (app/index.js)

/***/ }),
/* 1 */
/***/ (function(module, exports) {

// removed by extract-text-webpack-plugin

/***/ }),
/* 2 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_0__polyfills__ = __webpack_require__(3);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_1__global_setup__ = __webpack_require__(7);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_1__global_setup___default = __webpack_require__.n(__WEBPACK_IMPORTED_MODULE_1__global_setup__);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_2__global_cachebreak__ = __webpack_require__(9);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_2__global_cachebreak___default = __webpack_require__.n(__WEBPACK_IMPORTED_MODULE_2__global_cachebreak__);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_3__clickhandler__ = __webpack_require__(10);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_3__clickhandler___default = __webpack_require__.n(__WEBPACK_IMPORTED_MODULE_3__clickhandler__);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_4__modal__ = __webpack_require__(11);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_4__modal___default = __webpack_require__.n(__WEBPACK_IMPORTED_MODULE_4__modal__);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_5__message__ = __webpack_require__(12);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_5__message___default = __webpack_require__.n(__WEBPACK_IMPORTED_MODULE_5__message__);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_6__loadingcontainer__ = __webpack_require__(13);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_6__loadingcontainer___default = __webpack_require__.n(__WEBPACK_IMPORTED_MODULE_6__loadingcontainer__);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_7__cropeditor__ = __webpack_require__(15);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_7__cropeditor___default = __webpack_require__.n(__WEBPACK_IMPORTED_MODULE_7__cropeditor__);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_8__settingsscreen__ = __webpack_require__(17);












/***/ }),
/* 3 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_0__array_filter__ = __webpack_require__(4);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_0__array_filter___default = __webpack_require__.n(__WEBPACK_IMPORTED_MODULE_0__array_filter__);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_1__array_find__ = __webpack_require__(5);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_1__array_find___default = __webpack_require__.n(__WEBPACK_IMPORTED_MODULE_1__array_find__);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_2__cpt_wait_for_final_event__ = __webpack_require__(6);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_2__cpt_wait_for_final_event___default = __webpack_require__.n(__WEBPACK_IMPORTED_MODULE_2__cpt_wait_for_final_event__);




/***/ }),
/* 4 */
/***/ (function(module, exports) {

if (!Array.prototype.filter) {
	Array.prototype.filter = function (fun /*, thisArg*/) {
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

/***/ }),
/* 5 */
/***/ (function(module, exports) {

if (!Array.prototype.find) {
	Array.prototype.find = function (predicate) {
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

/***/ }),
/* 6 */
/***/ (function(module, exports) {

/**
 * Waiting x milliseconds for a final event than call the callback.
 * @see http://stackoverflow.com/a/4541963
 */
var CPT_waitForFinalEvent = function () {
	var timers = {};
	return function (callback, ms, uniqueId) {
		if (!uniqueId) {
			uniqueId = "Don't call this twice without a uniqueId";
		}
		if (timers[uniqueId]) {
			clearTimeout(timers[uniqueId]);
		}
		timers[uniqueId] = setTimeout(callback, ms);
	};
}();

/** USAGE ******************
$(window).resize(function () {
	CPT_waitForFinalEvent(function(){
		alert('Resize...');
	}, 500, "some unique string");
});
***************************/

/***/ }),
/* 7 */
/***/ (function(module, exports, __webpack_require__) {

/* WEBPACK VAR INJECTION */(function(global) {global.CROP_THUMBNAILS_VUE = {
	app: null, //will be initialized in modal/modal.js
	components: {}
};
/* WEBPACK VAR INJECTION */}.call(exports, __webpack_require__(8)))

/***/ }),
/* 8 */
/***/ (function(module, exports) {

var g;

// This works in non-strict mode
g = (function() {
	return this;
})();

try {
	// This works if eval is allowed (see CSP)
	g = g || Function("return this")() || (1,eval)("this");
} catch(e) {
	// This works if the window reference is available
	if(typeof window === "object")
		g = window;
}

// g can still be undefined, but nothing to do about it...
// We return undefined, instead of nothing here, so it's
// easier to handle this case. if(!global) { ...}

module.exports = g;


/***/ }),
/* 9 */
/***/ (function(module, exports) {


/**
 * Provide a global accessable cache-break-function (only available on backend-pages where crop-thumbnail is active --> post-editor, mediathek)
 * Calling this function will add a timestamp on the provided Image-Element.
 * ATTENTION: using this will also delete all other parameters on the images src-attribute.
 * @param {dom-element / jquery-selection} elem
 */
CROP_THUMBNAILS_DO_CACHE_BREAK = function (elem) {
	var $ = jQuery;
	var images = $(elem);
	for (var i = 0; i < images.length; i++) {
		var img = $(images[i]); //select image
		var imageUrl = img.attr('src');
		var imageUrlArray = imageUrl.split("?");

		img.attr('src', imageUrlArray[0] + '?&cacheBreak=' + new Date().getTime());
	}
};

/***/ }),
/* 10 */
/***/ (function(module, exports) {

/**
 * Create Listener for click-events with element-class ".cropThumbnailsLink".
 * Open the modal box.
 */
jQuery(document).ready(function ($) {
	$(document).on('click', '.cropThumbnailsLink', function (e) {
		e.preventDefault();

		//get the data from the link
		var data = $(this).data('cropthumbnail');

		var title = $(this).attr('title');
		var posttype = null;
		if (data.posttype !== undefined) {
			posttype = data.posttype;
		}

		var modal = new CROP_THUMBNAILS_VUE.modal();
		modal.open(data.image_id, posttype, title);
	});
});

/***/ }),
/* 11 */
/***/ (function(module, exports) {

CROP_THUMBNAILS_VUE.modal = function () {
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
	that.close = function (event) {
		CROP_THUMBNAILS_VUE.app.$destroy();
		CROP_THUMBNAILS_VUE.app = null;
		removeModal();
		$('body').trigger('cropThumbnailModalClosed');
		document.removeEventListener('keydown', that.closeByEscKey, true);
	};

	/**
  * Should be called when the background is clicked
  * @var Event
  */
	that.closeByBackground = function (event) {
		if (event.target == document.getElementById('cpt_Modal')) {
			that.close(event);
		}
	};

	that.closeByEscKey = function (event) {
		if (!event.keyCode || event.keyCode === 27) {
			event.stopPropagation();
			that.close(event);
		}
	};

	that.open = function (imageId, posttype, title) {

		var id = imageId;
		var modalHtml = '';
		modalHtml += '<div id="cpt_Modal" class="cpt_Modal">';
		modalHtml += '<div class="cpt_ModalDialog" role="dialog" aria-label="' + $('<div>').text(title).html() + '">';
		modalHtml += '<button type="button" class="cpt_ModalClose" aria-label="close">&times;</button>';
		modalHtml += '<div class="cpt_ModalHeader"><div class="cpt_ModalTitle">' + title + '</div></div>';

		modalHtml += '<div class="cpt_ModalContent" id="cpt_crop_editor">';
		modalHtml += '<cropeditor image-id="' + id + '"';
		if (typeof posttype === 'string') {
			modalHtml += ' posttype="' + posttype + '"';
		}
		modalHtml += '></cropeditor>';
		modalHtml += '</div>'; //end cpt_ModalContent
		modalHtml += '</div>'; //end cpt_ModalDialog
		modalHtml += '</div>'; //end cpt_Modal;


		$('body').prepend(modalHtml).addClass('cpt_ModalIsOpen');
		$('#cpt_Modal .cpt_ModalClose').click(that.close);
		$('#cpt_Modal').on('touchstart mousedown', that.closeByBackground);
		document.addEventListener('keydown', that.closeByEscKey, true);

		CROP_THUMBNAILS_VUE.app = new Vue({
			el: '#cpt_crop_editor',
			mounted: function () {
				console.log('cpt_crop_editor mounted');
			},
			components: CROP_THUMBNAILS_VUE.components
		});
	};
};

/***/ }),
/* 12 */
/***/ (function(module, exports) {

CROP_THUMBNAILS_VUE.components.message = {
	template: '@./message.tpl.html',
	props: {},
	data: function () {
		return {
			closed: false
		};
	},
	methods: {
		close: function () {
			this.closed = true;
		}
	}
};

/***/ }),
/* 13 */
/***/ (function(module, exports, __webpack_require__) {

CROP_THUMBNAILS_VUE.components.loadingcontainer = {
	template: __webpack_require__(14),
	props: {
		image: {
			required: true,
			type: String
		}
	},
	data: function () {
		return {
			status: null
		};
	},
	watch: {
		image: function () {
			this.setup();
		}
	},
	mounted: function () {
		this.setup();
	},
	methods: {
		setup: function () {
			var that = this;
			that.setStart();
			setTimeout(function () {
				var imgLoad = imagesLoaded(that.$el);
				imgLoad.once('done', function () {
					if (that.status !== 'failed') {
						that.setComplete();
					}
				}).once('fail', function () {
					that.setFailed();
				});
			}, 300);
		},
		setComplete: function () {
			this.status = 'completed';
		},
		setStart: function () {
			this.status = 'loading';
		},
		setFailed: function () {
			this.status = 'failed';
		}
	}
};

/***/ }),
/* 14 */
/***/ (function(module, exports) {

module.exports = "<div class=\"loadingcontainer\" :class=\"status\">\n\t<img :src=\"image\" style=\"display:none;\" />\n\t<slot></slot>\n\t\n\t<transition name=\"fade\">\n\t\t<div class=\"loadingMsg\" v-if=\"status==='loading'\">\n\t\t\t<div class=\"cptLoadingSpinner\"></div>\n\t\t</div>\n\t</transition>\n</div>\n"

/***/ }),
/* 15 */
/***/ (function(module, exports, __webpack_require__) {

CROP_THUMBNAILS_VUE.components.cropeditor = {
	template: __webpack_require__(16),
	props: {
		imageId: {
			required: true,
			type: Number
		},
		posttype: {
			required: false,
			type: String,
			default: null
		}
	},
	components: {
		loadingcontainer: CROP_THUMBNAILS_VUE.components.loadingcontainer,
		message: CROP_THUMBNAILS_VUE.components.message
	},
	data: function () {
		return {
			cropData: null, //
			loading: false, //will be true as long as the crop-request is running
			selectSameRatio: true, //boolean value if same ratio image-sizes should be selected at once
			croppingApi: null, //the object of the crop-library
			currentCropSize: null, //the size of the cropp region (needed for lowResWarning)
			lang: null, //language-variable (filled after initial request)
			nonce: null, //the nonce for the crop-request
			showDebugType: null, //the type of the debug to show: null-> no debug open, 'js' -> show jsDebug, 'data' -> show dataDebug
			dataDebug: null //will be filled after the crop request finished
		};
	},
	mounted: function () {
		this.loadCropData();
	},
	computed: {
		cropImage: function () {
			if (this.cropData !== undefined) {
				var result = this.cropData.sourceImage.full;
				var targetRatio = Math.round(result.ratio * 10);
				if (this.cropData.sourceImage.large !== null && this.cropData.sourceImage.large.width > 745 && targetRatio === Math.round(this.cropData.sourceImage.large.ratio * 10) && this.cropData.sourceImage.full.url !== this.cropData.sourceImage.large.url) {
					result = this.cropData.sourceImage.large;
				}
				if (this.cropData.sourceImage.medium_large !== null && this.cropData.sourceImage.medium_large.width > 745 && targetRatio === Math.round(this.cropData.sourceImage.medium_large.ratio * 10) && this.cropData.sourceImage.full.url !== this.cropData.sourceImage.medium_large.url) {
					result = this.cropData.sourceImage.medium_large;
				}
				return result;
			}
		},
		filteredImageSizes: function () {
			return this.cropData.imageSizes.filter(function (elem) {
				return !elem.hideByPostType;
			});
		},
		activeImageSizes: function () {
			return this.cropData.imageSizes.filter(function (elem) {
				return elem.active;
			});
		},
		sourceImageHasOrientation: function () {
			try {
				if (typeof this.cropData.sourceImageMeta.orientation === 'string' && this.cropData.sourceImageMeta.orientation !== '1' && this.cropData.sourceImageMeta.orientation !== '0') {
					return true;
				}
			} catch (e) {}
			return false;
		}
	},
	methods: {
		loadCropData: function () {
			var that = this;
			var getParameter = {
				action: 'cpt_cropdata',
				imageId: this.imageId,
				posttype: this.posttype
			};
			that.loading = true;
			jQuery.get(ajaxurl, getParameter, function (responseData) {
				that.makeAllInactive(responseData.imageSizes);
				that.addCacheBreak(responseData.imageSizes);
				that.cropData = responseData;
				that.lang = that.cropData.lang;
				that.nonce = that.cropData.nonce;
				delete that.cropData.nonce;
			}).always(function () {
				that.loading = false;
			});
		},
		isLowRes: function (image) {
			if (!image.active || this.currentCropSize === null) {
				return false;
			}
			if (image.width === 0 && this.currentCropSize.height < image.height) {
				return true;
			}
			if (image.height === 0 && this.currentCropSize.width < image.width) {
				return true;
			}
			if (image.height === 9999) {
				if (this.currentCropSize.width < image.width) {
					return true;
				}
				return false;
			}
			if (image.width === 9999) {
				if (this.currentCropSize.height < image.height) {
					return true;
				}
				return false;
			}
			if (this.currentCropSize.width < image.width || this.currentCropSize.height < image.height) {
				return true;
			}
			return false;
		},
		toggleActive: function (image) {
			var newValue = !image.active;

			if (image.active === false) {
				this.makeAllInactive();
			}

			if (this.selectSameRatio) {
				this.cropData.imageSizes.forEach(function (i) {
					if (i.printRatio === image.printRatio && i.hideByPostType === false) {
						i.active = newValue;
					}
				});
			} else {
				image.active = newValue;
			}

			if (this.activeImageSizes.length > 0) {
				this.activateCropArea();
			} else {
				this.deactivateCropArea();
			}
		},
		makeAllInactive: function (imageSizes) {
			if (imageSizes === undefined) {
				imageSizes = this.cropData.imageSizes;
			}
			imageSizes.forEach(function (i) {
				i.active = false;
				i.lowResWarning = false;
			});
			this.deactivateCropArea();
		},
		addCacheBreak: function (imageSizes) {
			if (imageSizes === undefined) {
				imageSizes = this.cropData.imageSizes;
			}
			imageSizes.forEach(function (i) {
				i.cacheBreak = Date.now();
			});
		},
		updateCurrentCrop: function () {
			var result = null;
			if (this.croppingApi !== null) {
				var size = this.croppingApi.tellSelect();
				result = {
					width: Math.round(size.w),
					height: Math.round(size.h)
				};
			}
			this.currentCropSize = result;
		},
		activateCropArea: function () {
			var that = this;
			that.deactivateCropArea();

			function getPreselect(width, height, targetRatio) {
				var x0 = 0;
				var y0 = 0;
				var x1 = width;
				var y1 = height;
				var sourceRatio = width / height;

				if (sourceRatio <= targetRatio) {
					y0 = height / 2 - width / targetRatio / 2;
					y1 = height - y0;
				} else {
					x0 = width / 2 - height * targetRatio / 2;
					x1 = width - x0;
				}
				var result = [x0, y0, x1, y1];
				return result;
			}

			var options = {
				trueSize: [that.cropData.sourceImage.full.width, that.cropData.sourceImage.full.height],
				aspectRatio: 0,
				setSelect: [],
				onSelect: that.updateCurrentCrop
			};

			//get the options
			that.activeImageSizes.forEach(function (i) {
				if (options.aspectRatio === 0) {
					options.aspectRatio = i.ratio; //initial
				}
				if (options.aspectRatio !== i.ratio) {
					console.info('Crop Thumbnails: print ratio is different from normal ratio on image size "' + i.name + '".');
				}
			});

			options.setSelect = getPreselect(that.cropData.sourceImage.full.width, that.cropData.sourceImage.full.height, options.aspectRatio);

			//debug
			if (that.cropData.options.debug_js) {
				console.info('Cropping options', options);
			}

			jQuery(that.$el).find('img.cptCroppingImage').Jcrop(options, function () {
				that.croppingApi = this;
				that.updateCurrentCrop();
			});
		},
		deactivateCropArea: function () {
			if (this.croppingApi !== null) {
				this.croppingApi.destroy();
				this.croppingApi = null;
				this.currentCropSize = null;
			}
		},
		showDebugClick: function (type) {
			if (this.showDebugType === type) {
				this.showDebugType = null;
			} else {
				this.showDebugType = type;
			}
		},
		cropThumbnails: function () {
			var that = this;

			function getDataOfActiveImageSizes() {
				var result = [];
				that.activeImageSizes.forEach(function (i) {
					if (i.active) {
						var tmp = {
							name: i.name,
							width: i.width,
							height: i.height,
							ratio: i.ratio,
							crop: i.crop
						};
						result.push(tmp);
					}
				});
				return result;
			}

			if (!that.loading && that.croppingApi !== null) {
				that.loading = true;

				var cptRequestParams = {
					action: 'cptSaveThumbnail',
					_ajax_nonce: that.nonce,
					cookie: encodeURIComponent(document.cookie),
					crop_thumbnails: JSON.stringify({
						'selection': that.croppingApi.tellSelect(),
						'sourceImageId': that.cropData.sourceImageId,
						'activeImageSizes': getDataOfActiveImageSizes()
					})
				};

				var request = jQuery.post(ajaxurl, cptRequestParams, null, 'json');
				request.done(function (responseData) {
					if (that.cropData.options.debug_data) {
						that.dataDebug = responseData.debug;
						console.log('Save Function Debug', responseData.debug);
					}
					if (responseData.error !== undefined) {
						alert(responseData.error);
						return;
					}
					if (responseData.success !== undefined) {
						if (responseData.changedImageName !== undefined) {
							//update activeImageSizes with the new URLs
							that.activeImageSizes.forEach(function (value, key) {
								if (responseData.changedImageName[value.name] !== undefined) {
									value.url = responseData.changedImageName[value.name];
								}
							});
						}
						that.addCacheBreak(that.activeImageSizes);
						return;
					}
				}).fail(function (response) {
					alert(that.lang.script_connection_error);
					var debug = {
						status: response.status,
						statusText: response.statusText,
						requestUrl: ajaxurl,
						requestParams: cptRequestParams
					};
					console.error('crop-thumbnails connection error', debug);
				}).always(function () {
					that.loading = false;
				});
			}
		}
	}
};

/***/ }),
/* 16 */
/***/ (function(module, exports) {

module.exports = "<div class=\"cptEditorInner\" v-if=\"cropData && lang\" :class=\"{loading:loading,cropEditorActive:croppingApi}\">\n\t\n\t<div class=\"cptWaitingWindow\" v-if=\"loading\">\n\t\t<div class=\"msg\">\n\t\t\t{{ lang.waiting }}\n\t\t\t<div>\n\t\t\t\t<div class=\"cptLoadingSpinner\"></div>\n\t\t\t</div>\n\t\t</div>\n\t</div>\n\t\n\t<div class=\"cptWaitingWindow cptCropDisabledMsg\" v-if=\"cropData.hiddenOnPostType\">\n\t\t<div class=\"msg\">{{lang.cropDisabled}}</div>\n\t</div>\n\t\n\t\n\t<div class=\"mainWindow\" v-if=\"!cropData.hiddenOnPostType\">\n\t\t\n\t\t<div class=\"cptSelectionPane\" :class=\"{ cptImagesAreSelected : (activeImageSizes.length>0) }\">\n\t\t\t<div class=\"cptSelectionPaneInner\">\n\t\t\t\t<message v-if=\"sourceImageHasOrientation\">{{lang.message_image_orientation}}</message>\n\t\t\t\t<p>\n\t\t\t\t\t<label class=\"cptSameRatioLabel\">\n\t\t\t\t\t\t<input type=\"checkbox\" v-model=\"selectSameRatio\" />\n\t\t\t\t\t\t{{lang.label_same_ratio}}\n\t\t\t\t\t</label>\n\t\t\t\t\t<button type=\"button\" class=\"button\" @click=\"makeAllInactive()\">{{lang.label_deselect_all}}</button>\n\t\t\t\t</p>\n\t\t\t\t<ul class=\"cptImageSizelist\">\n\t\t\t\t\t<li v-for=\"i in filteredImageSizes\" :class=\"{active : i.active}\" @click=\"toggleActive(i)\">\n\t\t\t\t\t\t<section class=\"cptImageSizeInner\">\n\t\t\t\t\t\t\t<header>{{i.nameLabel}}</header>\n\t\t\t\t\t\t\t<div class=\"lowResWarning\" v-if=\"isLowRes(i)\" :title=\"lang.lowResWarning\"><span>!</span></div>\n\t\t\t\t\t\t\t<div class=\"notYetCropped\" v-if=\"!isLowRes(i) && i.url === cropData.sourceImage.full.url\" :title=\"lang.notYetCropped\"><span class=\"dashicons dashicons-image-crop\"></span></div>\n\t\t\t\t\t\t\t<div class=\"dimensions\">{{ lang.dimensions }} {{i.width}} x {{i.height}} {{ lang.pixel }}</div>\n\t\t\t\t\t\t\t<div class=\"ratio\">{{ lang.ratio }} {{i.printRatio}}</div>\n\t\t\t\t\t\t\t\n\t\t\t\t\t\t\t<loadingcontainer :image=\"i.url+'?cacheBreak='+i.cacheBreak\">\n\t\t\t\t\t\t\t\t<div class=\"cptImageBgContainer\" :style=\"{'background-image': 'url('+i.url+'?cacheBreak='+i.cacheBreak+')'}\"></div>\n\t\t\t\t\t\t\t</loadingcontainer>\n\t\t\t\t\t\t</section>\n\t\t\t\t\t</li>\n\t\t\t\t</ul>\n\t\t\t</div>\n\t\t</div>\n\t\t<div class=\"cptCropPane\">\n\t\t\t<div class=\"info\">\n\t\t\t\t<h3>{{ lang.rawImage }}</h3>\n\t\t\t\t<div class=\"dimensions\">{{ lang.dimensions }} {{cropData.sourceImage.full.width}} x {{cropData.sourceImage.full.height}} {{ lang.pixel }}</div>\n\t\t\t\t<div class=\"ratio\">{{ lang.ratio }} {{cropData.sourceImage.full.printRatio}}</div>\n\t\t\t</div>\n\t\t\t<button type=\"button\" class=\"button cptGenerate\" :class=\"{'button-primary':croppingApi}\" @click=\"cropThumbnails()\" :disabled=\"!croppingApi\">{{ lang.label_crop }}</button>\n\t\t\t\n\t\t\t<div class=\"cropContainer\">\n\t\t\t\t<img class=\"cptCroppingImage\" :src=\"cropImage.url\" />\n\t\t\t</div>\n\t\n\t\t\t<h4>{{ lang.instructions_header }}</h4>\n\t\t\t<ul class=\"step-info\">\n\t\t\t\t<li>{{ lang.instructions_step_1 }}</li>\n\t\t\t\t<li>{{ lang.instructions_step_2 }}</li>\n\t\t\t\t<li>{{ lang.instructions_step_3 }}</li>\n\t\t\t</ul>\n\n\t\t\t<div>\n\t\t\t\t<button type=\"button\" class=\"button\" v-if=\"cropData.options.debug_js\" @click=\"showDebugClick('js')\">show JS-Debug</button>\n\t\t\t\t<button type=\"button\" class=\"button\" v-if=\"cropData.options.debug_data && dataDebug!==null\" @click=\"showDebugClick('data')\">show Data-Debug</button>\n\t\t\t\t<pre v-if=\"showDebugType==='data'\">{{ dataDebug }}</pre>\n\t\t\t\t<pre v-if=\"showDebugType==='js'\"><br />cropImage:{{cropImage}}<br />cropData:{{ cropData }}</pre>\n\t\t\t</div>\n\t\t</div>\n\t</div>\n</div>\n"

/***/ }),
/* 17 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_0__init_on_settingspage__ = __webpack_require__(18);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_0__init_on_settingspage___default = __webpack_require__.n(__WEBPACK_IMPORTED_MODULE_0__init_on_settingspage__);


CROP_THUMBNAILS_VUE.components['cpt-settingsscreen'] = {
    template: __webpack_require__(19),
    props: {
        settings: {
            required: true
        }
    },
    components: {},
    data: function () {
        return {
            settingsData: JSON.parse(this.settings)
        };
    },
    mounted: function () {},
    computed: {},
    methods: {
        isButtonHiddenOnPostType: function (postType) {
            return this.settingsData.options && this.settingsData.options.hide_post_type && this.settingsData.options.hide_post_type[postType] === "1";
        },
        isImageSizeHidden: function (postType, imageSize) {
            return this.settingsData.options && this.settingsData.options.hide_size && this.settingsData.options.hide_size[postType] && this.settingsData.options.hide_size[postType][imageSize] === "1";
        }
    }
};

/***/ }),
/* 18 */
/***/ (function(module, exports) {

jQuery(document).ready(function ($) {
    if ($('body.settings_page_page-cpt').length > 0) {
        CROP_THUMBNAILS_VUE.app = new Vue({
            el: '#cpt_settings_settingsscreen',
            mounted: function () {},
            components: CROP_THUMBNAILS_VUE.components
        });
    }
});

/***/ }),
/* 19 */
/***/ (function(module, exports) {

module.exports = "<div>\n\n    <div class=\"cptSettingsPostListDescription\">{{settingsData.lang.choose_image_sizes}}</div>\n    <ul class=\"cptSettingsPostList\">\n\n        <li v-for=\"postType in settingsData.post_types\">\n            <section>\n                <header><h3>{{postType.label}}</h3></header>\n\n                \n                <ul class=\"cptImageSizes\">\n                    <li v-for=\"imageSize in settingsData.image_sizes\" v-if=\"imageSize.crop\">\n                        <label>\n                            <input type=\"checkbox\" :value=\"imageSize.id\" :name=\"'crop-post-thumbs[hide_size]['+postType.name+']['+imageSize.id+']'\" :checked=\"isImageSizeHidden(postType.name,imageSize.id)\"/>\n                            <span class=\"name\">{{imageSize.name}}</span>\n                            <span class=\"defaultName\" v-if=\"imageSize.name!==imageSize.id\">({{imageSize.id}})</span>\n                        </label>\n                    </li>\n                </ul>\n                \n                <label>\n                    <input id=\"cpt_settings_post\" type=\"checkbox\" :name=\"'crop-post-thumbs[hide_post_type]['+postType.name+']'\" value=\"1\" :checked=\"isButtonHiddenOnPostType(postType.name)\">\n                     {{settingsData.lang.hide_on_post_type}}\n                </label>\n            </section>\n        </li>\n    </ul>\n    \n</div>"

/***/ })
/******/ ]);