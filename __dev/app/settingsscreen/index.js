import './init-on-settingspage';

CROP_THUMBNAILS_VUE.components['cpt-settingsscreen'] = {
    template: require('./settingsscreen.tpl.html'),
    props: {
    },
    components: {
        
    },
    data: function () {
        return {
            test: 'hallo welt',
            lang: null,//language-variable (filled after initial request)
            nonce: null,//the nonce for the crop-request
        };
    },
    mounted: function () {
        console.log('settingsscreen mounted');
    },
    computed: {},
    methods: {}
};