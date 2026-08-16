<template>
  <div class="login">
    <form class="login__card card" @submit.prevent="onSubmit">
      <h1>АвтоДеталь</h1>
      <p class="muted login__subtitle">CRM оптового отдела продаж запчастей</p>

      <div v-if="error" class="alert alert--error">{{ error }}</div>

      <div class="field">
        <label for="email">E-mail</label>
        <input id="email" v-model.trim="email" class="input" type="email" autocomplete="username" required />
      </div>

      <div class="field">
        <label for="password">Пароль</label>
        <input
          id="password"
          v-model="password"
          class="input"
          type="password"
          autocomplete="current-password"
          required
        />
      </div>

      <button class="btn btn--primary login__submit" type="submit" :disabled="loading">
        {{ loading ? 'Проверяем…' : 'Войти' }}
      </button>

      <p class="muted login__hint">
        Демо-доступ: admin@crm.local / Admin123!
      </p>
    </form>
  </div>
</template>

<script>
export default {
  name: 'LoginView',
  data() {
    return {
      email: '',
      password: '',
    };
  },
  computed: {
    loading() {
      return this.$store.state.auth.loading;
    },
    error() {
      return this.$store.state.auth.error;
    },
  },
  methods: {
    async onSubmit() {
      const success = await this.$store.dispatch('auth/login', {
        email: this.email,
        password: this.password,
      });

      if (success) {
        await this.$store.dispatch('dictionaries/load');
        this.$router.push(this.$route.query.redirect || '/');
      }
    },
  },
};
</script>

<style scoped>
.login {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #1d2433;
}

.login__card {
  width: 360px;
}

.login__submit {
  width: 100%;
  justify-content: center;
}

.login__subtitle {
  margin: -8px 0 4px;
  font-size: 13px;
}

.login__hint {
  font-size: 12px;
  margin: 12px 0 0;
  text-align: center;
}
</style>
