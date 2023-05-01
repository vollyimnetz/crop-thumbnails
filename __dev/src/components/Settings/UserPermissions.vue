<template>
    <div class="cpt_UserPermissions">
        <h2>{{settings.lang.general.nav_user_permissions}}</h2>

        <p>
            <label>
                <input type="checkbox" v-model="form.user_permission_only_on_edit_files" />
                {{settings.lang.user_permissions.text}}
            </label>
        </p>

        <p>
            <button type="button" class="button-primary doSaveBtn" @click="doSave">{{settings.lang.general.save_changes}}</button>
        </p>

        <p v-if="result==='error'">{{result}}</p>
        <p v-if="result==='success'">{{settings.lang.general.successful_saved}}</p>
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
            user_permission_only_on_edit_files: false
        }
    }),
    methods: {
        doSetup() {
            if(this.settings.options) {
                this.form.user_permission_only_on_edit_files = transformToBoolValue(this.settings.options.user_permission_only_on_edit_files);
            }
        },
        doSave() {
            if(this.loading) return;
            this.loading = true;
            this.result = null;
            saveUserPermission(form)
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
