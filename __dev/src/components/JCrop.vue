<template>
    <div class="JCrop">
        <img :src="src" ref="cropImage" class="cropImage" />
    </div>
</template>

<script>
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
    }),
    watch: {
        options: {
            handler(newValue) {
                console.log('options changed', this.jcrop, newValue);
                this.jcrop.setOptions(newValue);
                this.jcrop.Rect.center(100000,100000);
                this.jcrop.refresh();
            },
            deep: true
        }
    },
    methods: {
        doTeardown() {
            console.log('doTeardown')
            if(this.jcrop) this.jcrop.destroy();
            this.jcrop = null;
        },
        doSetup() {/*
            const img = new Image();
            this.$el.appendChild(img);
            img.src = this.src;
            */
            JcropObject.load(this.$refs.cropImage).then(this.startJcrop);
            
        },
        startJcrop(img){
            this.jcrop = JcropObject.attach(img, this.options || {});
            window.CropThumbnailsJcrop = this.jcrop;
            let rect = JcropObject.Rect.sizeOf(this.jcrop.el);

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

            if(this.rect) rect = JcropObject.Rect.from(this.rect);
            else rect = rect.scale(.7,.5).center(rect.w, rect.h)

            this.jcrop.newWidget(rect);
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

    --crop-thumbnails-handle-offset: -6px;
    --crop-thumbnails-handle-size: 10px;
    .jcrop-widget .jcrop-handle { width: var(--crop-thumbnails-handle-size); height: var(--crop-thumbnails-handle-size); }
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