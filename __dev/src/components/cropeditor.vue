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
      <div class="msg">{{ lang.cropDisabled }}</div>
    </div>

    <div class="cptWaitingWindow cptNoPermissionMsg" v-if="cropData.noPermission">
      <div class="msg">{{ lang.noPermission }}</div>
    </div>

    <div class="mainWindow" v-if="!cropData.hiddenOnPostType && !cropData.noPermission">

      <div class="cptSelectionPane" :class="{ cptImagesAreSelected : (selectedImageSizes.length>0) }">
        <div class="cptSelectionPaneInner">
          <message v-if="sourceImageHasOrientation">{{ lang.message_image_orientation }}</message>
          <div class="cptToolbar">
            <label class="cptSameRatioMode">
              {{ lang.label_same_ratio_mode }}
              <select v-model="sameRatioMode" @change="updateSameRatioMode">
                <option v-for="option in sameRatioModeOptions" :key="option.value" :value="option.value">
                  {{ option.text }}
                </option>
              </select>
            </label>
            <button type="button" class="button cptDeselectAll" @click="makeAllInactive()">
              {{ lang.label_deselect_all }}
            </button>
          </div>


          <ul class="cptImageSizelist" v-if="filteredImageSizes.length>0">
            <li v-for="i in filteredImageSizes" :key="i.nameLabel" :class="imageSizeClass(i)" @click="toggleActive(i)">
              <section class="cptImageSizeInner">
                <template v-if="sameRatioMode!=='group'">
                  <header>{{ i.nameLabel }}</header>
                  <div class="lowResWarning" v-if="isLowRes(i)" :title="lang.lowResWarning"><span>!</span></div>
                  <div class="notYetCropped" v-if="!isLowRes(i) && i.notYetCropped" :title="lang.notYetCropped"><span
                      class="dashicons dashicons-image-crop"></span></div>
                  <div class="dimensions">{{ lang.dimensions }} {{ i.width }} x {{ i.height }} {{ lang.pixel }}</div>
                  <div class="ratio">{{ lang.ratio }} {{ i.printRatio }}</div>
                </template>
                <template v-else>
                  <header>{{ i.printRatio }}</header>
                  <div class="notYetCropped" v-if="isImageInGroupNotYetCropped(i.printRatio)"
                       :title="lang.notYetCropped"><span class="dashicons dashicons-image-crop"></span></div>
                </template>

                <loadingcontainer :image="i.url+'?cacheBreak='+i.cacheBreak">
                  <div class="cptImageBgContainer"
                       :style="{'background-image': 'url('+i.url+'?cacheBreak='+i.cacheBreak+')'}"></div>
                </loadingcontainer>
              </section>
            </li>
          </ul>

          <div class="cptImageSizelist" v-else>
            <div class="noImageSizesAvailable">
              {{ lang.infoNoImageSizesAvailable }}
            </div>
          </div>
        </div>
      </div>
      <div class="cptCropPane">
        <div class="info">
          <h3>{{ lang.rawImage }}</h3>
          <div class="dimensions">{{ lang.dimensions }} {{ cropData.sourceImage.full.width }} x
            {{ cropData.sourceImage.full.height }} {{ lang.pixel }}
          </div>
          <div class="ratio">{{ lang.ratio }} {{ cropData.sourceImage.full.printRatio }}</div>
        </div>
        <button type="button" class="button cptGenerate" :class="{'button-primary':croppingApi}"
                @click="cropThumbnails()" :disabled="!croppingApi">{{ lang.label_crop }}
        </button>

        <div class="cropContainer">
          <img class="cptCroppingImage" ref="cptCroppingImage" :src="cropImage.url"/>
        </div>

        <div class="selectionInfo" v-if="selectedImageSizes.length>0">
          <h4>{{ lang.headline_selected_image_sizes }}</h4>
          <ul>
            <li v-for="i in selectedImageSizes" :key="i.nameLabel">
              <div>
                <span class="name">{{ i.nameLabel }}</span> <span class="dimensions">({{
                  lang.dimensions
                }} {{ i.width }} x {{ i.height }} {{ lang.pixel }})</span>
              </div>
              <div class="lowResWarning" v-if="isLowRes(i)">
                <span class="icon">!</span>
                <span class="text">{{ lang.lowResWarning }}</span>
              </div>
              <div class="notYetCropped" v-if="i.notYetCropped">
                <span class="icon dashicons dashicons-image-crop"></span>
                <span class="text">{{ lang.notYetCropped }}</span>
              </div>
            </li>
          </ul>
          <hr/>
        </div>

        <div v-if="!loading && croppingApi!==null">
          <div><label>Oben Links:
            <input :style="{maxWidth: 100 + 'px'}" name="x" type="number"
                   :value="Math.round(croppingApi.tellSelect().x)" @blur="setValue" @keyup.enter="setValue"></label>
            <input :style="{maxWidth: 100 + 'px'}" name="y" type="number"
                   :value="Math.round(croppingApi.tellSelect().y)" @blur="setValue" @keyup.enter="setValue">
          </div>
        </div>


        <div class="instructionInfo">
          <h4>{{ lang.instructions_header }}</h4>
          <ul class="step-info">
            <li>{{ lang.instructions_step_1 }}</li>
            <li>{{ lang.instructions_step_2 }}</li>
            <li>{{ lang.instructions_step_3 }}</li>
          </ul>
        </div>

        <div>
          <button type="button" class="button" v-if="cropData.options.debug_js" @click="showDebugClick('js')">show
            JS-Debug
          </button>
          <button type="button" class="button" v-if="cropData.options.debug_data && dataDebug!==null"
                  @click="showDebugClick('data')">show Data-Debug
          </button>
          <pre v-if="showDebugType==='data'">{{ dataDebug }}</pre>
          <pre v-if="showDebugType==='js'"><br/>cropImage:{{ cropImage }}<br/>cropData:{{ cropData }}</pre>
        </div>

      </div>
    </div>
  </div>

