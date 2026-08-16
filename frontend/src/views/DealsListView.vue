<template>
  <div>
    <h1>Заказы</h1>

    <div class="toolbar">
      <div class="field">
        <label>Поиск</label>
        <input v-model.trim="filter.query" class="input" placeholder="Номер или название" @input="debouncedLoad" />
      </div>
      <div class="field">
        <label>Стадия</label>
        <select v-model="filter.stage" class="select" @change="reload">
          <option value="">Все</option>
          <option v-for="stage in stages" :key="stage.code" :value="stage.code">{{ stage.label }}</option>
        </select>
      </div>
      <div v-if="isAdmin" class="field">
        <label>Ответственный</label>
        <select v-model="filter.responsibleId" class="select" @change="reload">
          <option value="">Все</option>
          <option v-for="user in users" :key="user.id" :value="user.id">{{ user.fullName }}</option>
        </select>
      </div>
      <div class="field">
        <label>Сортировка</label>
        <select v-model="filter.sort" class="select" @change="reload">
          <option value="-id">Сначала новые</option>
          <option value="-amount">По сумме, убывание</option>
          <option value="amount">По сумме, возрастание</option>
          <option value="-updatedAt">По дате изменения</option>
        </select>
      </div>
    </div>

    <div v-if="error" class="alert alert--error">{{ error }}</div>

    <div class="card">
      <table class="table">
        <thead>
          <tr>
            <th>Номер</th>
            <th>Название</th>
            <th>Контрагент</th>
            <th>Стадия</th>
            <th>Сумма</th>
            <th>Ответственный</th>
            <th>Создана</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="deal in items" :key="deal.id">
            <td>
              <router-link :to="{ name: 'deal', params: { id: deal.id } }">{{ deal.number }}</router-link>
            </td>
            <td>{{ deal.title }}</td>
            <td>{{ deal.clientName }}</td>
            <td><span class="badge" :class="stageClass(deal.stage)">{{ deal.stageLabel }}</span></td>
            <td>{{ money(deal.amount, deal.currency) }}</td>
            <td>{{ deal.responsibleName }}</td>
            <td>{{ date(deal.createdAt) }}</td>
          </tr>
          <tr v-if="items.length === 0">
            <td colspan="7" class="muted">Заказы не найдены</td>
          </tr>
        </tbody>
      </table>

      <PaginationBar :meta="meta" @change="onPage" />
    </div>
  </div>
</template>

<script>
import PaginationBar from '@/components/PaginationBar.vue';
import { dealApi } from '@/api/crm';
import { formatDate, formatMoney } from '@/utils/format';

export default {
  name: 'DealsListView',
  components: { PaginationBar },
  data() {
    return {
      items: [],
      meta: null,
      page: 1,
      filter: { query: '', stage: '', responsibleId: '', sort: '-id' },
      error: '',
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
    stages() {
      return this.$store.state.dictionaries.stages;
    },
  },
  created() {
    this.load();
  },
  beforeDestroy() {
    window.clearTimeout(this.debounceTimer);
  },
  methods: {
    money: (value, currency) => formatMoney(value, currency),
    date: (value) => formatDate(value),
    stageClass(stage) {
      if (stage === 'won') {
        return 'badge--success';
      }

      if (stage === 'lost') {
        return 'badge--danger';
      }

      return '';
    },
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
        const response = await dealApi.list({ ...this.filter, page: this.page, limit: 20 });
        this.items = response.items;
        this.meta = response.meta;
      } catch (error) {
        this.error = error.message;
      }
    },
  },
};
</script>
