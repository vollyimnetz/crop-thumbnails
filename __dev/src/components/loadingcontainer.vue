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
export default {
    props:{
        image : {
            required: true,
            type:String
        }
    },
    data:function() {
        return {
            status:null
        };
    },
    watch:{
        image:function() {
            this.setup();
        }
    },
    mounted:function() {
        this.setup();
    },
    methods:{
        setup : function() {
            var that = this;
            that.setStart();
            setTimeout(function() {
                var imgLoad = imagesLoaded( that.$el );
                imgLoad
                    .once('done',function() {
                        if(that.status!=='failed') {
                            that.setComplete();
                        }
                    })
                    .once('fail',function() {
                        that.setFailed();
                    })
                    ;
            },300);
        },
        setComplete : function() {
            this.status = 'completed';
        },
        setStart : function() {
            this.status = 'loading';
        },
        setFailed : function() {
            this.status = 'failed';
        }
    }
}
</script>