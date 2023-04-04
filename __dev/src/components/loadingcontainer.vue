<template>
    <div class="loadingcontainer" :class="status">
        <img :src="image" style="display:none;" />
        <slot></slot>
        
        <transition name="fade">
            <div class="loadingMsg" v-if="status==='loading'">
                <div class="cptLoadingSpinner"></div>
            </div>
        </transition>
    </div>
</template>

<script>
import imagesLoaded from 'imagesloaded';
export default {
    props:{
        image : { required: true, type:String }
    },
    data: () => ({
        status:null
    }),
    mounted() { this.setup(); },
    watch:{
        image() {
            this.setup();
        }
    },
    methods:{
        setup() {
            this.setStart();
            setTimeout(() => {
                var imgLoad = imagesLoaded( this.$el );
                imgLoad
                    .once('done',() => {
                        if(this.status!=='failed') {
                            this.setComplete();
                        }
                    })
                    .once('fail',() => {
                        this.setFailed();
                    });
            },300);
        },
        setComplete() {
            this.status = 'completed';
        },
        setStart() {
            this.status = 'loading';
        },
        setFailed() {
            this.status = 'failed';
        }
    }
}
</script>