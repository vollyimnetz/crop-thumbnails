<template>
    <div class="cptEditorInner" v-if="cropData" :class="{ loading }">
        <div class="cptWaitingWindow" v-if="loading">
            <div class="msg">
                <div>
                    <div class="cptLoadingSpinner"></div>
                </div>
            </div>
        </div>

        <div class="cptWaitingWindow cptCropDisabledMsg" v-if="cropData.hiddenOnPostType">
            <div class="msg">{{lang.cropDisabled}}</div>
        </div>

        <div class="cptWaitingWindow cptNoPermissionMsg" v-if="!!errorMessage">
            <div class="msg">{{errorMessage}}</div>
        </div>

        <div class="mainWindow" v-if="!cropData.hiddenOnPostType && !errorMessage">

            <div class="cptSelectionPane" :class="{ cptImagesAreSelected : (selectedImageSizes.length>0) }">
                <div class="cptSelectionPaneInner">
                    <Message v-if="sourceImageHasOrientation">{{lang.message_image_orientation}}</Message>
                    <div class="cptToolbar">
                        <label class="cptSameRatioMode" v-if="!options?.same_ratio_mode">
                            {{lang.label_same_ratio_mode}}
                            <select v-model="sameRatioMode" @change="updateRatioMode">
                                <option v-for="option in sameRatioModeOptions" :key="option.value" :value="option.value">{{option.text}}</option>
                            </select>
                        </label>
                        <button type="button" class="button cptDeselectAll" @click="makeAllInactive()">{{lang.label_deselect_all}}</button>
                    </div>

                    <section class="cptImageSizelist" v-if="filteredImageSizes.length>0">
                        <CropImageSize v-for="i in filteredImageSizes" :key="i.nameLabel" @click="toggleActive(i)" :image="i" :lang="lang" :currentCropSize="realCurrentCropSize" :sameRatioMode="sameRatioMode" :notYetCropped="isImageInGroupNotYetCropped(i.printRatio)"></CropImageSize>
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
                    <div class="dimensions">{{ lang.dimensions }} {{ originalImage.width }} x {{ originalImage.height }} {{ lang.pixel }}</div>
                    <div class="ratio">{{ lang.ratio }} {{ originalImage.printRatio }}</div>
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
                    <pre class="cropThumbnailDebug" v-if="showDebugType==='data'">{{ dataDebug }}
                        <button class="copyDebug" @click="copyToClipboard(dataDebug)">Copy</button>
                    </pre>
                    <pre class="cropThumbnailDebug" v-if="showDebugType==='js'">{{ cropData }}
                        <button class="copyDebug" @click="copyToClipboard(cropData)">Copy</button>
                    </pre>
                </div>
            </div>
        </div>
    </div>

</template>

