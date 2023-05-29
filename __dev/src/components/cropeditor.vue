<template>
    <div class="cptEditorInner" v-if="cropData && lang" :class="{ loading }">
        
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
            
            <div class="cptSelectionPane" :class="{ cptImagesAreSelected : (selectedImageSizes.length>0) }">
                <div class="cptSelectionPaneInner">
                    <Message v-if="sourceImageHasOrientation">{{lang.message_image_orientation}}</Message>
                    <div class="cptToolbar">
                        <label class="cptSameRatioMode">
                            {{lang.label_same_ratio_mode}}
                            <select v-model="sameRatioMode" @change="updateRatioMode">
                                <option v-for="option in sameRatioModeOptions" :key="option.value" :value="option.value">{{option.text}}</option>
                            </select>
                        </label>
                        <button type="button" class="button cptDeselectAll" @click="makeAllInactive()">{{lang.label_deselect_all}}</button>
                    </div>


                    <section class="cptImageSizelist" v-if="filteredImageSizes.length>0">
                        <CropImageSize v-for="i in filteredImageSizes" :key="i.nameLabel" @click="toggleActive(i)" :image="i" :lang="lang" :currentCropSize="currentCropSize" :sameRatioMode="sameRatioMode" :notYetCropped="isImageInGroupNotYetCropped(i.printRatio)"></CropImageSize>
                    </section>

                    <div class="cptImageSizelist" v-else>
                        <div class="noImageSizesAvailable">
                            {{lang.infoNoImageSizesAvailable}}
                        </div>
                    </div>
                </div>
            </div>
            <div class="cptCropPane">
                <div class="info">
                    <h3>{{ lang.rawImage }}</h3>
                    <div class="dimensions">{{ lang.dimensions }} {{cropData.sourceImage.full.width}} x {{cropData.sourceImage.full.height}} {{ lang.pixel }}</div>
                    <div class="ratio">{{ lang.ratio }} {{cropData.sourceImage.full.printRatio}}</div>
                </div>
                <button type="button" class="button cptGenerate" :class="{'button-primary':cropLoaded}" @click="cropThumbnails()" :disabled="!cropLoaded">{{ lang.label_crop }}</button>
                
                <div class="cropContainer" v-if="cropImage.url">
                    <CropArea :baseImage="cropImage" :options="cropOptions" :lang="lang" @change="updateCurrentCrop" @ready="cropAreaLoaded" @cancel="makeAllInactive()" :largeHandles="largeHandles"></CropArea>
                </div>
        
                <div class="selectionInfo" v-if="selectedImageSizes.length>0">
                    <h4>{{lang.headline_selected_image_sizes}}</h4>
                    <ul>
                        <li v-for="i in selectedImageSizes" :key="i.nameLabel">
                            <div>
                                <span class="name">{{i.nameLabel}}</span> <span class="dimensions">({{ lang.dimensions }} {{i.width}} x {{i.height}} {{ lang.pixel }})</span>
                            </div>
                            <div class="lowResWarning" v-if="isLowRes(i)">
                                <span class="icon">!</span>
                                <span class="text">{{lang.lowResWarning}}</span>
                            </div>
                            <div class="notYetCropped" v-if="i.notYetCropped">
                                <span class="icon dashicons dashicons-image-crop"></span>
                                <span class="text">{{lang.notYetCropped}}</span>
                            </div>
                        </li>
                    </ul>
                </div>

                <div class="instructionInfo">
                    <h4>{{ lang.instructions_header }}</h4>
                    <ul class="step-info">
                        <li>{{ lang.instructions_step_1 }}</li>
                        <li>{{ lang.instructions_step_2 }}</li>
                        <li>{{ lang.instructions_step_3 }}</li>
                    </ul>
                </div>

                <div class="cpt_checkbox_large_handles_wrapper">
                    <label>
                        <input type="checkbox" v-model="largeHandles" @change="updateHandleSize" />
                        <span>{{ lang.label_large_handles }}</span>
                    </label>
                </div>

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
import CropImageSize from './CropImageSize.vue';
import Message from './Message.vue';
import jQuery from 'jquery';
import CropArea from './CropArea.vue';
import { isLowRes, getCenterPreselect } from './cropCalculations';
export default {
    components: { CropImageSize, Message, CropArea },
    props:{
        imageId : { required: true, type: Number },
        posttype : { required: false, type: String, default: null },
    },
    data:() =>({
        cropData : null,//
        loading : false,//will be true as long as the crop-request is running
        cropLoaded : false,//the object of the crop-library
        currentCropSize : null,//the size of the crop region (needed for lowResWarning)
        lang : null,//language-variable (filled after initial request)
        nonce : null,//the nonce for the crop-request
        showDebugType : null,//the type of the debug to show: null-> no debug open, 'js' -> show jsDebug, 'data' -> show dataDebug
        dataDebug : null,//will be filled after the crop request finished
        
        sameRatioMode : null,// can be NULL, "select" or "group"
        sameRatioModeOptions: [],

        cropOptions: null,
        largeHandles: false,
    }),
    mounted() {
        this.loadCropData();
    },
    computed:{
        /**
         * the image to apply the crop on usually this is the "full" image-size. Sometimes the "large" image size is sufficiant.
         */
        cropImage() {
            if(!this.cropData) return null;
            return this.cropData.sourceImage.full;
        },
        filteredImageSizes() {
            let result = this.cropData.imageSizes;
            if(this.sameRatioMode==='group') {
                let remember = [];
                result = result.filter(elem => {
                    let existingPrintRatioIndex = remember.indexOf(elem.printRatio);
                    if(existingPrintRatioIndex>-1) {
                        //notYetCropped is true if in one of the group-entries notYetCropped is true
                        //result[existingPrintRatioIndex].notYetCropped = result[existingPrintRatioIndex].notYetCropped || elem.notYetCropped;
                        return false;
                    }
                    remember.push(elem.printRatio);
                    return true;
                });
            }
            return result;
        },
        selectedImageSizes() {
            if(!this.cropData) return [];
            return this.cropData.imageSizes.filter(elem => elem.active );
        },
        selectedImageSizesData() {
            return this.selectedImageSizes.map(i => ({
                    name: i.name,
                    width: i.width,
                    height: i.height,
                    ratio: i.ratio,
                    crop: i.crop
                })
            );
        },
        sourceImageHasOrientation() {
            try {
                return (typeof this.cropData.sourceImageMeta.orientation === 'string'
                        && this.cropData.sourceImageMeta.orientation !== '1'
                        && this.cropData.sourceImageMeta.orientation !== '0');
            } catch(e) {}
            return false;
        }
    },
    methods:{
        cropAreaLoaded() {
            this.cropLoaded = true;
        },
        updateCurrentCrop(data) {
            
            this.currentCropSize = { 
                width: data.width, 
                height: data.height,
                left: data.left,
                top: data.top,
            };
        },
        isImageInGroupNotYetCropped(printRatio) {
            return this.cropData.imageSizes.filter(elem => elem.printRatio===printRatio && elem.notYetCropped).length>0;
        },
        setupRatioMode() {
            this.sameRatioModeOptions = [
                { value: null, text: this.lang.label_same_ratio_mode_nothing },
                { value: 'select', text: this.lang.label_same_ratio_mode_select },
                { value: 'group', text: this.lang.label_same_ratio_mode_group },
            ];
            try { this.sameRatioMode = localStorage.getItem('cpt_same_ratio_mode'); } catch(e) {}
        },
        updateRatioMode() {
            try { localStorage.setItem('cpt_same_ratio_mode', this.sameRatioMode); } catch(e) {}
        },
        setupHandleSize() {
            try {
                this.largeHandles = localStorage.getItem('cpt_large_handles');
                if(this.largeHandles===null || this.largeHandles==="false") this.largeHandles = false;
                if(this.largeHandles==="true") this.largeHandles = true;
            } catch(e) {}
        },
        updateHandleSize() {
            try { localStorage.setItem('cpt_large_handles', this.largeHandles); } catch(e) {}
        },
        loadCropData() {
            var getParameter = {
                action : 'cpt_cropdata',
                imageId : this.imageId,
                posttype : this.posttype
            };
            this.loading = true;
            jQuery.get(ajaxurl, getParameter, (responseData) => {
                this.makeAllInactive(responseData.imageSizes);
                this.addCacheBreak(responseData.imageSizes);
                this.cropData = responseData;
                this.lang = this.cropData.lang;
                this.nonce = this.cropData.nonce;
                delete this.cropData.nonce;
            })
            .fail((data) => {
                this.cropData = data.responseJSON;
                this.lang = this.cropData.lang;
                this.nonce = this.cropData.nonce;
                delete this.cropData.nonce;
                if(data.status===403) {
                    this.cropData.noPermission = true;
                }
            })
            .always(() => {
                this.loading = false;
                this.setupRatioMode();
                this.setupHandleSize();
                
                if(this.cropData && this.cropData.imageSizes) {
                    //remove elements with hideByPostType===true
                    this.cropData.imageSizes = this.cropData.imageSizes.filter(elem => !elem.hideByPostType);

                    //apply notYetCropped variable
                    this.cropData.imageSizes.forEach(elem => {
                        elem.notYetCropped = elem.url === this.cropData.sourceImage.full.url;
                    });
                }
            });
        },
        isLowRes(image) {
            return isLowRes(image, this.currentCropSize);
        },
        toggleActive(image) {
            let newValue = !image.active;
            
            if(image.active===false) {
                this.makeAllInactive();
            }

            if(this.sameRatioMode === 'select' || this.sameRatioMode === 'group') {
                //multi select
                this.cropData.imageSizes.forEach(i => {
                    if (i.printRatio === image.printRatio) {
                        i.active = newValue;
                    }
                });
            } else {
                //single select
                image.active = newValue;
            }
            
            if(this.selectedImageSizes.length>0) {
                this.activateCropArea();
            } else {
                this.deactivateCropArea();
            }
        },
        makeAllInactive(imageSizes) {
            if(imageSizes===undefined) {
                imageSizes = this.cropData.imageSizes;
            }
            imageSizes.forEach(i => {
                i.active = false;
                i.lowResWarning = false;
            });
            this.deactivateCropArea();
        },
        addCacheBreak(imageSizes) {
            if(imageSizes===undefined) {
                imageSizes = this.cropData.imageSizes;
            }
            imageSizes.forEach(i => {
                i.cacheBreak = Date.now();
            });
        },
        activateCropArea() {
            this.deactivateCropArea();
            this.cropOptions = this.getCropOptions();
        },
        getCropOptions() {
            let options = {
                trueSize: [ this.cropImage.width , this.cropImage.height ],
                aspectRatio: 0,
                setSelect: [],
            };

            //get the options
            this.selectedImageSizes.forEach(i => {
                if(options.aspectRatio === 0) {
                    options.aspectRatio = i.ratio;//initial
                }
                if(options.aspectRatio !== i.ratio) {
                    console.info('Crop Thumbnails: print ratio is different from normal ratio on image size "'+i.name+'".');
                }
            });
            
            options.setSelect = getCenterPreselect(this.cropImage.width , this.cropImage.height, options.aspectRatio);

            //debug
            if(this.cropData.options.debug_js) {
                console.info('Cropping options',options);
            }
            return options;
        },
        deactivateCropArea() {
            this.currentCropSize = null;
            this.cropOptions = null;
        },
        showDebugClick(type) {
            if(this.showDebugType === type) {
                this.showDebugType = null;
            } else {
                this.showDebugType = type;
            }
        },
        getSelectionForApi() {
            let result = {
                x: Math.floor(this.currentCropSize.left),
                y: Math.floor(this.currentCropSize.top),
                x2: Math.floor(this.currentCropSize.left + this.currentCropSize.width),
                y2: Math.floor(this.currentCropSize.top + this.currentCropSize.height),
                w: Math.floor(this.currentCropSize.width),
                h: Math.floor(this.currentCropSize.height),
            };

            if(result.x < 0) result.x = 0;
            if(result.y < 0) result.y = 0;
            if(this.cropImage) {
                if(result.x2 > this.cropImage.width) result.x2 = this.cropImage.width;
                if(result.y2 > this.cropImage.height) result.y2 = this.cropImage.height;
                if(result.w > this.cropImage.width) result.w = this.cropImage.width;
                if(result.h > this.cropImage.height) result.h = this.cropImage.height;
            }
            return result;
        },
        cropThumbnails() {
            if(!this.loading && this.cropImage) {
                this.loading = true;
                
                const cptRequestParams = {
                    action : 'cptSaveThumbnail',
                    _ajax_nonce : this.nonce,
                    cookie : encodeURIComponent(document.cookie),
                    crop_thumbnails : JSON.stringify({
                        'selection' : this.getSelectionForApi(),
                        'sourceImageId' : this.cropData.sourceImageId,
                        'activeImageSizes' : this.selectedImageSizesData
                    })
                };
                
                jQuery
                    .post(ajaxurl, cptRequestParams, null, 'json')
                    .done((responseData) => {
                        if(this.cropData.options.debug_data) {
                            this.dataDebug = responseData.debug;
                            console.log('Save Function Debug',responseData.debug);
                        }
                        if(responseData.error!==undefined) {
                            alert(responseData.error);
                            return;
                        }
                        if(responseData.success!==undefined) {
                            if(responseData.changedImageName!==undefined) {
                                //update selectedImageSizes with the new URLs
                                this.selectedImageSizes.forEach((value,key) => {
                                    if(responseData.changedImageName[value.name]!==undefined) {
                                        value.url = responseData.changedImageName[value.name];
                                    }
                                });
                            }
                            this.addCacheBreak(this.selectedImageSizes);
                            return;
                        }
                    })
                    .fail((response) => {
                        alert(this.lang.script_connection_error);
                        let debug = {
                            status: response.status,
                            statusText: response.statusText,
                            requestUrl: ajaxurl,
                            requestParams: cptRequestParams
                        };
                        console.error('crop-thumbnails connection error', debug);
                    })
                    .always(() => {
                        this.loading = false;
                    });
            }
        }
    }
}
</script>