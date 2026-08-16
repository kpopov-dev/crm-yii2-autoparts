<template>
  <div>
    <div class="header">
      <h1>Контрагенты</h1>
      <button class="btn btn--primary" type="button" @click="openCreate">Добавить контрагента</button>
    </div>

    <div class="toolbar">
      <div class="field">
        <label>Поиск</label>
        <input v-model.trim="filter.query" class="input" placeholder="Название, e-mail или ИНН" @input="debouncedLoad" />
      </div>
      <div v-if="isAdmin" class="field">
        <label>Менеджер</label>
        <select v-model="filter.managerId" class="select" @change="reload">
          <option value="">Все</option>
          <option v-for="user in users" :key="user.id" :value="user.id">{{ user.fullName }}</option>
        </select>
      </div>
      <div class="field">
        <label>Статус</label>
        <select v-model="filter.isActive" class="select" @change="reload">
          <option value="">Все</option>
          <option value="1">Активные</option>
          <option value="0">В архиве</option>
        </select>
      </div>
    </div>

    <div v-if="error" class="alert alert--error">{{ error }}</div>

    <div class="card">
      <table class="table">
        <thead>
          <tr>
            <th>Название</th>
            <th>Контакты</th>
            <th>ИНН</th>
            <th>Менеджер</th>
            <th>Статус</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="client in items" :key="client.id">
            <td>{{ client.name }}</td>
            <td>
              <div>{{ client.email || '—' }}</div>
              <div class="muted">{{ client.phone || '—' }}</div>
            </td>
            <td>{{ client.inn || '—' }}</td>
            <td>{{ client.managerName }}</td>
            <td>
              <span class="badge" :class="client.isActive ? 'badge--success' : ''">
                {{ client.isActive ? 'Активен' : 'В архиве' }}
              </span>
            </td>
            <td class="actions">
              <button class="btn btn--sm" type="button" @click="openEdit(client)">Изменить</button>
              <button
                v-if="client.isActive"
                class="btn btn--sm"
                type="button"
                @click="archive(client)"
              >
                В архив
              </button>
            </td>
          </tr>
          <tr v-if="items.length === 0">
            <td colspan="6" class="muted">Контрагенты не найдены</td>
          </tr>
        </tbody>
      </table>

      <PaginationBar :meta="meta" @change="onPage" />
    </div>

    <ModalDialog v-if="formVisible" :title="modalTitle" @close="formVisible = false">
      <div v-if="formError" class="alert alert--error">{{ formError }}</div>

      <div class="field">
        <label>Название</label>
        <input v-model.trim="form.name" class="input" />
      </div>
      <div class="field">
        <label>E-mail</label>
        <input v-model.trim="form.email" class="input" type="email" />
      </div>
      <div class="field">
        <label>Телефон</label>
        <input v-model.trim="form.phone" class="input" />
      </div>
      <div class="field">
        <label>ИНН</label>
        <input v-model.trim="form.inn" class="input" />
      </div>
      <div v-if="isAdmin" class="field">
        <label>Менеджер</label>
        <select v-model="form.managerId" class="select">
          <option v-for="user in users" :key="user.id" :value="user.id">{{ user.fullName }}</option>
        </select>
      </div>
      <div class="field">
        <label>Комментарий</label>
        <textarea v-model.trim="form.comment" class="textarea" rows="3"></textarea>
      </div>

      <template #footer>
        <button class="btn" type="button" @click="formVisible = false">Отмена</button>
        <button class="btn btn--primary" type="button" :disabled="saving" @click="submit">Сохранить</button>
      </template>
    </ModalDialog>
  </div>
</template>

<script>
import ModalDialog from '@/components/ModalDialog.vue';
import PaginationBar from '@/components/PaginationBar.vue';
import { clientApi } from '@/api/crm';

const emptyForm = () => ({
  id: null,
  name: '',
  email: '',
  phone: '',
  inn: '',
  comment: '',
  managerId: null,
});

export default {
  name: 'ClientsView',
  components: { ModalDialog, PaginationBar },
  data() {
    return {
      items: [],
      meta: null,
      page: 1,
      filter: { query: '', managerId: '', isActive: '1' },
      form: emptyForm(),
      formVisible: false,
      saving: false,
      error: '',
      formError: '',
      debounceTimer: null,
    };
  },
  computed: {
    isAdmin() {
      return this.$store.getters['auth/isAdmin'];
    },
    users() {
      return this.$store.state.dictionaries.users;
    },
    modalTitle() {
      return this.form.id ? 'Редактирование контрагента' : 'Новый контрагент';
    },
  },
  created() {
    this.load();
  },
  beforeDestroy() {
    window.clearTimeout(this.debounceTimer);
  },
  methods: {
    debouncedLoad() {
      window.clearTimeout(this.debounceTimer);
      this.debounceTimer = window.setTimeout(this.reload, 350);
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
        const response = await clientApi.list({ ...this.filter, page: this.page, limit: 20 });
        this.items = response.items;
        this.meta = response.meta;
      } catch (error) {
        this.error = error.message;
      }
    },
    openCreate() {
      this.form = emptyForm();
      this.form.managerId = this.$store.state.auth.user.id;
      this.formError = '';
      this.formVisible = true;
    },
    openEdit(client) {
      this.form = {
        id: client.id,
        name: client.name,
        email: client.email || '',
        phone: client.phone || '',
        inn: client.inn || '',
        comment: client.comment || '',
        managerId: client.managerId,
      };
      this.formError = '';
      this.formVisible = true;
    },
    async submit() {
      this.saving = true;
      this.formError = '';

      const payload = { ...this.form };
      delete payload.id;

      try {
        if (this.form.id) {
          await clientApi.update(this.form.id, payload);
        } else {
          await clientApi.create(payload);
        }

        this.formVisible = false;
        await this.load();
      } catch (error) {
        this.formError = this.describe(error);
      } finally {
        this.saving = false;
      }
    },
    async archive(client) {
      if (!window.confirm(`Перевести контрагента «${client.name}» в архив?`)) {
        return;
      }

      try {
        await clientApi.archive(client.id);
        await this.load();
      } catch (error) {
        this.error = error.message;
      }
    },
    describe(error) {
      const fields = Object.keys(error.errors || {});

      if (fields.length === 0) {
        return error.message;
      }

      return fields.map((field) => error.errors[field].join(', ')).join('; ');
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

.actions {
  display: flex;
  gap: 6px;
  justify-content: flex-end;
}
</style>
