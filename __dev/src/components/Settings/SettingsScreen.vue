<template>
    <div class="cpt_SettingsScreen" v-if="settings">
        <nav class="tabNavigation">
            <button type="button" class="button" :class="{ 'button-primary': type==='post_types_and_sizes'}" @click="type='post_types_and_sizes'">{{settings.lang.general.nav_post_types}}</button>
            <button type="button" class="button" :class="{ 'button-primary': type==='user_permissions'}" @click="type='user_permissions'">{{settings.lang.general.nav_user_permissions}}</button>
            <button type="button" class="button" :class="{ 'button-primary': type==='developer_settings'}" @click="type='developer_settings'">{{settings.lang.general.nav_developer_settings}}</button>
            <button type="button" class="button" :class="{ 'button-primary': type==='quicktest'}" @click="type='quicktest'">{{settings.lang.general.nav_plugin_test}}</button>
            <button type="button" class="button" :class="{ 'button-primary': type==='toolkit'}" @click="type='toolkit'">Resize-Toolkit</button>

        </nav>
        <template v-if="type==='post_types_and_sizes'">
            <PostTypeSettings :settings="settings"></PostTypeSettings>
        </template>
        <template v-if="type==='user_permissions'">
            <UserPermissions :settings="settings"></UserPermissions>
        </template>
        <template v-if="type==='developer_settings'">
            <DeveloperSettings :settings="settings"></DeveloperSettings>
        </template>
        <template v-if="type==='quicktest'">
            <QuickTest :settings="settings"></QuickTest>
        </template>
        <template v-if="type==='toolkit'">
            <Toolkit :settings="settings"></Toolkit>
        </template>
    </div>
</template>

<script>
import PostTypeSettings from './PostTypeSettings.vue';
import Toolkit from './Toolkit.vue';
import QuickTest from './PluginTest.vue';
import UserPermissions from './UserPermissions.vue';
import DeveloperSettings from './DeveloperSettings.vue';

import { getSettings } from './api';

export default {
    components: { PostTypeSettings, Toolkit, QuickTest, UserPermissions, DeveloperSettings },
    mounted() { this.doSetup(); },
    data: () => ({
        loading: false,
        type: 'post_types_and_sizes',
        settings: null,
    }),
    methods: {
        doSetup() {
            if(this.loading) return;
            this.loading = true;
            getSettings()
                .then(response => {
                    this.settings = response.data
                })
                .catch(error => {

                })
                .then(() =>{
                    this.loading = false;
                })
        }
    }
};
</script>

<style lang="scss">
.cpt_SettingsScreen {
    button + button { margin-left:3px !important; }
    form label { display: block; }
    form .inputField { margin-bottom:1em; }

    .toolbar { margin:1em auto; }
    .toolbar.text-right { text-align: right; }
}

.cropThumbnailSettings {
  h2 { margin-top:3em; }
  .cpt_settings_paypal { border:1px solid #298CBA; border-radius:3px; background-color:#f6f6f6; max-width:30em; padding:0 0.5em; margin:2em 0; text-align:center; }
  .cpt_settings_submit { margin: 1.5em auto; }


  .cptSettingsPostList { margin: 0;
    &::after { content:""; display: block; clear:both; }
    &>li { padding:0 4px 4px 0; box-sizing: border-box; margin: 0;
      @media(min-width:760px) { width: 33.333%; float:left; }
    }
    section { border: 1px solid rgba(0,0,0,0.1); background: #fff; padding: 1em; 
      h3 { margin-top:0; overflow: hidden; text-overflow: ellipsis; }
      & ul { margin: 1em 0; border-bottom: 1px solid rgba(0,0,0,0.1); }
    }
  }
  .cptSettingsPostListDescription { text-align: center; font-size:1.2em; padding:1em; margin:0 0 4px; border: 1px solid rgba(0,0,0,0.1); }

  .form-table th,
  .form-table td { padding-top:0; padding-bottom:0; }
}
</style>
