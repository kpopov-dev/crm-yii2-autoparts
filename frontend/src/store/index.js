import Vue from 'vue';
import Vuex from 'vuex';
import auth from './auth';
import notifications from './notifications';
import dictionaries from './dictionaries';

Vue.use(Vuex);

export default new Vuex.Store({
  strict: import.meta.env.DEV,
  modules: {
    auth,
    notifications,
    dictionaries,
  },
});
