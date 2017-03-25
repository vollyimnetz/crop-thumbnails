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
				
				var params = new URLSearchParams();
				params.append('action', 'cptSaveThumbnail');
				params.append('_ajax_nonce', that.cropData.nonce);
				params.append('cookie', encodeURIComponent(document.cookie));
				params.append('crop_thumbnails', JSON.stringify({
						'selection' : selectionData,
						'sourceImageId' : that.cropData.imageObj.ID,
						'activeImageSizes' : getDataOfActiveImageSizes()
					})
				);
				
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
						if(response.data.success!==undefined) {
							if(response.data.changedImageName!==undefined) {
								//update activeImageSizes with the new URLs
								that.activeImageSizes.forEach(function(value,key) {
									if(response.data.changedImageName[value.name]!==undefined) {
										value.url = response.data.changedImageName[value.name];
									}
								});
							}
							that.addCacheBreak(that.activeImageSizes);
							that.loading = false;
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
