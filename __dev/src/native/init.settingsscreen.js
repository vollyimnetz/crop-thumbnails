
import cptSettingsscreen from './../components/settingsscreen.vue'
import Vue from 'vue';
jQuery(document).ready(function ($) {
    if($('body.settings_page_page-cpt').length>0) {
        CROP_THUMBNAILS_VUE.app = new Vue({
            el: '#cpt_settings_settingsscreen',
            mounted: function () {console.log('cpt_settings_settingsscreen mounted')},
            components: {
                cptSettingsscreen
            }
        });
    }
});