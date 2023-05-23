<template>
    <div class="CropArea" :class="{ noOptions : !options, largeHandles }" :style="loadingStyle">
        <div class="cptLoadingSpinner" v-if="loading"></div>
        <div class="cptOverlayMessage" v-if="!loading && !options">
            <div>
                <span class="text">{{lang.instructions_overlay_text}}</span>
                <span class="dashicons dashicons-arrow-left-alt"></span>
                <span class="dashicons dashicons-arrow-up-alt"></span>
            </div>
        </div>
        <cropper
            ref="cropper"
            :src="baseImage.url"
            :resizeImage="false"
            :stencil-props="stencilProps"
            @change="change"
            @ready="imageLoaded"
        ></cropper>
        <div>
            <button v-if="options" type="button" @click="moveY(-10)">-- Y</button>
            <button v-if="options" type="button" @click="moveY(-1)">- Y</button>
            <button v-if="options" type="button" @click="moveY(1)">+ Y</button>
            <button v-if="options" type="button" @click="moveY(10)">++ Y</button>
        </div>
        <div>
            <button v-if="options" type="button" @click="moveX(-10)">-- X</button>
            <button v-if="options" type="button" @click="moveX(-1)">- X</button>
            <button v-if="options" type="button" @click="moveX(1)">+ X</button>
            <button v-if="options" type="button" @click="moveX(10)">++ X</button>
        </div>
    </div>
</template>

<script>
/**
 * @see https://advanced-cropper.github.io/vue-advanced-cropper/guides/advanced-recipes.html
 */
import { Cropper } from 'vue-advanced-cropper';
import 'vue-advanced-cropper/dist/style.css';
export default {
    components: { Cropper },
    props: {
        baseImage: { required:true, type: Object },
        lang: { required:true, type: Object },
        options: { required:false, type: Object, default:null },
        largeHandles: { required:false, type: Boolean, default:false },
    },
    emits: [ 'change', 'ready', 'cancel' ],
    mounted() { this.doSetup(); },
    data: () =>({
        loading:true,
        keyListener: null,
    }),
    computed: {
        loadingStyle() {
            if(!this.loading) return null;
            return {
                'padding-top': (this.baseImage.height / this.baseImage.width * 100) + '%'
            };
        },
        stencilProps() {
            if(!this.options) return {};
            setTimeout(() => { this.$refs.cropper.refresh(); },10);
            return { 
                aspectRatio: this.options.aspectRatio,
                handlers: {
                    eastNorth: true,
                    westNorth: true,
                    westSouth: true,
                    eastSouth: true,
                    north: false,
                    south: false,
                    west: false,
                    east: false,
                }
            };
        },
    },
    watch: {
        options: {
            handler(newValue) { this.applyOptions(); },
            deep: true
        }
    },
    methods: {
        moveY(value) {
            const { coordinates, image } = this.$refs.cropper.getResult();
            if(value === -1) {//do small step
                let stepSize = 10;
                coordinates.top = (coordinates.top-stepSize < 0) ? 0 : coordinates.top-stepSize;
            }
            if(value === -10) {//do large step
                let stepSize = 40;
                coordinates.top = (coordinates.top-stepSize < 0) ? 0 : coordinates.top-stepSize;
            }
            if(value === 1) {//do small step
                let stepSize = 10;
                coordinates.top = (coordinates.top+stepSize > image.height) ? image.height : coordinates.top+stepSize;
            }
            if(value === 10) {//do large step
                let stepSize = 40;
                coordinates.top = (coordinates.top+stepSize > image.height) ? image.height : coordinates.top+stepSize;
            }
            this.$refs.cropper.setCoordinates(coordinates);
        },
        moveX(value) {
            const { coordinates, image } = this.$refs.cropper.getResult();
            if(value === -1) {//do small step
                let stepSize = 10;
                coordinates.left = (coordinates.left-stepSize < 0) ? 0 : coordinates.left-stepSize;
            }
            if(value === -10) {//do large step
                let stepSize = 40;
                coordinates.left = (coordinates.left-stepSize < 0) ? 0 : coordinates.left-stepSize;
            }
            if(value === 1) {//do small step
                let stepSize = 10;
                coordinates.left = (coordinates.left+stepSize > image.width) ? image.width : coordinates.left+stepSize;
            }
            if(value === 10) {//do large step
                let stepSize = 40;
                coordinates.left = (coordinates.left+stepSize > image.width) ? image.width : coordinates.left+stepSize;
            }
            this.$refs.cropper.setCoordinates(coordinates);
        },
        imageLoaded() {
            this.$emit('ready');
            this.loading = false;
            this.$refs.cropper.refresh();
        },
        doSetup() {
            window.crop_thumbnails_cropper = this.$refs.cropper;
        },
        change({ coordinates }) {
            this.$emit('change', coordinates);
            //console.log(coordinates, canvas);
        },
        applyOptions() {
            if(!this.options) {
                this.removeKeyboadShortcuts();
            } else {
                this.addKeyboadShortcuts();
                this.$refs.cropper.setCoordinates({
                    width: this.options.trueSize[0], height: this.options.trueSize[1],
                    left: this.options.setSelect[0], top: this.options.setSelect[1],
                });
            }
            setTimeout(() => {
                this.$refs.cropper.refresh();
            },10);
        },
        addKeyboadShortcuts() {
            document.addEventListener('keydown', this.handleKeyboadShortcuts);
        },
        removeKeyboadShortcuts() {
            document.removeEventListener( 'keydown', this.handleKeyboadShortcuts);
        },
        handleKeyboadShortcuts(event) {
            switch(event.key) {
                case 'ArrowUp':
                    // Handle the up key press
                    this.moveY(-1);
                    event.preventDefault();
                    break;
                case 'ArrowDown':
                    // Handle the down key press
                    this.moveY(1);
                    event.preventDefault();
                    break;
                case 'ArrowLeft':
                    // Handle the left key press
                    this.moveX(-1);
                    event.preventDefault();
                    break;
                case 'ArrowRight':
                    this.moveX(1);
                    // Handle the right key press
                    event.preventDefault();
                    break;
                case 'Escape':
                    // Handle the escape key press
                    this.$emit('cancel');
                    event.preventDefault();
                    break;
                default:
                    // Ignore other key presses
                    return;
            }
        },
    },
};
</script>

