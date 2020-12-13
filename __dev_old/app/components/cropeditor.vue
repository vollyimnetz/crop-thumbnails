<template>
    <div class="cptEditorInner" v-if="cropData && lang" :class="{loading:loading,cropEditorActive:croppingApi}">
        
        <div class="cptWaitingWindow" v-if="loading">
            <div class="msg">
                {{ lang.waiting }}
                <div>
                    <div class="cptLoadingSpinner"></div>
                </div>
            </div>
        </div>
        
        <div class="cptWaitingWindow cptCropDisabledMsg" v-if="cropData.hiddenOnPostType">
            <div class="msg">{{lang.cropDisabled}}</div>
        </div>
        
        <div class="cptWaitingWindow cptNoPermissionMsg" v-if="cropData.noPermission">
            <div class="msg">{{lang.noPermission}}</div>
        </div>

        <div class="mainWindow" v-if="!cropData.hiddenOnPostType && !cropData.noPermission">
            
            <div class="cptSelectionPane" :class="{ cptImagesAreSelected : (activeImageSizes.length>0) }">
                <div class="cptSelectionPaneInner">
                    <message v-if="sourceImageHasOrientation">{{lang.message_image_orientation}}</message>
                    <p>
                        <label class="cptSameRatioLabel">
                            <input type="checkbox" v-model="selectSameRatio" />
                            {{lang.label_same_ratio}}
                        </label>
                        <button type="button" class="button" @click="makeAllInactive()">{{lang.label_deselect_all}}</button>
                    </p>
                    <ul class="cptImageSizelist">
                        <li v-for="i in filteredImageSizes" :key="i.nameLabel" :class="imageSizeClass(i)" @click="toggleActive(i)">
                            <section class="cptImageSizeInner">
                                <header>{{i.nameLabel}}</header>
                                <div class="lowResWarning" v-if="isLowRes(i)" :title="lang.lowResWarning"><span>!</span></div>
                                <div class="notYetCropped" v-if="!isLowRes(i) && i.url === cropData.sourceImage.full.url" :title="lang.notYetCropped"><span class="dashicons dashicons-image-crop"></span></div>
                                <div class="dimensions">{{ lang.dimensions }} {{i.width}} x {{i.height}} {{ lang.pixel }}</div>
                                <div class="ratio">{{ lang.ratio }} {{i.printRatio}}</div>
                                
                                <loadingcontainer :image="i.url+'?cacheBreak='+i.cacheBreak">
                                    <div class="cptImageBgContainer" :style="{'background-image': 'url('+i.url+'?cacheBreak='+i.cacheBreak+')'}"></div>
                                </loadingcontainer>
                            </section>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="cptCropPane">
                <div class="info">
                    <h3>{{ lang.rawImage }}</h3>
                    <div class="dimensions">{{ lang.dimensions }} {{cropData.sourceImage.full.width}} x {{cropData.sourceImage.full.height}} {{ lang.pixel }}</div>
                    <div class="ratio">{{ lang.ratio }} {{cropData.sourceImage.full.printRatio}}</div>
                </div>
                <button type="button" class="button cptGenerate" :class="{'button-primary':croppingApi}" @click="cropThumbnails()" :disabled="!croppingApi">{{ lang.label_crop }}</button>
                
                <div class="cropContainer">
                    <img class="cptCroppingImage" :src="cropImage.url" />
                </div>
        
                <h4>{{ lang.instructions_header }}</h4>
                <ul class="step-info">
                    <li>{{ lang.instructions_step_1 }}</li>
                    <li>{{ lang.instructions_step_2 }}</li>
                    <li>{{ lang.instructions_step_3 }}</li>
                </ul>

                <div>
                    <button type="button" class="button" v-if="cropData.options.debug_js" @click="showDebugClick('js')">show JS-Debug</button>
                    <button type="button" class="button" v-if="cropData.options.debug_data && dataDebug!==null" @click="showDebugClick('data')">show Data-Debug</button>
                    <pre v-if="showDebugType==='data'">{{ dataDebug }}</pre>
                    <pre v-if="showDebugType==='js'"><br />cropImage:{{cropImage}}<br />cropData:{{ cropData }}</pre>
                </div>
            </div>
        </div>
    </div>

</template>

