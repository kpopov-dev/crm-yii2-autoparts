<template>
  <div class="layout">
    <aside class="sidebar">
      <div class="sidebar__brand">
        АвтоДеталь
        <span class="sidebar__brand-sub">оптовый склад запчастей</span>
      </div>
      <nav class="sidebar__nav">
        <router-link to="/" exact>Дашборд</router-link>
        <router-link to="/deals">Воронка заказов</router-link>
        <router-link to="/deals/list">Заказы</router-link>
        <router-link to="/clients">Контрагенты</router-link>
        <router-link to="/tasks">Задачи</router-link>
        <router-link to="/notifications">
          Уведомления
          <span v-if="unread > 0" class="sidebar__counter">{{ unread }}</span>
        </router-link>
      </nav>
    </aside>

    <div class="content">
      <header class="topbar">
        <div class="topbar__user">
          <strong>{{ userName }}</strong>
          <span class="muted">{{ roleLabel }}</span>
        </div>
        <button class="btn btn--sm" type="button" @click="onLogout">Выйти</button>
      </header>

      <main class="main">
        <slot />
      </main>
    </div>
  </div>
</template>

<script>
export default {
  name: 'AppLayout',
  computed: {
    userName() {
      return this.$store.getters['auth/userName'];
    },
    roleLabel() {
      return this.$store.getters['auth/isAdmin'] ? 'Администратор' : 'Менеджер';
    },
    unread() {
      return this.$store.state.notifications.unread;
    },
  },
  created() {
    this.$store.dispatch('notifications/fetchUnread');
    this.timer = window.setInterval(() => {
      this.$store.dispatch('notifications/fetchUnread');
    }, 30000);
  },
  beforeDestroy() {
    window.clearInterval(this.timer);
  },
  methods: {
    onLogout() {
      this.$store.dispatch('auth/logout');
      this.$router.push({ name: 'login' });
    },
  },
};
</script>

<style scoped>
.layout {
  display: flex;
  min-height: 100vh;
}

.sidebar {
  width: 220px;
  background: #1d2433;
  color: #fff;
  padding: 20px 0;
  flex-shrink: 0;
}

.sidebar__brand {
  font-size: 20px;
  font-weight: 700;
  letter-spacing: 0.04em;
  padding: 0 20px 20px;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.sidebar__brand-sub {
  font-size: 11px;
  font-weight: 400;
  letter-spacing: 0.02em;
  color: #8f9ab5;
  text-transform: uppercase;
}

.sidebar__nav {
  display: flex;
  flex-direction: column;
}

.sidebar__nav a {
  color: #b9c2d6;
  padding: 10px 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.sidebar__nav a:hover {
  background: #262f41;
  color: #fff;
  text-decoration: none;
}

.sidebar__nav a.router-link-active {
  background: #2f6fed;
  color: #fff;
}

.sidebar__counter {
  background: #d64545;
  border-radius: 999px;
  font-size: 11px;
  padding: 1px 7px;
}

.content {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.topbar {
  background: #fff;
  border-bottom: 1px solid var(--color-border);
  padding: 12px 24px;
  display: flex;
  justify-content: flex-end;
  align-items: center;
  gap: 16px;
}

.topbar__user {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  line-height: 1.2;
}

.main {
  padding: 24px;
  flex: 1;
}
</style>
