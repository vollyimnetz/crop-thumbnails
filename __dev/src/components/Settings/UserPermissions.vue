<template>
    <div class="cpt_UserPermissions">
        <h2>{{settings.lang.user_settings.nav_user_permissions}}</h2>

        <p>
            <label>
                <input type="checkbox" v-model="form.user_permission_only_on_edit_files" />
                {{settings.lang.user_settings.text_user_permissions}}
            </label>
        </p>

        <h2>{{settings.lang.user_settings.nav_same_ratio_mode}}</h2>

        <p>{{settings.lang.user_settings.text_same_ratio_mode}}</p>
        <p>
            <label>
                {{settings.lang.user_settings.label_same_ratio_mode}}
                <select type="checkbox" v-model="form.same_ratio_mode">
                    <option :value="null">{{settings.lang.user_settings.label_same_ratio_mode_default}}</option>
                    <option value="select">{{settings.lang.user_settings.label_same_ratio_mode_select}}</option>
                    <option value="group">{{settings.lang.user_settings.label_same_ratio_mode_group}}</option>
                </select>
            </label>
        </p>

        <p v-if="result==='error'">{{result}}</p>
        <p v-if="result==='success'">{{settings.lang.general.successful_saved}}</p>

        <p style="margin-top:3em;">
            <button type="button" class="button-primary doSaveBtn" @click="doSave">{{settings.lang.general.save_changes}}</button>
            <span class="cptLoadingSpinner small" v-if="loading"></span>
        </p>
    </div>
</template>

<script>
import { saveUserPermission, transformToBoolValue } from './api';
export default {
    props: {
        settings: { required:true, type:Object },
    },
    mounted() { this.doSetup(); },
    data: () => ({
        loading: false,
        error: false,
        result: null,//may be "error" or "success"
        form: {
            same_ratio_mode: null,
            user_permission_only_on_edit_files: false
        }
    }),
    methods: {
        doSetup() {
            if(this.settings.options) {
                this.form.user_permission_only_on_edit_files = transformToBoolValue(this.settings.options.user_permission_only_on_edit_files);
                this.form.same_ratio_mode = this.settings.options.same_ratio_mode;
            }
        },
        doSave() {
            if(this.loading) return;
            this.loading = true;
            this.result = null;
            saveUserPermission(this.form)
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
