<template>
    <div class="cpt_SettingsScreen">
        <div class="cptLoadingSpinner" v-if="loading"></div>
        <section v-if="settings">
            <nav class="tabNavigation">
                <button type="button" class="button" :class="{ 'button-primary': type==='post_types_and_sizes'}" @click="type='post_types_and_sizes'">{{settings.lang.general.nav_post_types}}</button>
                <button type="button" class="button" :class="{ 'button-primary': type==='user_permissions'}" @click="type='user_permissions'">{{settings.lang.general.nav_user_permissions}}</button>
                <button type="button" class="button" :class="{ 'button-primary': type==='developer_settings'}" @click="type='developer_settings'">{{settings.lang.general.nav_developer_settings}}</button>
                <button type="button" class="button" :class="{ 'button-primary': type==='quicktest'}" @click="type='quicktest'">{{settings.lang.general.nav_plugin_test}}</button>
                <!--<button type="button" class="button" :class="{ 'button-primary': type==='toolkit'}" @click="type='toolkit'">Resize-Toolkit</button>-->
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
            <div>
                <PaypalInfo :settings="settings"></PaypalInfo>
            </div>
        </section>
    </div>
</template>

<script>
import PostTypeSettings from './PostTypeSettings.vue';
import Toolkit from './Toolkit.vue';
import QuickTest from './PluginTest.vue';
import UserPermissions from './UserPermissions.vue';
import DeveloperSettings from './DeveloperSettings.vue';
import PaypalInfo from './PaypalInfo.vue';

import { getSettings } from './api';

export default {
    components: { PostTypeSettings, Toolkit, QuickTest, UserPermissions, DeveloperSettings, PaypalInfo },
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
    h2 { margin-top:3em; }
}
</style>
