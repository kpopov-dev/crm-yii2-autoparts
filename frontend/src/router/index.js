import Vue from 'vue';
import VueRouter from 'vue-router';
import store from '@/store';
import { getToken } from '@/api/http';

Vue.use(VueRouter);

const routes = [
  {
    path: '/login',
    name: 'login',
    component: () => import('@/views/LoginView.vue'),
    meta: { public: true },
  },
  {
    path: '/',
    name: 'dashboard',
    component: () => import('@/views/DashboardView.vue'),
  },
  {
    path: '/deals',
    name: 'deals',
    component: () => import('@/views/DealsBoardView.vue'),
  },
  {
    path: '/deals/list',
    name: 'deals-list',
    component: () => import('@/views/DealsListView.vue'),
  },
  {
    path: '/deals/:id',
    name: 'deal',
    component: () => import('@/views/DealView.vue'),
    props: true,
  },
  {
    path: '/clients',
    name: 'clients',
    component: () => import('@/views/ClientsView.vue'),
  },
  {
    path: '/tasks',
    name: 'tasks',
    component: () => import('@/views/TasksView.vue'),
  },
  {
    path: '/notifications',
    name: 'notifications',
    component: () => import('@/views/NotificationsView.vue'),
  },
  {
    path: '*',
    redirect: '/',
  },
];

const router = new VueRouter({
  mode: 'history',
  routes,
});

router.beforeEach(async (to, from, next) => {
  if (to.meta.public) {
    next();

    return;
  }

  if (!getToken()) {
    next({ name: 'login', query: { redirect: to.fullPath } });

    return;
  }

  if (!store.state.auth.user) {
    const user = await store.dispatch('auth/fetchProfile');

    if (!user) {
      next({ name: 'login' });

      return;
    }

    await store.dispatch('dictionaries/load');
  }

  next();
});

export default router;
