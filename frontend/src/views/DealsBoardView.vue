<template>
  <div>
    <div class="header">
      <h1>Воронка заказов</h1>
      <button class="btn btn--primary" type="button" @click="openCreate">Новый заказ</button>
    </div>

    <div class="toolbar">
      <div class="field">
        <label>Поиск</label>
        <input v-model.trim="filter.query" class="input" placeholder="Номер или название" @input="debouncedLoad" />
      </div>
      <div v-if="isAdmin" class="field">
        <label>Ответственный</label>
        <select v-model="filter.responsibleId" class="select" @change="load">
          <option value="">Все</option>
          <option v-for="user in users" :key="user.id" :value="user.id">{{ user.fullName }}</option>
        </select>
      </div>
      <div class="field">
        <label>&nbsp;</label>
        <label class="checkbox">
          <input v-model="filter.onlyOpen" type="checkbox" @change="load" />
          Только открытые
        </label>
      </div>
    </div>

    <div v-if="error" class="alert alert--error">{{ error }}</div>

    <div class="board">
      <div
        v-for="column in columns"
        :key="column.stage"
        class="board__column"
        :class="{ 'board__column--target': dragOverStage === column.stage }"
        @dragover.prevent="dragOverStage = column.stage"
        @dragleave="dragOverStage = ''"
        @drop="onDrop(column.stage)"
      >
        <header class="board__header">
          <span>{{ column.label }}</span>
          <span class="badge">{{ column.count }}</span>
        </header>
        <div class="board__amount muted">{{ money(column.amount) }}</div>

        <article
          v-for="deal in column.items"
          :key="deal.id"
          class="deal"
          draggable="true"
          @dragstart="onDragStart(deal)"
          @dragend="onDragEnd"
        >
          <router-link :to="{ name: 'deal', params: { id: deal.id } }" class="deal__title">
            {{ deal.title }}
          </router-link>
          <div class="deal__meta muted">{{ deal.number }} · {{ deal.clientName }}</div>
          <div class="deal__amount">{{ money(deal.amount, deal.currency) }}</div>
          <div class="deal__footer muted">
            <span>{{ deal.responsibleName }}</span>
            <span v-if="deal.openTasks > 0" class="badge badge--warning">{{ deal.openTasks }}</span>
          </div>
        </article>

        <p v-if="column.items.length === 0" class="board__empty muted">Нет сделок</p>
      </div>
    </div>

    <ModalDialog v-if="createVisible" title="Новый заказ" @close="createVisible = false">
      <div v-if="formError" class="alert alert--error">{{ formError }}</div>

      <div class="field">
        <label>Название</label>
        <input v-model.trim="form.title" class="input" />
      </div>
      <div class="field">
        <label>Контрагент</label>
        <select v-model="form.clientId" class="select">
          <option :value="null">Выберите контрагента</option>
          <option v-for="client in clients" :key="client.id" :value="client.id">{{ client.name }}</option>
        </select>
      </div>
      <div class="field">
        <label>Сумма</label>
        <input v-model.number="form.amount" class="input" type="number" min="0" step="100" />
      </div>
      <div class="field">
        <label>Валюта</label>
        <select v-model="form.currency" class="select">
          <option value="RUB">RUB</option>
          <option value="USD">USD</option>
          <option value="EUR">EUR</option>
          <option value="CNY">CNY</option>
        </select>
      </div>
      <div class="field">
        <label>Описание</label>
        <textarea v-model.trim="form.description" class="textarea" rows="3"></textarea>
      </div>

      <template #footer>
        <button class="btn" type="button" @click="createVisible = false">Отмена</button>
        <button class="btn btn--primary" type="button" :disabled="saving" @click="submit">Создать</button>
      </template>
    </ModalDialog>
  </div>
</template>

<script>
import ModalDialog from '@/components/ModalDialog.vue';
import { clientApi, dealApi } from '@/api/crm';
import { formatMoney } from '@/utils/format';

export default {
  name: 'DealsBoardView',
  components: { ModalDialog },
  data() {
    return {
      columns: [],
      clients: [],
      filter: { query: '', responsibleId: '', onlyOpen: true },
      draggedDeal: null,
      dragOverStage: '',
      createVisible: false,
      saving: false,
      error: '',
      formError: '',
      form: { title: '', clientId: null, amount: 0, currency: 'RUB', description: '' },
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
  },
  created() {
    this.load();
  },
  beforeDestroy() {
    window.clearTimeout(this.debounceTimer);
  },
  methods: {
    money: (value, currency) => formatMoney(value, currency || 'RUB'),
    debouncedLoad() {
      window.clearTimeout(this.debounceTimer);
      this.debounceTimer = window.setTimeout(this.load, 350);
    },
    async load() {
      this.error = '';

      try {
        const response = await dealApi.board(this.filter);
        this.columns = response.columns;
      } catch (error) {
        this.error = error.message;
      }
    },
    onDragStart(deal) {
      this.draggedDeal = deal;
    },
    onDragEnd() {
      this.draggedDeal = null;
      this.dragOverStage = '';
    },
    async onDrop(stage) {
      const deal = this.draggedDeal;
      this.dragOverStage = '';
      this.draggedDeal = null;

      if (!deal || deal.stage === stage) {
        return;
      }

      this.error = '';

      try {
        await dealApi.changeStage(deal.id, stage, 'Перенос на канбан-доске');
        await this.load();
        await this.$store.dispatch('notifications/fetchUnread');
      } catch (error) {
        this.error = error.message;
      }
    },
    async openCreate() {
      this.formError = '';
      this.createVisible = true;

      if (this.clients.length === 0) {
        const response = await clientApi.list({ limit: 100, isActive: 1 });
        this.clients = response.items;
      }
    },
    async submit() {
      this.saving = true;
      this.formError = '';

      try {
        await dealApi.create(this.form);
        this.createVisible = false;
        this.form = { title: '', clientId: null, amount: 0, currency: 'RUB', description: '' };
        await this.load();
      } catch (error) {
        this.formError = error.message;
      } finally {
        this.saving = false;
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
  font-size: 14px;
  color: var(--color-text);
  padding: 8px 0;
}

.board {
  display: grid;
  grid-auto-flow: column;
  grid-auto-columns: minmax(240px, 1fr);
  gap: 12px;
  overflow-x: auto;
  padding-bottom: 12px;
}

.board__column {
  background: #eef1f7;
  border-radius: var(--radius);
  padding: 12px;
  min-height: 300px;
  border: 2px solid transparent;
  transition: border-color 0.15s ease;
}

.board__column--target {
  border-color: var(--color-primary);
}

.board__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-weight: 600;
}

.board__amount {
  font-size: 12px;
  margin-bottom: 10px;
}

.board__empty {
  font-size: 12px;
  text-align: center;
  margin-top: 20px;
}

.deal {
  background: #fff;
  border-radius: 8px;
  padding: 10px;
  margin-bottom: 8px;
  box-shadow: var(--shadow);
  cursor: grab;
}

.deal:active {
  cursor: grabbing;
}

.deal__title {
  font-weight: 600;
  color: var(--color-text);
  display: block;
}

.deal__meta {
  font-size: 12px;
  margin: 2px 0 6px;
}

.deal__amount {
  font-variant-numeric: tabular-nums;
  font-weight: 600;
}

.deal__footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 12px;
  margin-top: 6px;
}
</style>