<script>
import CropImageSize from './CropImageSize.vue';
import Message from './Message.vue';
import CropArea from './CropArea.vue';
import { isLowRes, getCenterPreselect } from './cropCalculations';
import { getCropData, saveCrop } from './api';
export default {
    components: { CropImageSize, Message, CropArea },
    props:{
        imageId : { required: true, type: [Number, String] },
        posttype : { required: false, type: String, default: null },
    },
    mounted() {
        this.doSetup();
    },
    data:() =>({
        cropData : null,//
        loading : false,//will be true as long as the crop-request is running
        cropLoaded : false,//the object of the crop-library
        currentCropSize : null,//the size of the crop region (needed for lowResWarning)
        nonce : null,//the nonce for the crop-request
        showDebugType : null,//the type of the debug to show: null-> no debug open, 'js' -> show jsDebug, 'data' -> show dataDebug
        dataDebug : null,//will be filled after the crop request finished

        sameRatioMode : null,// can be NULL, "select" or "group"

        cropOptions: null,
        largeHandles: false,

        errorMessage: false,
        cropBaseSize: null,
    }),
    computed:{
        /**
         * the image to apply the crop on usually this is the "full" image-size. Sometimes the "large" image size is sufficiant.
         */
        cropImage() {
            if(!this.cropData) return null;
            if(!this.cropBaseSize) return null;
            return this.cropData.sourceImage[this.cropBaseSize];
        },
        originalImage() {
            if(!this.cropData) return null;
            return this.cropData.sourceImage.original_image;
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
        },
        hasScaledFullImage() {
            return this.cropData.sourceImage.full.width !== this.cropData.sourceImage.original_image.width;
        },
        isScaledFullImage() {
            return this.hasScaledFullImage && this.cropData.sourceImage.full.width === this.cropImage.width;
        },
        realCurrentCropSize() {
            if(!this.currentCropSize) return null;
            let scale = this.originalImage.width / this.cropImage.width;
            return {
                width: this.currentCropSize.width * scale,
                height: this.currentCropSize.height * scale,
                left: this.currentCropSize.left * scale,
                top: this.currentCropSize.top * scale,
            };
        },
        lang() {
            return this.cropData?.lang ?? null;
        },
        options() {
            return this.cropData?.options ?? null;
        },
        hasSettingsSameRatioMode() {
            return this.options?.same_ratio_mode ?? false;
        },
        sameRatioModeOptions() {
            if(!this.lang) return [];
            return [
                { value: null, text: this.lang.label_same_ratio_mode_nothing },
                { value: 'select', text: this.lang.label_same_ratio_mode_select },
                { value: 'group', text: this.lang.label_same_ratio_mode_group },
            ];
        }
    },
    methods:{
        doSetup() {
            this.loadCropData();
        },
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
            if(this.errorMessage) return;
            try { this.sameRatioMode = localStorage.getItem('cpt_same_ratio_mode'); } catch(e) {}

            if(this.hasSettingsSameRatioMode) {
                this.sameRatioMode = this.options.same_ratio_mode;
            }
        },
        updateRatioMode() {
            try { localStorage.setItem('cpt_same_ratio_mode', this.sameRatioMode); } catch(e) {}
        },
        setupHandleSize() {
            try {
                this.largeHandles = localStorage.getItem('cpt_large_handles');
                if(this.largeHandles===null || this.largeHandles==='false') this.largeHandles = false;
                if(this.largeHandles==="true") this.largeHandles = true;
            } catch(e) {}
        },
        updateHandleSize() {
            try { localStorage.setItem('cpt_large_handles', this.largeHandles); } catch(e) {}
        },
        loadCropData() {
            let params = {
                imageId : this.imageId,
                posttype : this.posttype
            };
            this.loading = true;
            this.errorMessage = false;
            getCropData(params)
                .then((response) => {
                    this.makeAllInactive(response.data.imageSizes);
                    this.addCacheBreak(response.data.imageSizes);
                    this.cropData = response.data;
                    this.cropBaseSize = this.cropData.cropBaseSize;
                    this.nonce = this.cropData.nonce;
                    delete this.cropData.nonce;
                })
                .catch((error) => {
                    this.cropData = error.response.data;
                    this.nonce = this.cropData.nonce;
                    delete this.cropData.nonce;
                    this.errorMessage = 'ERROR';
                    if(error.response.data.lang) this.errorMessage = error.response.data.lang.unknownError;
                    if(error.status===403) {
                        if(error.response.data.message) this.errorMessage = error.response.data.message;
                        if(error.response.data.lang) this.errorMessage = error.response.data.lang.noPermission;
                    }
                    console.error('crop-thumbnails connection error', this.errorMessage, this.cropData);
                })
                .finally(() => {
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
            return isLowRes(image, this.realCurrentCropSize);
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
                x: Math.floor(this.realCurrentCropSize.left),
                y: Math.floor(this.realCurrentCropSize.top),
                x2: Math.floor((this.realCurrentCropSize.left + this.realCurrentCropSize.width)),
                y2: Math.floor((this.realCurrentCropSize.top + this.realCurrentCropSize.height)),
                w: Math.floor(this.realCurrentCropSize.width),
                h: Math.floor(this.realCurrentCropSize.height),
            };

            if(result.x < 0) result.x = 0;
            if(result.y < 0) result.y = 0;
            if(this.originalImage) {
                if(result.x2 > this.originalImage.width) result.x2 = this.originalImage.width;
                if(result.y2 > this.originalImage.height) result.y2 = this.originalImage.height;
                if(result.w > this.originalImage.width) result.w = this.originalImage.width;
                if(result.h > this.originalImage.height) result.h = this.originalImage.height;
            }
            return result;
        },
        cropThumbnails() {
            if(!this.loading && this.cropImage) {
                this.loading = true;

                const cropRequest = {
                    crop_thumbnails: {
                        selection : this.getSelectionForApi(),
                        sourceImageId : this.cropData.sourceImageId,
                        activeImageSizes : this.selectedImageSizesData
                    }
                };
                //console.log('request data', cropRequest);
                saveCrop(cropRequest)
                    .then((response) => {
                        if(this.cropData.options.debug_data) {
                            this.dataDebug = response.data.debug;
                            console.log('Save Function Debug', response.data.debug);
                        }
                        if(response.data.error!==undefined) {
                            alert(response.data.error);
                            return;
                        }
                        if(response.data.success!==undefined) {
                            if(response.data.changedImageName!==undefined) {
                                //update selectedImageSizes with the new URLs
                                this.selectedImageSizes.forEach((value) => {
                                    if(response.data.changedImageName[value.name]!==undefined) {
                                        value.url = response.data.changedImageName[value.name];
                                    }
                                });
                            }
                            this.addCacheBreak(this.selectedImageSizes);
                            return;
                        }
                    })
                    .catch((error) => {
                        alert(this.lang.script_connection_error);
                        let debug = {
                            status: error.response.status,
                            statusText: error.response.statusText,
                            requestUrl: error.config.url,
                            requestParams: error.config.data
                        };
                        console.error('crop-thumbnails connection error', debug);
                    })
                    .finally(() => {
                        this.loading = false;
                    });
            }
        },
        copyToClipboard(text) {
            try {
                if(typeof text === 'object') text = JSON.stringify(text, null, "\t");
                //use the clipboard API
                navigator.clipboard.writeText(text).then(() => {
                    alert('Text copied to clipboard');
                });
            } catch (error) {
                alert('Error while try to copy to clipboard');
            }
        }
    }
}
</script>