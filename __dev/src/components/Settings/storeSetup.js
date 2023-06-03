import { createStore } from 'vuex'
import { storeModule as toolkitStore } from './store'

const storeObj = {
    modules: {
        toolkit: toolkitStore
    }//fill modules later
};

export default createStore(storeObj);