<style lang="scss">
.CropArea { position: relative;
    .cptLoadingSpinner { position: absolute; z-index: 2; left:calc(50% - 30px/2); top: calc(50% - 30px/2); }
    .cptOverlayMessage { position: absolute; z-index: 2; left:0; top:0; width: 100%; height: 100%; display: flex; justify-content: center; align-items: center;
        &>div { background: rgba(0,0,0,0.8); color:#fff; box-shadow: 0 0 1em rgba(0,0,0,0.8); border-radius:.3em; display: block; padding: 0.5em 1em; font-size: 1.3em; text-align: center; }
        .text { display: block; margin-bottom: .7em;}
        .dashicons.dashicons-arrow-left-alt { display: none; }
        @media(min-width:760px) {
            .dashicons.dashicons-arrow-left-alt { display: inline-block; }
            .dashicons.dashicons-arrow-up-alt { display: none; }
        }
    }


    @import 'vue-advanced-cropper/dist/theme.compact.scss';

    &.noOptions .vue-advanced-cropper__foreground,
    &.noOptions .vue-rectangle-stencil { visibility:hidden; }
    &.noOptions .vue-advanced-cropper::after { content:""; display: block; position: absolute; top:0; left:0; width: 100%; height: 100%; background: rgba(255,255,255,0.3); }

    --handleSize: 25px;
    &.largeHandles {
        --handleSize: 40px;
    }
    .vue-simple-handler-wrapper { height: var(--handleSize); width: var(--handleSize); }
    .vue-simple-handler--west-north,
    .vue-simple-handler--east-south,
    .vue-simple-handler--west-south,
    .vue-simple-handler--east-north { border-color:#539fea; border-width: 4px; height: calc( var(--handleSize) - 10px); width: calc( var(--handleSize) - 10px); }
    .vue-simple-handler--hover { border-color: #f59500; }
    .vue-handler-wrapper--north,
    .vue-handler-wrapper--south,
    .vue-handler-wrapper--east,
    .vue-handler-wrapper--west { visibility: hidden; }

    .line { border: 2px dashed red; }
}
</style>