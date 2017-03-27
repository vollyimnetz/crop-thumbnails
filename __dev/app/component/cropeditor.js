CROP_THUMBNAILS_VUE.components.cropeditor = {
	template: '@./cropeditor.tpl.html',
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
			data.imageSizes.forEach(function(i) {
				i.dynamic = false;
				if(i.width===0 || i.height===0) {
					i.printRatio+= ' dynamic';
					i.dynamic = true;
				}
			});
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
