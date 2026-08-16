<template>
  <div v-if="deal">
    <div class="header">
      <div>
        <h1>{{ deal.title }}</h1>
        <p class="muted">{{ deal.number }} · {{ deal.clientName }}</p>
      </div>
      <router-link class="btn" to="/deals">К воронке</router-link>
    </div>

    <div v-if="error" class="alert alert--error">{{ error }}</div>

    <div class="columns">
      <section class="card">
        <h2>Параметры заказа</h2>
        <dl class="details">
          <dt>Стадия</dt>
          <dd><span class="badge">{{ deal.stageLabel }}</span></dd>
          <dt>Сумма</dt>
          <dd>{{ money(deal.amount, deal.currency) }}</dd>
          <dt>Ответственный</dt>
          <dd>{{ deal.responsibleName }}</dd>
          <dt>Создана</dt>
          <dd>{{ dateTime(deal.createdAt) }}</dd>
          <dt>Закрыта</dt>
          <dd>{{ dateTime(deal.closedAt) }}</dd>
        </dl>
        <p v-if="deal.description">{{ deal.description }}</p>

        <div v-if="deal.availableStages.length" class="stage-actions">
          <h2>Сменить стадию</h2>
          <input v-model.trim="comment" class="input" placeholder="Комментарий к переходу" />
          <div class="stage-actions__buttons">
            <button
              v-for="stage in deal.availableStages"
              :key="stage.code"
              class="btn btn--sm"
              type="button"
              :disabled="saving"
              @click="changeStage(stage.code)"
            >
              {{ stage.label }}
            </button>
          </div>
        </div>
      </section>

      <section class="card">
        <h2>История стадий</h2>
        <ul class="timeline">
          <li v-for="entry in deal.history" :key="entry.id">
            <div class="timeline__title">
              <span v-if="entry.stageFromLabel">{{ entry.stageFromLabel }} → </span>
              <strong>{{ entry.stageToLabel }}</strong>
            </div>
            <div class="muted timeline__meta">{{ entry.userName }} · {{ dateTime(entry.createdAt) }}</div>
            <div v-if="entry.comment" class="timeline__comment">{{ entry.comment }}</div>
          </li>
        </ul>
      </section>
    </div>
  </div>
</template>

<script>
import { dealApi } from '@/api/crm';
import { formatDateTime, formatMoney } from '@/utils/format';

export default {
  name: 'DealView',
  props: {
    id: { type: [String, Number], required: true },
  },
  data() {
    return {
      deal: null,
      comment: '',
      saving: false,
      error: '',
    };
  },
  created() {
    this.load();
  },
  methods: {
    money: (value, currency) => formatMoney(value, currency),
    dateTime: (value) => formatDateTime(value),
    async load() {
      this.error = '';

      try {
        this.deal = await dealApi.view(this.id);
      } catch (error) {
        this.error = error.message;
      }
    },
    async changeStage(stage) {
      this.saving = true;
      this.error = '';

      try {
        this.deal = await dealApi.changeStage(this.id, stage, this.comment);
        this.comment = '';
        await this.$store.dispatch('notifications/fetchUnread');
      } catch (error) {
        this.error = error.message;
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
  align-items: flex-start;
}

.header p {
  margin: -8px 0 16px;
}

.columns {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
  gap: 16px;
}

.details {
  display: grid;
  grid-template-columns: 140px 1fr;
  gap: 6px 12px;
  margin: 0 0 12px;
}

.details dt {
  color: var(--color-muted);
}

.details dd {
  margin: 0;
}

.stage-actions {
  border-top: 1px solid var(--color-border);
  padding-top: 12px;
  margin-top: 12px;
}

.stage-actions__buttons {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 10px;
}

.timeline {
  list-style: none;
  margin: 0;
  padding: 0;
}

.timeline li {
  border-left: 2px solid var(--color-border);
  padding: 0 0 14px 14px;
  position: relative;
}

.timeline li::before {
  content: '';
  position: absolute;
  left: -5px;
  top: 6px;
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: var(--color-primary);
}

.timeline__meta {
  font-size: 12px;
}

.timeline__comment {
  font-size: 13px;
  margin-top: 2px;
}
</style>
