<template>
  <div id="app-root">
    <router-view v-if="isLoginRoute" />
    <AppLayout v-else>
      <router-view />
    </AppLayout>
  </div>
</template>

<script>
import AppLayout from '@/components/AppLayout.vue';

export default {
  name: 'App',
  components: { AppLayout },
  computed: {
    isLoginRoute() {
      return this.$route.name === 'login';
    },
  },
};
</script>
