import { dealApi, taskApi, userApi } from '@/api/crm';

export default {
  namespaced: true,
  state: () => ({
    stages: [],
    statuses: [],
    users: [],
    loaded: false,
  }),
  mutations: {
    setDictionaries(state, { stages, statuses, users }) {
      state.stages = stages;
      state.statuses = statuses;
      state.users = users;
      state.loaded = true;
    },
  },
  actions: {
    async load({ commit, state }) {
      if (state.loaded) {
        return;
      }

      const [stages, statuses, users] = await Promise.all([
        dealApi.stages(),
        taskApi.statuses(),
        userApi.list(),
      ]);

      commit('setDictionaries', {
        stages: stages.items,
        statuses: statuses.items,
        users: users.items,
      });
    },
  },
};