<script>
import loadingcontainer from './loadingcontainer.vue';
import message from './message.vue';
export default {
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
		loadingcontainer,
		message
	},
	data:function() {
		return {
			cropData : null,//
			loading : false,//will be true as long as the crop-request is running
			selectSameRatio : true,//boolean value if same ratio image-sizes should be selected at once
			croppingApi : null,//the object of the crop-library
			currentCropSize : null,//the size of the cropp region (needed for lowResWarning)
			lang : null,//language-variable (filled after initial request)
			nonce : null,//the nonce for the crop-request
			showDebugType : null,//the type of the debug to show: null-> no debug open, 'js' -> show jsDebug, 'data' -> show dataDebug
			dataDebug : null//will be filled after the crop request finished
		};
	},
	mounted:function() {
		this.loadCropData();
	},
	computed:{
		cropImage : function() {
			if(this.cropData!==undefined) {
				var result = this.cropData.sourceImage.full;
				var targetRatio = Math.round(result.ratio * 10);
				if(this.cropData.sourceImage.large!==null 
					&& this.cropData.sourceImage.large.width>745 
					&& targetRatio === Math.round(this.cropData.sourceImage.large.ratio * 10)
					&& this.cropData.sourceImage.full.url !== this.cropData.sourceImage.large.url
					) {
					result = this.cropData.sourceImage.large;
				}
				if(this.cropData.sourceImage.medium_large!==null
					&& this.cropData.sourceImage.medium_large.width>745 
					&& targetRatio === Math.round(this.cropData.sourceImage.medium_large.ratio * 10)
					&& this.cropData.sourceImage.full.url !== this.cropData.sourceImage.medium_large.url
					) {
					result = this.cropData.sourceImage.medium_large;
				}
				return result;
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
		},
		sourceImageHasOrientation : function() {
			try {
				if(typeof this.cropData.sourceImageMeta.orientation === 'string' && this.cropData.sourceImageMeta.orientation !== '1' && this.cropData.sourceImageMeta.orientation !== '0') {
					return true;
				}
			} catch(e) {}
			return false;
		}
	},
	methods:{
		imageSizeClass: function (imageSize) {
			var baseClass = { active: imageSize.active };
			baseClass['cptImageSize-' + imageSize.nameLabel] = true;//add image-size to the class
			return baseClass;
		},
		loadCropData : function() {
			var that = this;
			var getParameter = {
				action : 'cpt_cropdata',
				imageId : this.imageId,
				posttype : this.posttype
			};
			that.loading = true;
			jQuery.get(ajaxurl,getParameter,function(responseData) {
				that.makeAllInactive(responseData.imageSizes);
				that.addCacheBreak(responseData.imageSizes);
				that.cropData = responseData;
				that.lang = that.cropData.lang;
				that.nonce = that.cropData.nonce;
				delete that.cropData.nonce;
			}).fail(function(data) {
				that.cropData = data.responseJSON;
				that.lang = that.cropData.lang;
				that.nonce = that.cropData.nonce;
				delete that.cropData.nonce;
				if(data.status===403) {
					that.cropData.noPermission = true;
				}
			}).always(function() {
				that.loading = false;
			});
		},
		isLowRes : function(image) {
			if(!image.active || this.currentCropSize===null) {
				return false;
			}
			if(image.width===0 && this.currentCropSize.height < image.height) {
				return true;
			}
			if(image.height===0 && this.currentCropSize.width < image.width) {
				return true;
			}
			if(image.height===9999) {
				if(this.currentCropSize.width < image.width) {
					return true;
				}
				return false;
			}
			if(image.width===9999) {
				if(this.currentCropSize.height < image.height) {
					return true;
				}
				return false;
			}
			if(this.currentCropSize.width < image.width || this.currentCropSize.height < image.height) {
				return true;
			}
			return false;
		},
		toggleActive : function(image) {
			var newValue = !image.active;
			
			if(image.active===false) {
				this.makeAllInactive();
			}
			
			if(this.selectSameRatio) {
				this.cropData.imageSizes.forEach(function(i) {
					if (i.printRatio === image.printRatio && i.hideByPostType===false) {
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
				i.lowResWarning = false;
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
		updateCurrentCrop : function() {
			var result = null;
			if(this.croppingApi!==null) {
				var size = this.croppingApi.tellSelect();
				result = {
					width : Math.round(size.w),
					height : Math.round(size.h)
				};
			}
			this.currentCropSize = result;
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
				setSelect: [],
				onSelect:that.updateCurrentCrop
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
				that.updateCurrentCrop();
			});
		},
		deactivateCropArea : function() {
			if(this.croppingApi!==null) {
				this.croppingApi.destroy();
				this.croppingApi = null;
				this.currentCropSize = null;
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
				
				var cptRequestParams = {
					action : 'cptSaveThumbnail',
					_ajax_nonce : that.nonce,
					cookie : encodeURIComponent(document.cookie),
					crop_thumbnails : JSON.stringify({
						'selection' : that.croppingApi.tellSelect(),
						'sourceImageId' : that.cropData.sourceImageId,
						'activeImageSizes' : getDataOfActiveImageSizes()
					})
				};
				
				var request = jQuery.post(ajaxurl,cptRequestParams,null,'json');
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
						alert(that.lang.script_connection_error);
						var debug = {
							status: response.status,
							statusText: response.statusText,
							requestUrl: ajaxurl,
							requestParams: cptRequestParams
						};
						console.error('crop-thumbnails connection error', debug);
					})
					.always(function() {
						that.loading = false;
					});
			}
		}
	}
}
</script>