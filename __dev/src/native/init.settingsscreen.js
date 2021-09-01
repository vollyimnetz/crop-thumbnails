
import cptSettingsscreen from './../components/settingsscreen.vue'
import Vue from 'vue';
jQuery(function ($) {
    if($('body.settings_page_page-cpt').length>0) {
        CROP_THUMBNAILS_VUE.app = new Vue({
            el: '#cpt_settings_settingsscreen',
            components: {
                cptSettingsscreen
            }
        });
    }
});