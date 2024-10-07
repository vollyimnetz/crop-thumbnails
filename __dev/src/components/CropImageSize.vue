<template>
    <section class="CropImageSize" :class="cssClass">
        <template v-if="sameRatioMode!=='group'">
            <header>{{image.nameLabel}} asdf</header>
            <div class="lowResWarning" v-if="isLowRes" :title="lang.lowResWarning"><span>!</span></div>
            <div class="notYetCropped" v-if="!isLowRes && image.notYetCropped" :title="lang.notYetCropped"><span class="dashicons dashicons-image-crop"></span></div>
            <div class="dimensions">{{ lang.dimensions }} {{image.width}} x {{image.height}} {{ lang.pixel }}</div>
            <div class="ratio">{{ lang.ratio }} {{image.printRatio}}</div>
        </template>
        <template v-else>
            <header>{{image.printRatio}}</header>
            <div class="notYetCropped" v-if="notYetCropped" :title="lang.notYetCropped"><span class="dashicons dashicons-image-crop"></span></div>
        </template>

        <LoadingContainer :image="image.url+'?cacheBreak='+image.cacheBreak">
            <div class="cptImageBgContainer" :style="{'background-image': 'url('+image.url+'?cacheBreak='+image.cacheBreak+')'}"></div>
        </LoadingContainer>
    </section>
</template>

<script>
import LoadingContainer from './LoadingContainer.vue';
import { isLowRes } from './cropCalculations';
export default {
    components: { LoadingContainer },
    props: {
        image: { required:true, type: Object },
        lang: { required:true, type: Object },
        sameRatioMode: { required:true, type: [Object, String] },
        notYetCropped: { required:true, type: Boolean },
        currentCropSize: { required:true, type: [Object, String] },
    },
    computed:{
        isLowRes() {
            return isLowRes(this.image, this.currentCropSize);
        },
        cssClass() {
            var baseClass = { active: this.image.active };
            baseClass['cptImageSize-' + this.image.nameLabel] = true;//add image-size to the class
            return baseClass;
        },
    },
    methods: {
        isImageInGroupNotYetCropped(printRatio) {
            return this.cropData.imageSizes.filter(elem => elem.printRatio===printRatio && elem.notYetCropped).length>0;
        },
    }
}
</script>