import './init-on-settingspage';

CROP_THUMBNAILS_VUE.components['cpt-settingsscreen'] = {
    template: require('./settingsscreen.tpl.html'),
    props: {
        settings: {
            required: true
        },
    },
    components: {
        
    },
    data: function () {
        return {
            settingsData: JSON.parse(this.settings)
        };
    },
    mounted: function () {},
    computed: {},
    methods: {
        isButtonHiddenOnPostType : function(postType) {
            return (this.settingsData.options && this.settingsData.options.hide_post_type && this.settingsData.options.hide_post_type[postType] === "1");
        },
        isImageSizeHidden : function(postType,imageSize) {
            return (this.settingsData.options && this.settingsData.options.hide_size && this.settingsData.options.hide_size[postType] && this.settingsData.options.hide_size[postType][imageSize] === "1");
        }
    }
};