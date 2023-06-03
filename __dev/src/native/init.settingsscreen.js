
import { createApp } from 'vue'
import store from './../components/Settings/storeSetup';
import SettingsScreen from './../components/Settings/SettingsScreen.vue'
import jQuery from "jquery";
jQuery(function ($) {
    if($('body.settings_page_page-cpt').length>0) {
        window.CROP_THUMBNAILS_VUE.app = createApp();
        window.CROP_THUMBNAILS_VUE.app.component('cptSettingsscreen',SettingsScreen);
        window.CROP_THUMBNAILS_VUE.app.mount('#cpt_settings_settingsscreen');
        window.CROP_THUMBNAILS_VUE.app.use(store);
    }
});