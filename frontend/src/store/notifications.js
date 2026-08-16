import { notificationApi } from '@/api/crm';

export default {
  namespaced: true,
  state: () => ({
    items: [],
    unread: 0,
  }),
  mutations: {
    setItems(state, items) {
      state.items = items;
    },
    setUnread(state, unread) {
      state.unread = unread;
    },
  },
  actions: {
    async fetch({ commit }, onlyUnread = false) {
      const response = await notificationApi.list({ onlyUnread, limit: 30 });
      commit('setItems', response.items);
    },
    async fetchUnread({ commit }) {
      try {
        const response = await notificationApi.unreadCount();
        commit('setUnread', response.count);
      } catch (error) {
        commit('setUnread', 0);
      }
    },
    async markAllRead({ dispatch }) {
      await notificationApi.readAll();
      await dispatch('fetch');
      await dispatch('fetchUnread');
    },
  },
};
