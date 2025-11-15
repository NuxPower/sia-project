<template>
  <div class="auth-card">
    <div class="auth-card__header">
      <div class="auth-card__badge">
        <i class="fas fa-envelope-open-text text-3xl text-blue-200"></i>
      </div>
      <div>
        <h1 class="auth-card__title">Verify your email</h1>
        <p class="auth-card__subtitle">
          We sent a confirmation link to complete your registration
        </p>
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

    <div class="auth-form__group">
      <p style="color: rgba(226, 232, 240, 0.85); text-align: center;">
        Please check your email for the verification link.
        If you didn't receive it, you can resend the link below.
      </p>
    </div>

    <form class="auth-form" @submit.prevent="handleResend">
      <div class="auth-form__group">
        <label class="auth-form__label">Email Address</label>
        <div class="auth-input-wrapper">
          <i class="fas fa-envelope"></i>
          <input
            v-model="emailInput"
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
        <span>{{ loading ? 'Sending...' : 'Resend verification email' }}</span>
      </button>
    </form>

    <div class="auth-footer">
      <span>Need to use a different account?</span>
      <a href="#" class="auth-link" @click.prevent="emit('navigate', { view: 'login', email: emailInput })">
        Return to sign in
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

const emailInput = ref(props.email);
const loading = ref(false);
const statusMessage = ref('');
const errorMessage = ref('');

watch(
  () => props.email,
  (next) => {
    if (next) {
      emailInput.value = next;
    }
  }
);

const handleResend = async () => {
  loading.value = true;
  statusMessage.value = '';
  errorMessage.value = '';

  try {
    const { data } = await axios.post('/api/auth/email/resend', {
      email: emailInput.value
    });

    statusMessage.value = data?.message ?? 'Verification email sent successfully.';
  } catch (error) {
    const message = error.response?.data?.message;
    const validationErrors = error.response?.data?.errors;

    if (validationErrors) {
      errorMessage.value = Object.values(validationErrors).flat().join(' ');
    } else if (message) {
      errorMessage.value = message;
    } else {
      errorMessage.value = 'Failed to resend verification email.';
    }
  } finally {
    loading.value = false;
  }
};
</script>

