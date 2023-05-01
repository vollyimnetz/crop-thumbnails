<template>
    <div class="cpt_DeveloperSettings">
        <h2>{{settings.lang.general.nav_developer_settings}}</h2>

        <p>
            <label>
                <input type="checkbox" v-model="form.enable_debug_js" />
                {{settings.lang.developer_settings.enable_debug_js}}
            </label>
        </p>
        <p>
            <label>
                <input type="checkbox" v-model="form.enable_debug_data" />
                {{settings.lang.developer_settings.enable_debug_data}}
            </label>
        </p>


        <div v-if="result==='error'">{{result}}</div>
        <div v-if="result==='success'">{{settings.lang.general.successful_saved}}</div>

        <div>
            <button type="button" class="button-primary doSaveBtn" @click="doSave">{{settings.lang.general.save_changes}}</button>
        </div>
    </div>
</template>

<script>
import { saveDeveloperSettings, transformToBoolValue } from './api';
export default {
    props: {
        settings: { required:true, type:Object },
    },
    mounted() { this.doSetup(); },
    data: () => ({
        loading: false,
        form: {
            enable_debug_js: false,
            enable_debug_data: false,
        },
        error: false,
        result: null,//may be "error" or "success"
    }),
    methods: {
        doSetup() {
            if(this.settings.options) {
                this.form.enable_debug_data = transformToBoolValue(this.settings.options.debug_data)
                this.form.enable_debug_js = transformToBoolValue(this.settings.options.debug_js)
            }
        },
        doSave() {
            if(this.loading) return;
            this.loading = true;
            this.result = null;
            saveDeveloperSettings(this.form)
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
