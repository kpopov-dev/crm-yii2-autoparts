<template>
  <div class="overlay" @click.self="$emit('close')">
    <div class="modal">
      <header class="modal__header">
        <h2>{{ title }}</h2>
        <button class="modal__close" type="button" @click="$emit('close')">×</button>
      </header>
      <div class="modal__body">
        <slot />
      </div>
      <footer class="modal__footer">
        <slot name="footer" />
      </footer>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ModalDialog',
  props: {
    title: { type: String, default: '' },
  },
};
</script>

<style scoped>
.overlay {
  position: fixed;
  inset: 0;
  background: rgba(29, 36, 51, 0.45);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 50;
}

.modal {
  background: #fff;
  border-radius: 12px;
  width: 480px;
  max-width: calc(100vw - 32px);
  max-height: calc(100vh - 64px);
  display: flex;
  flex-direction: column;
}

.modal__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 20px;
  border-bottom: 1px solid var(--color-border);
}

.modal__header h2 {
  margin: 0;
}

.modal__close {
  border: none;
  background: none;
  font-size: 22px;
  line-height: 1;
  cursor: pointer;
  color: var(--color-muted);
}

.modal__body {
  padding: 20px;
  overflow-y: auto;
}

.modal__footer {
  padding: 14px 20px;
  border-top: 1px solid var(--color-border);
  display: flex;
  justify-content: flex-end;
  gap: 8px;
}
</style>
