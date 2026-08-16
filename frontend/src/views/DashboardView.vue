<template>
  <div>
    <h1>Дашборд оптовых продаж</h1>

    <div class="toolbar">
      <div class="field">
        <label>Период с</label>
        <input v-model="from" class="input" type="date" @change="load" />
      </div>
      <div class="field">
        <label>по</label>
        <input v-model="to" class="input" type="date" @change="load" />
      </div>
      <div v-if="isAdmin" class="field">
        <label>Менеджер</label>
        <select v-model="managerId" class="select" @change="load">
          <option value="">Все</option>
          <option v-for="user in users" :key="user.id" :value="user.id">{{ user.fullName }}</option>
        </select>
      </div>
    </div>

    <div v-if="error" class="alert alert--error">{{ error }}</div>

    <div class="stats">
      <StatCard label="Сделок в работе" :value="summary.active" :hint="openAmountLabel" />
      <StatCard label="Выиграно" :value="summary.won" :hint="wonAmountLabel" />
      <StatCard label="Конверсия" :value="conversionLabel" hint="доля выигранных сделок" />
      <StatCard label="Средний чек" :value="averageCheckLabel" hint="по отгруженным заказам" />
    </div>

    <div class="grid grid--two">
      <section class="card">
        <h2>Воронка заказов</h2>
        <FunnelChart :items="funnel" />
      </section>

      <section class="card">
        <h2>Выручка по месяцам</h2>
        <RevenueChart :items="revenue" />
        <div class="legend muted">
          <span v-for="item in revenue" :key="item.period">{{ item.period }}</span>
        </div>
      </section>
    </div>

    <section class="card section">
      <h2>Результаты менеджеров</h2>
      <table class="table">
        <thead>
          <tr>
            <th>Менеджер</th>
            <th>Сделок</th>
            <th>Выиграно</th>
            <th>Проиграно</th>
            <th>Сумма выигранных</th>
            <th>Конверсия</th>
            <th>Средний цикл</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in managers" :key="row.managerId">
            <td>{{ row.managerName }}</td>
            <td>{{ row.dealsTotal }}</td>
            <td>{{ row.dealsWon }}</td>
            <td>{{ row.dealsLost }}</td>
            <td>{{ money(row.wonAmount) }}</td>
            <td>{{ percent(row.conversion) }}</td>
            <td>{{ row.avgCycleDays }} дн.</td>
          </tr>
        </tbody>
      </table>
    </section>

    <section v-if="overdue.length" class="card section">
      <h2>Просроченные задачи</h2>
      <table class="table">
        <thead>
          <tr>
            <th>Менеджер</th>
            <th>Просрочено</th>
            <th>Самая старая</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in overdue" :key="row.managerId">
            <td>{{ row.managerName }}</td>
            <td><span class="badge badge--danger">{{ row.overdueCount }}</span></td>
            <td>{{ date(row.oldestDueAt) }}</td>
          </tr>
        </tbody>
      </table>
    </section>
  </div>
</template>

<script>
import FunnelChart from '@/components/FunnelChart.vue';
import RevenueChart from '@/components/RevenueChart.vue';
import StatCard from '@/components/StatCard.vue';
import { reportApi } from '@/api/crm';
import { formatDate, formatMoney, formatPercent } from '@/utils/format';

export default {
  name: 'DashboardView',
  components: { FunnelChart, RevenueChart, StatCard },
  data() {
    return {
      from: '',
      to: '',
      managerId: '',
      summary: { active: 0, won: 0, lost: 0, total: 0, wonAmount: 0, conversion: 0, averageCheck: 0 },
      funnel: [],
      revenue: [],
      managers: [],
      overdue: [],
      error: '',
    };
  },
  computed: {
    isAdmin() {
      return this.$store.getters['auth/isAdmin'];
    },
    users() {
      return this.$store.state.dictionaries.users;
    },
    conversionLabel() {
      return formatPercent(this.summary.conversion);
    },
    averageCheckLabel() {
      return formatMoney(this.summary.averageCheck, 'RUB');
    },
    wonAmountLabel() {
      return formatMoney(this.summary.wonAmount, 'RUB');
    },
    openAmountLabel() {
      const open = this.funnel
        .filter((item) => item.stage !== 'won' && item.stage !== 'lost')
        .reduce((acc, item) => acc + item.amount, 0);

      return formatMoney(open, 'RUB');
    },
  },
  created() {
    this.load();
  },
  methods: {
    money: (value) => formatMoney(value, 'RUB'),
    percent: (value) => formatPercent(value),
    date: (value) => formatDate(value),
    async load() {
      this.error = '';

      try {
        const response = await reportApi.dashboard({
          from: this.from,
          to: this.to,
          managerId: this.managerId,
        });

        this.summary = response.summary;
        this.funnel = response.funnel;
        this.revenue = response.revenueByMonth;
        this.managers = response.managers;
        this.overdue = response.overdueTasks;
        this.from = this.from || response.range.from;
        this.to = this.to || response.range.to;
      } catch (error) {
        this.error = error.message;
      }
    },
  },
};
</script>

<style scoped>
.stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
  margin-bottom: 16px;
}

.grid--two {
  grid-template-columns: repeat(auto-fit, minmax(360px, 1fr));
}

.section {
  margin-top: 16px;
}

.legend {
  display: flex;
  justify-content: space-between;
  font-size: 11px;
  margin-top: 4px;
}
</style>
