<template>
    <div class="notice notice-warning is-dismissible cptMessage" aria-role="alert" v-if="!closed">
        <p><slot></slot></p>
        <button type="button" class="notice-dismiss" @click="close()" aria-label="close"></button>
    </div>
</template>

<script>
export default {
    props:{},
    data:() => ({
        closed:false
    }),
    methods:{
        close() {
            this.closed = true;
        }
    }
}
</script>