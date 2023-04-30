<template>
    <div class="cpt_UserPermissions">
        <h2>{{settings.lang.general.nav_user_permissions}}</h2>

        <label>
            <input type="checkbox" value="1" />
            {{settings.lang.user_permissions.text}}
        </label>

        <div v-if="result==='error'">{{result}}</div>
        <div v-if="result==='success'">{{settings.lang.general.successful_saved}}</div>

        <div>
            <button type="button" class="button-secondary startTest" @click="doSave">{{settings.lang.general.save_changes}}</button>
        </div>
    </div>
</template>

<script>
import { saveUserPermission } from './api';
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
            saveUserPermission()
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
