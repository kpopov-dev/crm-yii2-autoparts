<template>
  <div>
    <div class="header">
      <h1>Задачи</h1>
      <button class="btn btn--primary" type="button" @click="openCreate">Новая задача</button>
    </div>

    <div class="toolbar">
      <div class="field">
        <label>Статус</label>
        <select v-model="filter.status" class="select" @change="reload">
          <option value="">Все</option>
          <option v-for="status in statuses" :key="status.code" :value="status.code">{{ status.label }}</option>
        </select>
      </div>
      <div v-if="isAdmin" class="field">
        <label>Исполнитель</label>
        <select v-model="filter.assigneeId" class="select" @change="reload">
          <option value="">Все</option>
          <option v-for="user in users" :key="user.id" :value="user.id">{{ user.fullName }}</option>
        </select>
      </div>
      <div class="field">
        <label>&nbsp;</label>
        <label class="checkbox">
          <input v-model="filter.overdue" type="checkbox" @change="reload" />
          Только просроченные
        </label>
      </div>
    </div>

    <div v-if="error" class="alert alert--error">{{ error }}</div>

    <div class="card">
      <table class="table">
        <thead>
          <tr>
            <th>Задача</th>
            <th>Заказ</th>
            <th>Исполнитель</th>
            <th>Срок</th>
            <th>Статус</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="task in items" :key="task.id">
            <td>{{ task.title }}</td>
            <td>
              <router-link v-if="task.dealId" :to="{ name: 'deal', params: { id: task.dealId } }">
                {{ task.dealNumber }}
              </router-link>
              <span v-else class="muted">—</span>
            </td>
            <td>{{ task.assigneeName }}</td>
            <td :class="{ overdue: task.isOverdue }">{{ dateTime(task.dueAt) }}</td>
            <td><span class="badge" :class="statusClass(task)">{{ task.statusLabel }}</span></td>
            <td class="actions">
              <button
                v-if="task.status !== 'done' && task.status !== 'canceled'"
                class="btn btn--sm"
                type="button"
                @click="complete(task)"
              >
                Выполнить
              </button>
            </td>
          </tr>
          <tr v-if="items.length === 0">
            <td colspan="6" class="muted">Задачи не найдены</td>
          </tr>
        </tbody>
      </table>

      <PaginationBar :meta="meta" @change="onPage" />
    </div>

    <ModalDialog v-if="formVisible" title="Новая задача" @close="formVisible = false">
      <div v-if="formError" class="alert alert--error">{{ formError }}</div>

      <div class="field">
        <label>Название</label>
        <input v-model.trim="form.title" class="input" />
      </div>
      <div class="field">
        <label>Исполнитель</label>
        <select v-model="form.assigneeId" class="select">
          <option v-for="user in users" :key="user.id" :value="user.id">{{ user.fullName }}</option>
        </select>
      </div>
      <div class="field">
        <label>Срок</label>
        <input v-model="form.dueAt" class="input" type="datetime-local" />
      </div>
      <div class="field">
        <label>Описание</label>
        <textarea v-model.trim="form.description" class="textarea" rows="3"></textarea>
      </div>

      <template #footer>
        <button class="btn" type="button" @click="formVisible = false">Отмена</button>
        <button class="btn btn--primary" type="button" :disabled="saving" @click="submit">Создать</button>
      </template>
    </ModalDialog>
  </div>
</template>

<script>
import ModalDialog from '@/components/ModalDialog.vue';
import PaginationBar from '@/components/PaginationBar.vue';
import { taskApi } from '@/api/crm';
import { formatDateTime } from '@/utils/format';

export default {
  name: 'TasksView',
  components: { ModalDialog, PaginationBar },
  data() {
    return {
      items: [],
      meta: null,
      page: 1,
      filter: { status: '', assigneeId: '', overdue: false, sort: 'dueAt' },
      form: { title: '', assigneeId: null, dueAt: '', description: '' },
      formVisible: false,
      saving: false,
      error: '',
      formError: '',
    };
  },
  computed: {
    isAdmin() {
      return this.$store.getters['auth/isAdmin'];
    },
    users() {
      return this.$store.state.dictionaries.users;
    },
    statuses() {
      return this.$store.state.dictionaries.statuses;
    },
  },
  created() {
    this.load();
  },
  methods: {
    dateTime: (value) => formatDateTime(value),
    statusClass(task) {
      if (task.status === 'done') {
        return 'badge--success';
      }

      if (task.isOverdue) {
        return 'badge--danger';
      }

      return '';
    },
    reload() {
      this.page = 1;
      this.load();
    },
    onPage(page) {
      this.page = page;
      this.load();
    },
    async load() {
      this.error = '';

      try {
        const response = await taskApi.list({ ...this.filter, page: this.page, limit: 20 });
        this.items = response.items;
        this.meta = response.meta;
      } catch (error) {
        this.error = error.message;
      }
    },
    openCreate() {
      const tomorrow = new Date(Date.now() + 86400000);
      tomorrow.setSeconds(0, 0);

      this.form = {
        title: '',
        assigneeId: this.$store.state.auth.user.id,
        dueAt: tomorrow.toISOString().slice(0, 16),
        description: '',
      };
      this.formError = '';
      this.formVisible = true;
    },
    async submit() {
      this.saving = true;
      this.formError = '';

      try {
        await taskApi.create({
          ...this.form,
          dueAt: this.form.dueAt.replace('T', ' '),
        });
        this.formVisible = false;
        await this.load();
        await this.$store.dispatch('notifications/fetchUnread');
      } catch (error) {
        this.formError = error.message;
      } finally {
        this.saving = false;
      }
    },
    async complete(task) {
      try {
        await taskApi.changeStatus(task.id, 'done');
        await this.load();
      } catch (error) {
        this.error = error.message;
      }
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

.checkbox {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 8px 0;
}

.overdue {
  color: var(--color-danger);
  font-weight: 600;
}

.actions {
  text-align: right;
}
</style>
