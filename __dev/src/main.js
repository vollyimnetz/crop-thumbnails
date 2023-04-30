import './style/style.scss';
import './native/cpt_wait_for_final_event';
import './native/global.setup';
import './native/global.cachebreak';

import './native/init.clickhandler';
import './native/init.modal'
import './native/init.settingsscreen'


import { createApp } from 'vue'
import CropEditor from './components/CropEditor.vue';

createApp()
    .component('CropEditor', CropEditor)
    .mount('#cpt_crop_editor');