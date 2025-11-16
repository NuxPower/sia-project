<template>
  <div class="auth-card">
    <div class="auth-card__header">
      <div class="auth-card__badge">
        <i class="fas fa-key text-3xl text-blue-200"></i>
      </div>
      <div>
        <h1 class="auth-card__title">Reset your password</h1>
        <p class="auth-card__subtitle">Enter the code we emailed you and choose a new password</p>
      </div>
    </div>

    <div v-if="statusMessage" class="auth-alert auth-alert--success">
      <i class="fas fa-check-circle"></i>
      <span>{{ statusMessage }}</span>
    </div>

    <div v-if="errorMessage" class="auth-alert auth-alert--error">
      <i class="fas fa-exclamation-circle"></i>
      <span>{{ errorMessage }}</span>
    </div>

    <form class="auth-form" @submit.prevent="handleReset">
      <div class="auth-form__group">
        <label class="auth-form__label">Reset Token</label>
        <div class="auth-input-wrapper">
          <i class="fas fa-hashtag"></i>
          <input
            v-model="form.token"
            class="auth-input"
            type="text"
            placeholder="Paste the token from your email"
            required
          >
        </div>
      </div>

      <div class="auth-form__group">
        <label class="auth-form__label">Email Address</label>
        <div class="auth-input-wrapper">
          <i class="fas fa-envelope"></i>
          <input
            v-model="form.email"
            class="auth-input"
            type="email"
            placeholder="you@example.com"
            required
          >
        </div>
      </div>

      <div class="auth-form__group">
        <label class="auth-form__label">New Password</label>
        <div class="auth-input-wrapper">
          <i class="fas fa-lock"></i>
          <input
            v-model="form.password"
            :type="showPassword ? 'text' : 'password'"
            class="auth-input"
            placeholder="Enter new password"
            required
            autocomplete="new-password"
          >
          <button
            type="button"
            class="password-toggle"
            @click="showPassword = !showPassword"
          >
            <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
          </button>
        </div>
      </div>

      <div class="auth-form__group">
        <label class="auth-form__label">Confirm Password</label>
        <div class="auth-input-wrapper">
          <i class="fas fa-lock"></i>
          <input
            v-model="form.password_confirmation"
            :type="showConfirm ? 'text' : 'password'"
            class="auth-input"
            placeholder="Confirm new password"
            required
            autocomplete="new-password"
          >
          <button
            type="button"
            class="password-toggle"
            @click="showConfirm = !showConfirm"
          >
            <i :class="showConfirm ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
          </button>
        </div>
      </div>

      <button type="submit" class="auth-button" :disabled="loading">
        <i v-if="loading" class="fas fa-spinner fa-spin"></i>
        <i v-else class="fas fa-sync"></i>
        <span>{{ loading ? 'Resetting password...' : 'Reset Password' }}</span>
      </button>

      <div class="auth-footer">
        <a href="#" class="auth-link" @click.prevent="emit('navigate', { view: 'forgot', email: form.email })">
          Didn't receive a token? Send reset instructions again
        </a>
      </div>
    </form>
  </div>
</template>

<script setup>
import { reactive, ref, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
  email: {
    type: String,
    default: ''
  },
  token: {
    type: String,
    default: ''
  }
});

const emit = defineEmits(['navigate']);

const form = reactive({
  token: props.token,
  email: props.email,
  password: '',
  password_confirmation: ''
});

const showPassword = ref(false);
const showConfirm = ref(false);
const loading = ref(false);
const errorMessage = ref('');
const statusMessage = ref('');

watch(
  () => props.email,
  (next) => {
    if (next) {
      form.email = next;
    }
  }
);

watch(
  () => props.token,
  (next) => {
    if (next) {
      form.token = next;
    }
  }
);

const handleReset = async () => {
  loading.value = true;
  errorMessage.value = '';
  statusMessage.value = '';

  try {
    const { data } = await axios.post('/api/auth/password/reset', {
      token: form.token,
      email: form.email,
      password: form.password,
      password_confirmation: form.password_confirmation
    });

    statusMessage.value = data?.message ?? 'Password reset successfully.';
    emit('navigate', {
      view: 'login',
      email: form.email,
      successMessage: statusMessage.value
    });
  } catch (error) {
    const message = error.response?.data?.message;
    const validationErrors = error.response?.data?.errors;

    if (validationErrors) {
      errorMessage.value = Object.values(validationErrors).flat().join(' ');
    } else if (message) {
      errorMessage.value = message;
    } else {
      errorMessage.value = 'Failed to reset password. Please check your token and try again.';
    }
  } finally {
    loading.value = false;
  }
};
</script>

