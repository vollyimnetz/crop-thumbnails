jQuery(document).ready(function ($) {
    if($('body.settings_page_page-cpt').length>0) {
        CROP_THUMBNAILS_VUE.app = new Vue({
            el: '#cpt_settings_settingsscreen',
            mounted: function () {},
            components: CROP_THUMBNAILS_VUE.components
        });
    }
});