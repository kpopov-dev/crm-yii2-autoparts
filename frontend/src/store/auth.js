import { authApi } from '@/api/crm';
import { clearToken, getToken, setToken } from '@/api/http';

export default {
  namespaced: true,
  state: () => ({
    user: null,
    loading: false,
    error: '',
  }),
  getters: {
    isAuthenticated: (state) => Boolean(state.user) || Boolean(getToken()),
    isAdmin: (state) => Boolean(state.user) && state.user.role === 'admin',
    userName: (state) => (state.user ? state.user.fullName : ''),
  },
  mutations: {
    setUser(state, user) {
      state.user = user;
    },
    setLoading(state, loading) {
      state.loading = loading;
    },
    setError(state, error) {
      state.error = error;
    },
  },
  actions: {
    async login({ commit }, { email, password }) {
      commit('setLoading', true);
      commit('setError', '');

      try {
        const response = await authApi.login(email, password);
        setToken(response.token);
        commit('setUser', response.user);

        return true;
      } catch (error) {
        commit('setError', error.message);

        return false;
      } finally {
        commit('setLoading', false);
      }
    },
    async fetchProfile({ commit }) {
      if (!getToken()) {
        return null;
      }

      try {
        const user = await authApi.me();
        commit('setUser', user);

        return user;
      } catch (error) {
        clearToken();
        commit('setUser', null);

        return null;
      }
    },
    logout({ commit }) {
      clearToken();
      commit('setUser', null);
    },
  },
};
