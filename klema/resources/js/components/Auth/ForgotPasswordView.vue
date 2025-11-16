<template>
  <div class="auth-card">
    <div class="auth-card__header">
      <div class="auth-card__badge">
        <i class="fas fa-unlock-alt text-3xl text-blue-200"></i>
      </div>
      <div>
        <h1 class="auth-card__title">Forgot password?</h1>
        <p class="auth-card__subtitle">We'll send you instructions to reset it</p>
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

    <form class="auth-form" @submit.prevent="handleSubmit">
      <div class="auth-form__group">
        <label for="forgot-email" class="auth-form__label">Email Address</label>
        <div class="auth-input-wrapper">
          <i class="fas fa-envelope"></i>
          <input
            id="forgot-email"
            v-model="email"
            class="auth-input"
            type="email"
            placeholder="you@example.com"
            required
          >
        </div>
      </div>

      <button type="submit" class="auth-button" :disabled="loading">
        <i v-if="loading" class="fas fa-spinner fa-spin"></i>
        <i v-else class="fas fa-paper-plane"></i>
        <span>{{ loading ? 'Sending...' : 'Send reset instructions' }}</span>
      </button>
    </form>

    <div class="auth-footer" style="display: flex; flex-direction: column; gap: 12px;">
      <a href="#" class="auth-link" @click.prevent="emit('navigate', { view: 'reset', email, token: '' })">
        Already have a reset token? Enter it manually
      </a>
      <a href="#" class="auth-link" @click.prevent="emit('navigate', { view: 'login', email })">
        Back to sign in
      </a>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
  email: {
    type: String,
    default: ''
  }
});

const emit = defineEmits(['navigate']);

const email = ref(props.email);
const loading = ref(false);
const statusMessage = ref('');
const errorMessage = ref('');

watch(
  () => props.email,
  (next) => {
    if (next) {
      email.value = next;
    }
  }
);

const handleSubmit = async () => {
  loading.value = true;
  statusMessage.value = '';
  errorMessage.value = '';

  try {
    const { data } = await axios.post('/api/auth/password/forgot', {
      email: email.value
    });

    statusMessage.value = data?.message ?? 'Check your email for reset instructions.';
  } catch (error) {
    const message = error.response?.data?.message;
    const validationErrors = error.response?.data?.errors;

    if (validationErrors) {
      errorMessage.value = Object.values(validationErrors).flat().join(' ');
    } else if (message) {
      errorMessage.value = message;
    } else {
      errorMessage.value = 'Unable to send reset email. Please try again.';
    }
  } finally {
    loading.value = false;
  }
};
</script>

