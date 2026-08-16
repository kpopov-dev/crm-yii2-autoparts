<template>
  <div class="stat card">
    <div class="stat__label">{{ label }}</div>
    <div class="stat__value">{{ value }}</div>
    <div v-if="hint" class="stat__hint muted">{{ hint }}</div>
  </div>
</template>

<script>
export default {
  name: 'StatCard',
  props: {
    label: { type: String, required: true },
    value: { type: [String, Number], required: true },
    hint: { type: String, default: '' },
  },
};
</script>

<style scoped>
.stat__label {
  font-size: 13px;
  color: var(--color-muted);
}

.stat__value {
  font-size: 24px;
  font-weight: 700;
  margin-top: 4px;
}

.stat__hint {
  font-size: 12px;
  margin-top: 4px;
}
</style>