</template>

<script>
import loadingcontainer from './loadingcontainer.vue';
import message from './message.vue';

export default {
  components: {loadingcontainer, message},
  props: {
    imageId: {required: true, type: Number},
    posttype: {required: false, type: String, default: null},
  },
  data: () => ({
    cropData: null,//
    loading: false,//will be true as long as the crop-request is running
    croppingApi: null,//the object of the crop-library
    currentCropSize: null,//the size of the cropp region (needed for lowResWarning)
    lang: null,//language-variable (filled after initial request)
    nonce: null,//the nonce for the crop-request
    showDebugType: null,//the type of the debug to show: null-> no debug open, 'js' -> show jsDebug, 'data' -> show dataDebug
    dataDebug: null,//will be filled after the crop request finished

    sameRatioMode: null,// can be NULL, "select" or "group"
    sameRatioModeOptions: []
  }),
  mounted() {
    this.loadCropData();
  },
  computed: {
    cropImage() {
      if (this.cropData !== undefined) {
        var result = this.cropData.sourceImage.full;
        var targetRatio = Math.round(result.ratio * 10);
        if (this.cropData.sourceImage.large !== null
            && this.cropData.sourceImage.large.width > 745
            && targetRatio === Math.round(this.cropData.sourceImage.large.ratio * 10)
            && this.cropData.sourceImage.full.url !== this.cropData.sourceImage.large.url
        ) {
          result = this.cropData.sourceImage.large;
        }
        if (this.cropData.sourceImage.medium_large !== null
            && this.cropData.sourceImage.medium_large.width > 745
            && targetRatio === Math.round(this.cropData.sourceImage.medium_large.ratio * 10)
            && this.cropData.sourceImage.full.url !== this.cropData.sourceImage.medium_large.url
        ) {
          result = this.cropData.sourceImage.medium_large;
        }
        return result;
      }
    },
    filteredImageSizes() {
      //let result = JSON.parse(JSON.stringify(this.cropData.imageSizes));
      let result = this.cropData.imageSizes;

      if (this.sameRatioMode === 'group') {
        let remember = [];
        result = result.filter(elem => {
          let existingPrintRatioIndex = remember.indexOf(elem.printRatio);
          if (existingPrintRatioIndex > -1) {
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
      return this.cropData.imageSizes.filter(elem => elem.active);
    },
    sourceImageHasOrientation() {
      try {
        if (typeof this.cropData.sourceImageMeta.orientation === 'string' && this.cropData.sourceImageMeta.orientation !== '1' && this.cropData.sourceImageMeta.orientation !== '0') {
          return true;
        }
      } catch (e) {
      }
      return false;
    }
  },
  methods: {
    setValue({target}) {
      let values = this.croppingApi.tellSelect()
      values[target.name] = parseInt(target.value)
      this.croppingApi.setSelect([values.x, values.y, values.x2, values.y2])
    },
    isImageInGroupNotYetCropped(printRatio) {
      return this.cropData.imageSizes.filter(elem => elem.printRatio === printRatio && elem.notYetCropped).length > 0;
    },
    setupRatioMode() {
      this.sameRatioModeOptions = [
        {value: null, text: this.lang.label_same_ratio_mode_nothing},
        {value: 'select', text: this.lang.label_same_ratio_mode_select},
        {value: 'group', text: this.lang.label_same_ratio_mode_group},
      ];

      try {
        this.sameRatioMode = localStorage.getItem('cpt_same_ratio_mode');
      } catch (e) {
      }
    },
    updateSameRatioMode() {
      try {
        localStorage.setItem('cpt_same_ratio_mode', this.sameRatioMode);
      } catch (e) {
      }
    },
    imageSizeClass(imageSize) {
      var baseClass = {active: imageSize.active};
      baseClass['cptImageSize-' + imageSize.nameLabel] = true;//add image-size to the class
      return baseClass;
    },
    loadCropData() {
      let that = this;
      var getParameter = {
        action: 'cpt_cropdata',
        imageId: this.imageId,
        posttype: this.posttype
      };
      that.loading = true;
      jQuery.get(ajaxurl, getParameter, (responseData) => {
        that.makeAllInactive(responseData.imageSizes);
        that.addCacheBreak(responseData.imageSizes);
        that.cropData = responseData;
        that.lang = that.cropData.lang;
        that.nonce = that.cropData.nonce;
        delete that.cropData.nonce;
      }).fail((data) => {
        that.cropData = data.responseJSON;
        that.lang = that.cropData.lang;
        that.nonce = that.cropData.nonce;
        delete that.cropData.nonce;
        if (data.status === 403) {
          that.cropData.noPermission = true;
        }
      }).always(() => {
        that.loading = false;
        that.setupRatioMode();

        if (that.cropData && that.cropData.imageSizes) {
          //remove elements with hideByPostType===true
          that.cropData.imageSizes = that.cropData.imageSizes.filter(elem => !elem.hideByPostType);

          //apply notYetCropped variable
          that.cropData.imageSizes.forEach(elem => {
            elem.notYetCropped = elem.url === that.cropData.sourceImage.full.url;
          });
        }
      });
    },
    isLowRes(image) {
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
    toggleActive(image) {
      let newValue = !image.active;

      if (image.active === false) {
        this.makeAllInactive();
      }

      if (this.sameRatioMode === 'select' || this.sameRatioMode === 'group') {
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

      if (this.selectedImageSizes.length > 0) {
        this.activateCropArea();
      } else {
        this.deactivateCropArea();
      }
    },
    makeAllInactive(imageSizes) {
      if (imageSizes === undefined) {
        imageSizes = this.cropData.imageSizes;
      }
      imageSizes.forEach(i => {
        i.active = false;
        i.lowResWarning = false;
      });
      this.deactivateCropArea();
    },
    addCacheBreak(imageSizes) {
      if (imageSizes === undefined) {
        imageSizes = this.cropData.imageSizes;
      }
      imageSizes.forEach(i => {
        i.cacheBreak = Date.now();
      });
    },
    updateCurrentCrop() {
      let result = null;
      if (this.croppingApi !== null) {
        let size = this.croppingApi.tellSelect();
        result = {
          width: Math.round(size.w),
          height: Math.round(size.h)
        };
      }
      this.currentCropSize = result;
    },
    getPreselect(width, height, targetRatio) {
      let x0 = 0;
      let y0 = 0;
      let x1 = width;
      let y1 = height;
      let sourceRatio = width / height;

      if (sourceRatio <= targetRatio) {
        y0 = (height / 2) - ((width / targetRatio) / 2);
        y1 = height - y0;
      } else {
        x0 = (width / 2) - ((height * targetRatio) / 2);
        x1 = width - x0;
      }
      return [x0, y0, x1, y1];
    },
    activateCropArea() {
      let that = this;
      that.deactivateCropArea();

      let options = {
        trueSize: [that.cropData.sourceImage.full.width, that.cropData.sourceImage.full.height],
        aspectRatio: 0,
        setSelect: [],
        onSelect: that.updateCurrentCrop
      };

      //get the options
      that.selectedImageSizes.forEach(i => {
        if (options.aspectRatio === 0) {
          options.aspectRatio = i.ratio;//initial
        }
        if (options.aspectRatio !== i.ratio) {
          console.info('Crop Thumbnails: print ratio is different from normal ratio on image size "' + i.name + '".');
        }
      });

      options.setSelect = this.getPreselect(that.cropData.sourceImage.full.width, that.cropData.sourceImage.full.height, options.aspectRatio);
      options.minSize = [828,600];

      //debug
      if (that.cropData.options.debug_js) {
        console.info('Cropping options', options);
      }

      jQuery(this.$refs.cptCroppingImage).Jcrop(options, function () {
        that.croppingApi = this;
        that.updateCurrentCrop();
      });
    },
    deactivateCropArea() {
      if (this.croppingApi !== null) {
        this.croppingApi.destroy();
        this.croppingApi = null;
        this.currentCropSize = null;
      }
    },
    showDebugClick(type) {
      if (this.showDebugType === type) {
        this.showDebugType = null;
      } else {
        this.showDebugType = type;
      }
    },
    cropThumbnails() {
      let that = this;

      function getDataOfSelectedImageSizes() {
        let result = [];
        that.selectedImageSizes.forEach((i) => {
          if (i.active) {
            result.push({
              name: i.name,
              width: i.width,
              height: i.height,
              ratio: i.ratio,
              crop: i.crop
            });
          }
        });
        return result;
      }

      if (!that.loading && that.croppingApi !== null) {
        that.loading = true;

        const cptRequestParams = {
          action: 'cptSaveThumbnail',
          _ajax_nonce: that.nonce,
          cookie: encodeURIComponent(document.cookie),
          crop_thumbnails: JSON.stringify({
            'selection': that.croppingApi.tellSelect(),
            'sourceImageId': that.cropData.sourceImageId,
            'activeImageSizes': getDataOfSelectedImageSizes()
          })
        };

        jQuery
            .post(ajaxurl, cptRequestParams, null, 'json')
            .done((responseData) => {
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
                  //update selectedImageSizes with the new URLs
                  that.selectedImageSizes.forEach((value, key) => {
                    if (responseData.changedImageName[value.name] !== undefined) {
                      value.url = responseData.changedImageName[value.name];
                    }
                  });
                }
                that.addCacheBreak(that.selectedImageSizes);
                return;
              }
            })
            .fail((response) => {
              alert(that.lang.script_connection_error);
              let debug = {
                status: response.status,
                statusText: response.statusText,
                requestUrl: ajaxurl,
                requestParams: cptRequestParams
              };
              console.error('crop-thumbnails connection error', debug);
            })
            .always(() => {
              that.loading = false;
            });
      }
    }
  }
}
</script>