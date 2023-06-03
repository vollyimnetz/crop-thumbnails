/**
 * use with 
 */

// lets create the default state
const getInitialState = () => {
    return {
        adminUrl: null,
    }
}
  
  /** store module **************************************************************************/
export const storeModule = {
    namespaced: true,
    state: getInitialState(),
    
    // getters => all the public available access points
    getters: {
        adminUrl: state => state.adminUrl,
    },
  
    // mutations => all the things that change the state of the store
    mutations: {
        setAdminUrl(state, value) { state.adminUrl = value; },
    },
  
    // actions => all the actions/functionality the store can perform
    actions: {
        setAdminUrl({ commit }, value) {
            commit('setAdminUrl', value);
        },
    }
  }