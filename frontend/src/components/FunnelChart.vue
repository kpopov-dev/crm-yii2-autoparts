<template>
  <div class="funnel">
    <div v-for="item in normalized" :key="item.stage" class="funnel__row">
      <div class="funnel__label">{{ item.label }}</div>
      <div class="funnel__bar">
        <div class="funnel__fill" :style="{ width: item.width + '%' }"></div>
      </div>
      <div class="funnel__value">
        {{ item.count }}
        <span class="muted">{{ item.amountLabel }}</span>
      </div>
    </div>
  </div>
</template>

<script>
import { formatMoney } from '@/utils/format';

export default {
  name: 'FunnelChart',
  props: {
    items: { type: Array, default: () => [] },
  },
  computed: {
    normalized() {
      const max = this.items.reduce((acc, item) => Math.max(acc, item.count), 0);

      return this.items.map((item) => ({
        stage: item.stage,
        label: item.label,
        count: item.count,
        amountLabel: formatMoney(item.amount, 'RUB'),
        width: max > 0 ? Math.round((item.count / max) * 100) : 0,
      }));
    },
  },
};
</script>

<style scoped>
.funnel__row {
  display: grid;
  grid-template-columns: 150px 1fr 180px;
  align-items: center;
  gap: 12px;
  margin-bottom: 10px;
}

.funnel__bar {
  background: #eef1f7;
  border-radius: 6px;
  height: 18px;
  overflow: hidden;
}

.funnel__fill {
  background: linear-gradient(90deg, #2f6fed, #5b9bff);
  height: 100%;
  transition: width 0.3s ease;
}

.funnel__value {
  text-align: right;
  font-variant-numeric: tabular-nums;
}

.funnel__value .muted {
  margin-left: 8px;
  font-size: 12px;
}
</style>
