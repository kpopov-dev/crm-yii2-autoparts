<template>
  <div>
    <div class="header">
      <h1>Уведомления</h1>
      <button class="btn" type="button" :disabled="unread === 0" @click="markAll">
        Отметить все прочитанными
      </button>
    </div>

    <p class="muted">
      Уведомления создаются консьюмером RabbitMQ после обработки событий сделок и задач.
    </p>

    <div class="card">
      <ul class="feed">
        <li v-for="item in items" :key="item.id" :class="{ 'feed__item--unread': !item.isRead }">
          <div class="feed__title">{{ item.title }}</div>
          <div v-if="item.body" class="feed__body muted">{{ item.body }}</div>
          <div class="feed__meta muted">{{ item.type }} · {{ dateTime(item.createdAt) }}</div>
        </li>
      </ul>

      <p v-if="items.length === 0" class="muted">
        Уведомлений пока нет. Смените стадию заказа, чтобы событие прошло через очередь.
      </p>
    </div>
  </div>
</template>

<script>
import { formatDateTime } from '@/utils/format';

export default {
  name: 'NotificationsView',
  computed: {
    items() {
      return this.$store.state.notifications.items;
    },
    unread() {
      return this.$store.state.notifications.unread;
    },
  },
  created() {
    this.$store.dispatch('notifications/fetch');
    this.$store.dispatch('notifications/fetchUnread');
  },
  methods: {
    dateTime: (value) => formatDateTime(value),
    markAll() {
      this.$store.dispatch('notifications/markAllRead');
    },
  },
};
</script>

<style scoped>
.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.feed {
  list-style: none;
  margin: 0;
  padding: 0;
}

.feed li {
  padding: 10px 0 10px 12px;
  border-left: 3px solid transparent;
  border-bottom: 1px solid var(--color-border);
}

.feed__item--unread {
  border-left-color: var(--color-primary);
  background: #f7f9ff;
}

.feed__title {
  font-weight: 600;
}

.feed__body,
.feed__meta {
  font-size: 12px;
}
</style>
