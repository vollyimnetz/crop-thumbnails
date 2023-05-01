<template>
    <div class="cpt_PluginTest">
        <h2>{{settings.lang.general.nav_plugin_test}}</h2>

        <div class="cptLoadingSpinner" v-if="loading"></div>
        <div class="result" v-if="error"><strong class="fails">fail</strong> Failure processing the test - have a look on your server logs.</div>
        <div class="result" v-if="testResult && !error">
            <div v-for="(content,$index) in testResult" :key="$index" v-html="content"></div>
        </div>
        <div>
            <button type="button" class="button-primary startTest" @click="doTest">Start plugin quick-test.</button>
        </div>
    </div>
</template>

<script>
import { doPluginTest } from './api';

export default {
    props: {
        settings: { required:true, type:Object },
    },
    data: () => ({
        loading: false,
        testResult: null,
        error: false,
    }),
    methods: {
        doTest() {
            if(this.loading) return;
            this.loading = true;
            this.error = false;
            this.testResult = null;
            doPluginTest()
                .then(response => {
                    this.testResult = response.data
                })
                .catch(error => {
                    this.error = true;
                })
                .then(() =>{
                    this.loading = false;
                })
        }
    }
};
</script>

<style lang="scss">
.cpt_PluginTest {
    .cptLoadingSpinner { margin:1em 0; }
    .result { white-space:nowrap; background:#fff; border:1px solid #ddd; margin: 1em auto; padding: 1em;
        strong { display: inline-block; color:#fff; padding:3px 8px; margin-bottom: 1px; text-transform:uppercase; 
            &.success { background:#00cc00; }
            &.fails { background:#cc0000; }
            &.info { background:#008acc; }
        }
    }
    button.startTest { margin-top:1em; }
}
</style>
