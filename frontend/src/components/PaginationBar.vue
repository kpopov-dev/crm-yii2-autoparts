<template>
  <div v-if="meta && meta.pageCount > 1" class="pagination">
    <button class="btn btn--sm" type="button" :disabled="meta.page <= 1" @click="go(meta.page - 1)">
      Назад
    </button>
    <span class="muted">Страница {{ meta.page }} из {{ meta.pageCount }}, всего {{ meta.total }}</span>
    <button
      class="btn btn--sm"
      type="button"
      :disabled="meta.page >= meta.pageCount"
      @click="go(meta.page + 1)"
    >
      Вперёд
    </button>
  </div>
</template>

<script>
export default {
  name: 'PaginationBar',
  props: {
    meta: { type: Object, default: null },
  },
  methods: {
    go(page) {
      this.$emit('change', page);
    },
  },
};
</script>

<style scoped>
.pagination {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-top: 14px;
}
</style>
