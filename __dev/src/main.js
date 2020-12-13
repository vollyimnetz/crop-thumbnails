/*import Vue from 'vue'
import App from './App.vue'
import 'style/style.scss'

Vue.config.productionTip = false

new Vue({
  render: h => h(App),
}).$mount('#app')
*/
import './style/style.scss';
import './native/cpt_wait_for_final_event';
import './native/global.setup';
import './native/global.cachebreak';

import './native/init.clickhandler';
import './native/init.modal'
import './native/init.settingsscreen'