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
        <!--
        <div>
            <button v-if="options" type="button" @click="move(null, -10)">-- Y</button>
            <button v-if="options" type="button" @click="move(null, -1)">- Y</button>
            <button v-if="options" type="button" @click="move(null, 1)">+ Y</button>
            <button v-if="options" type="button" @click="move(null, 10)">++ Y</button>
        </div>
        <div>
            <button v-if="options" type="button" @click="move(-10, null)">-- X</button>
            <button v-if="options" type="button" @click="move(-1, null)">- X</button>
            <button v-if="options" type="button" @click="move(1, null)">+ X</button>
            <button v-if="options" type="button" @click="move(10, null)">++ X</button>
        </div>
        -->
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

        keyPressed_left: false,
        keyPressed_right: false,
        keyPressed_up: false,
        keyPressed_down: false,

        holdTimeoutDuration: 500,
        holdTimeout_left: null,
        holdTimeout_right: null,
        holdTimeout_up: null,
        holdTimeout_down: null,

        repeatInterval_left: null,
        repeatInterval_right: null,
        repeatInterval_up: null,
        repeatInterval_down: null,
        repeatInterval: 50,
    }),
    computed: {
        stepSizeSmall() {
            let result = 15;
            if(this.baseImage && this.baseImage.width && window.crop_thumbnails_cropper.$el) {
                result = Math.ceil( this.baseImage.width / window.crop_thumbnails_cropper.$el.clientWidth );
                result = result*2;
            }
            return result;
        },
        stepSizeLarge() {
            return this.stepSizeSmall*5;
        },
        loadingStyle() {
            if(!this.loading) return null;
            return {
                'padding-top': (this.baseImage.height / this.baseImage.width * 100) + '%'
            };
        },
        stencilProps() {
            if(!this.options) return {};
            setTimeout(() => { this.$refs.cropper.refresh(); }, 10);
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
        move(valueX, valueY) {
            const { coordinates, image } = this.$refs.cropper.getResult();
            //small step sizes
            if(valueX === 1) coordinates.left = (coordinates.left+this.stepSizeSmall > image.width) ? image.width : coordinates.left+this.stepSizeSmall;
            if(valueX === -1) coordinates.left = (coordinates.left-this.stepSizeSmall < 0) ? 0 : coordinates.left-this.stepSizeSmall;
            if(valueY === 1) coordinates.top = (coordinates.top+this.stepSizeSmall > image.height) ? image.height : coordinates.top+this.stepSizeSmall;
            if(valueY === -1) coordinates.top = (coordinates.top-this.stepSizeSmall < 0) ? 0 : coordinates.top-this.stepSizeSmall;
            //large step sizes
            if(valueX === 10) coordinates.left = (coordinates.left+this.stepSizeLarge > image.width) ? image.width : coordinates.left+this.stepSizeLarge;
            if(valueX === -10) coordinates.left = (coordinates.left-this.stepSizeLarge < 0) ? 0 : coordinates.left-this.stepSizeLarge;
            if(valueY === 10) coordinates.top = (coordinates.top+this.stepSizeLarge > image.height) ? image.height : coordinates.top+this.stepSizeLarge;
            if(valueY === -10) coordinates.top = (coordinates.top-this.stepSizeLarge < 0) ? 0 : coordinates.top-this.stepSizeLarge;
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
                this.removeKeyboardShortcuts();
            } else {
                this.addKeyboardShortcuts();
                this.$refs.cropper.setCoordinates({
                    width: this.options.trueSize[0], height: this.options.trueSize[1],
                    left: this.options.setSelect[0], top: this.options.setSelect[1],
                });
            }
            setTimeout(() => {
                this.$refs.cropper.refresh();
            },10);
        },
        addKeyboardShortcuts() {
            document.addEventListener('keydown', this.handleKeyDownShortcuts);
            document.addEventListener('keyup', this.handleKeyUpShortcuts);
        },
        removeKeyboardShortcuts() {
            document.removeEventListener( 'keydown', this.handleKeyDownShortcuts);
            document.removeEventListener('keyup', this.handleKeyUpShortcuts);
            if(this.holdTimeout_left) clearTimeout(this.holdTimeout_left);
            if(this.holdTimeout_right) clearTimeout(this.holdTimeout_right);
            if(this.holdTimeout_up) clearTimeout(this.holdTimeout_up);
            if(this.holdTimeout_down) clearTimeout(this.holdTimeout_down);
            if(this.repeatInterval_left) clearTimeout(this.repeatInterval_left);
            if(this.repeatInterval_right) clearTimeout(this.repeatInterval_right);
            if(this.repeatInterval_up) clearTimeout(this.repeatInterval_up);
            if(this.repeatInterval_down) clearTimeout(this.repeatInterval_down);
        },
        handleKeyDownShortcuts(event) {
            switch(event.key) {
                case 'ArrowLeft':
                    // Handle the left key press
                    if(!this.keyPressed_left) {
                        this.keyPressed_left = true;
                        this.move(-1, null);
                        this.holdTimeout_left = setTimeout(() => {
                            if(!this.keyPressed_left) return;
                            this.repeatInterval_left = setInterval(() => {
                                if(!this.keyPressed_left) return;
                                this.move(-10, null);
                            }, this.repeatInterval);
                        }, this.holdTimeoutDuration);
                    }
                    event.preventDefault();
                    break;
                case 'ArrowRight':
                    // Handle the right key press
                    if(!this.keyPressed_right) {
                        this.keyPressed_right = true;
                        this.move(1, null);
                        this.holdTimeout_right = setTimeout(() => {
                            if(!this.keyPressed_right) return;
                            this.repeatInterval_right = setInterval(() => {
                                if(!this.keyPressed_right) return;
                                this.move(10, null);
                            }, this.repeatInterval);
                        }, this.holdTimeoutDuration);
                    }
                    event.preventDefault();
                    break;
                case 'ArrowUp':
                    // Handle the up key press
                    if(!this.keyPressed_up) {
                        this.keyPressed_up = true;
                        this.move(null, -1);
                        this.holdTimeout_up = setTimeout(() => {
                            if(!this.keyPressed_up) return;
                            this.repeatInterval_up = setInterval(() => {
                                if(!this.keyPressed_up) return;
                                this.move(null, -10);
                            }, this.repeatInterval);
                        }, this.holdTimeoutDuration);
                    }
                    event.preventDefault();
                    break;
                case 'ArrowDown':
                    // Handle the down key press
                    if(!this.keyPressed_down) {
                        this.keyPressed_down = true;
                        this.move(null, 1);
                        this.holdTimeout_down = setTimeout(() => {
                            if(!this.keyPressed_down) return;
                            this.repeatInterval_down = setInterval(() => {
                                if(!this.keyPressed_down) return;
                                this.move(null, 10);
                            }, this.repeatInterval);
                        }, this.holdTimeoutDuration);
                    }
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
        handleKeyUpShortcuts(event) {
            switch(event.key) {
                case 'ArrowLeft':
                    this.keyPressed_left = false;
                    if(this.holdTimeout_left) clearTimeout(this.holdTimeout_left);
                    if(this.repeatInterval_left) clearInterval(this.repeatInterval_left);
                    event.preventDefault();
                    break;
                case 'ArrowRight':
                    this.keyPressed_right = false;
                    if(this.holdTimeout_right) clearTimeout(this.holdTimeout_right);
                    if(this.repeatInterval_right) clearInterval(this.repeatInterval_right);
                    event.preventDefault();
                    break;
                case 'ArrowUp':
                    this.keyPressed_up = false;
                    if(this.holdTimeout_up) clearTimeout(this.holdTimeout_up);
                    if(this.repeatInterval_up) clearInterval(this.repeatInterval_up);
                    event.preventDefault();
                    break;
                case 'ArrowDown':
                    this.keyPressed_down = false;
                    if(this.holdTimeout_down) clearTimeout(this.holdTimeout_down);
                    if(this.repeatInterval_down) clearInterval(this.repeatInterval_down);
                    event.preventDefault();
                    break;
                default:
                    // Ignore other key presses
                    return;
            }
        }
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

    --colorNormal: #539fea;
    --colorBg: rgba(255,255,255,.2);
    --colorHover: #f59500;
    --handleSize: 25px;
    &.largeHandles {
        --handleSize: 40px;
    }
    .vue-simple-handler::before,
    .vue-simple-handler::after { content:""; display:block; position:absolute; border:6px solid var(--colorBg); height: 100%; width: 100%; top:0; left:0; z-index:1; box-sizing: border-box; }
    .vue-simple-handler::after { border:4px solid var(--colorNormal); top:0; left:0; z-index:2; }
    .vue-simple-handler::before { width: calc(100% + 2px); height: calc(100% + 2px); }
    .vue-simple-handler--east-north::before { border-left:0; border-bottom:0; top:2px; left:-4px; }
    .vue-simple-handler--east-north::after { border-left:0; border-bottom:0; top:3px; left:-3px; }
    .vue-simple-handler--west-north::before { border-right:0; border-bottom:0; top:2px; left:2px; }
    .vue-simple-handler--west-north::after { border-right:0; border-bottom:0; top:3px; left:3px; }
    .vue-simple-handler--east-south::before { border-left:0; border-top:0; top:-4px; left:-4px; }
    .vue-simple-handler--east-south::after { border-left:0; border-top:0; top:-3px; left:-3px; }
    .vue-simple-handler--west-south::before { border-right:0; border-top:0; top:-4px; left:2px; }
    .vue-simple-handler--west-south::after { border-right:0; border-top:0; top:-3px; left:3px; }
    .vue-simple-handler--hover::after { border-color: #f59500; }

    .vue-simple-handler-wrapper { height: var(--handleSize); width: var(--handleSize); }
    .vue-simple-handler--east-north,
    .vue-simple-handler--west-north,
    .vue-simple-handler--east-south,
    .vue-simple-handler--west-south { border-color:#539fea; border-width: 0; height: calc( var(--handleSize) - 10px); width: calc( var(--handleSize) - 10px); opacity: 1; }
    .vue-handler-wrapper--north,
    .vue-handler-wrapper--south,
    .vue-handler-wrapper--east,
    .vue-handler-wrapper--west { visibility: hidden; }

    .line { border: 2px dashed red; }
}
</style>