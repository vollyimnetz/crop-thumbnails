<template>
    <div class="cpt_DeveloperSettings">
        <h2>{{settings.lang.general.nav_developer_settings}}</h2>

        <p>
            <label>
                <input type="checkbox" value="1" />
                {{settings.lang.developer_settings.enable_js_debug}}
            </label>
        </p>
        <p>
            <label>
                <input type="checkbox" value="1" />
                {{settings.lang.developer_settings.enable_data_debug}}
            </label>
        </p>


        <div v-if="result==='error'">{{result}}</div>
        <div v-if="result==='success'">{{settings.lang.general.successful_saved}}</div>

        <div>
            <button type="button" class="button-secondary startTest" @click="doSave">{{settings.lang.general.save_changes}}</button>
        </div>
    </div>
</template>

<script>
import { saveDeveloperSettings } from './api';
export default {
    props: {
        settings: { required:true, type:Object },
    },
    data: () => ({
        loading: false,
        error: false,
        result: null,//may be "error" or "success"
    }),
    methods: {
        doSave() {
            if(this.loading) return;
            this.loading = true;
            this.result = null;
            saveDeveloperSettings()
                .then(response => {
                    this.result = 'success';
                })
                .catch(error => {
                    this.result = 'error';
                })
                .then(() =>{
                    this.loading = false;
                })
        }
    }
};
</script>

<style lang="scss">
</style>
