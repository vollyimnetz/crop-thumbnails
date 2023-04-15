<template>
    <div class="JCrop">
        <img :src="src" ref="cropImage" class="cropImage" />
    </div>
</template>

<script>
//
import JcropObject from 'jcrop';
export default {
    props: {
        src : { required:true, type: String },
        options : { required:true, type: Object },
    },
    emits: [ 'activate', 'update', 'change', 'remove' ],
    mounted() { this.doSetup(); },
    beforeUnmount() { this.doTeardown() },
    data: () =>({
        jcrop: null,
        rect: null,

        resizeEvent:null,
    }),
    watch: {
        options: {
            handler(newValue) {
                this.applyOptions();
            },
            deep: true
        }
    },
    methods: {
        doTeardown() {
            if(this.jcrop) this.jcrop.destroy();
            this.jcrop = null;
            if(this.resizeEvent) removeEventListener(this.resizeEvent);
            this.resizeEvent = null;
        },
        doSetup() {
            this.resizeEvent = addEventListener("resize", (event) => {
                console.log('resize');


                //document.querySelector('.JCrop .jcrop-stage.jcrop-image-stage').style = "";
                if(this.jcrop) {
                    let imageStage = this.jcrop.updateShades();
                    imageStage.el.style = "";

                    this.jcrop.resizeToImage();
                    this.jcrop.refresh();
                }
            });
            JcropObject.load(this.$refs.cropImage).then(this.startJcrop);
        },
        startJcrop(img) {
            this.jcrop = JcropObject.attach(img, this.options || {});
            window.CROP_THUMBNAILS_JCROP = JcropObject;//make public accessable
            window.CROP_THUMBNAILS_JCROP_ELEM = this.jcrop;//make public accessable

            this.jcrop.listen('crop.activate', widget => {
                this.jcrop.listen('crop.activate', data => this.$emit('activate', data));
            });
            this.jcrop.listen('crop.change', widget => {
                this.jcrop.listen('crop.change', data => this.$emit('change', data));
            });
            this.jcrop.listen('crop.remove', widget => {
                this.jcrop.listen('crop.remove', data => this.$emit('remove', data));
            });
            this.jcrop.listen('crop.update', widget => {
                this.jcrop.refresh();//refresh the background
                this.jcrop.listen('crop.update', data => this.$emit('update', data));
            });

            this.applyOptions();
        },
        applyOptions() {
            this.rect = JcropObject.Rect.create(0,0,100,100);
            window.CROP_THUMBNAILS_JCROP_RECT = this.rect;
            //this.rect = JcropObject.Rect.create(this.options.setSelect[0],this.options.setSelect[1], this.options.setSelect[2], this.options.setSelect[3]);
            //this.jcrop.setOptions(this.options);
            console.log('startJcrop options', this.options);
            this.jcrop.newWidget(this.rect);

            console.log('options changed - new value', newValue);
            console.log('options changed - jcrop', this.jcrop)
            console.log('options changed - rect', this.rect)
            this.jcrop.setOptions({ aspectRatio: this.options.aspectRatio });
            if(this.rect) {
                this.rect.center(100000,100000);

            }
            this.jcrop.refresh();
            this.jcrop.focus();
        }
    }
}
</script>

<style lang="scss">
@import "jcrop/build/css/jcrop.scss";
.JCrop {
    .jcrop-stage { max-width: 100% !important;}
    img.cropImage { max-width: 100% !important; height: auto !important; }

    .jcrop-widget { outline: 0px solid #000; border: 0px solid #000;
        --line-width:2px;
        background: 
            linear-gradient(90deg, rgba(0,0,0,0.7) 50%, transparent 50%), 
            linear-gradient(90deg, rgba(0,0,0,0.7) 50%, transparent 50%), 
            linear-gradient(0deg, rgba(0,0,0,0.7) 50%, transparent 50%), 
            linear-gradient(0deg, rgba(0,0,0,0.7) 50%, transparent 50%);
        background-repeat: repeat-x, repeat-x, repeat-y, repeat-y;
        background-size: 15px var(--line-width), 15px var(--line-width), var(--line-width) 15px, var(--line-width) 15px;
        background-position: 0px 0px, 100% 100%, 0px 100%, 100% 0px;
        padding: 10px;
        animation: border-dance 15s infinite linear;
    }
    
    @keyframes border-dance {
        0% { background-position: 0px 0px, 100% 100%, 0px 100%, 100% 0px; }
        100% { background-position: 100% 0px, 0px 100%, 0px 0px, 100% 100%; }
    }
    --crop-thumbnails-handle-offset: -10px;
    --crop-thumbnails-handle-size: 20px;
    &.largeHandles {
        --crop-thumbnails-handle-offset: -20px;
        --crop-thumbnails-handle-size: 40px;
    }
    .jcrop-widget .jcrop-handle { width: var(--crop-thumbnails-handle-size); height: var(--crop-thumbnails-handle-size); background: rgba(0,0,0,.5); border:2px rgba(0,0,0,.7) solid; }
    .jcrop-widget .jcrop-handle.n { top: var(--crop-thumbnails-handle-offset); display: none; }
    .jcrop-widget .jcrop-handle.s { bottom: var(--crop-thumbnails-handle-offset); display: none; }
    .jcrop-widget .jcrop-handle.e { right: var(--crop-thumbnails-handle-offset); display: none; }
    .jcrop-widget .jcrop-handle.w { left: var(--crop-thumbnails-handle-offset); display: none; }
    .jcrop-widget .jcrop-handle.sw { bottom: var(--crop-thumbnails-handle-offset); left: var(--crop-thumbnails-handle-offset); }
    .jcrop-widget .jcrop-handle.nw { top: var(--crop-thumbnails-handle-offset); left: var(--crop-thumbnails-handle-offset); }
    .jcrop-widget .jcrop-handle.ne { top: var(--crop-thumbnails-handle-offset); right: var(--crop-thumbnails-handle-offset); }
    .jcrop-widget .jcrop-handle.se { bottom: var(--crop-thumbnails-handle-offset); right: var(--crop-thumbnails-handle-offset);}
}
</style>