
import { createApp } from 'vue'
import cptSettingsscreen from './../components/settingsscreen.vue'

jQuery(function ($) {
    if($('body.settings_page_page-cpt').length>0) {
        window.CROP_THUMBNAILS_VUE.app = createApp();
        window.CROP_THUMBNAILS_VUE.app.component('cptSettingsscreen',cptSettingsscreen);
        window.CROP_THUMBNAILS_VUE.app.mount('#cpt_settings_settingsscreen');
    }
});