<template>
  <div class="auth-card">
    <div class="auth-card__header">
      <div class="auth-card__badge">
        <i class="fas fa-seedling text-3xl text-blue-200"></i>
      </div>
      <div>
        <h1 class="auth-card__title">Join KLEMA</h1>
        <p class="auth-card__subtitle">Create your account to get started</p>
      </div>
    </div>

    <div v-if="successMessage" class="auth-alert auth-alert--success">
      <i class="fas fa-check-circle"></i>
      <span>{{ successMessage }}</span>
    </div>

    <div v-if="errorMessage" class="auth-alert auth-alert--error">
      <i class="fas fa-exclamation-circle"></i>
      <span>{{ errorMessage }}</span>
    </div>

    <form class="auth-form" @submit.prevent="handleRegister">
      <div class="auth-form__group">
        <label for="name" class="auth-form__label">Full Name</label>
        <div class="auth-input-wrapper">
          <i class="fas fa-user"></i>
          <input
            id="name"
            v-model="form.name"
            class="auth-input"
            type="text"
            placeholder="Enter your full name"
            required
            autocomplete="name"
          >
        </div>
      </div>

      <div class="auth-form__group">
        <label for="email" class="auth-form__label">Email Address</label>
        <div class="auth-input-wrapper">
          <i class="fas fa-envelope"></i>
          <input
            id="email"
            v-model="form.email"
            class="auth-input"
            type="email"
            placeholder="Enter your email"
            required
            autocomplete="email"
          >
        </div>
      </div>

      <div class="auth-form__group">
        <label for="password" class="auth-form__label">Password</label>
        <div class="auth-input-wrapper">
          <i class="fas fa-lock"></i>
          <input
            id="password"
            v-model="form.password"
            :type="showPassword ? 'text' : 'password'"
            class="auth-input"
            placeholder="Create a password"
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
        <label for="password_confirmation" class="auth-form__label">Confirm Password</label>
        <div class="auth-input-wrapper">
          <i class="fas fa-lock"></i>
          <input
            id="password_confirmation"
            v-model="form.password_confirmation"
            :type="showConfirm ? 'text' : 'password'"
            class="auth-input"
            placeholder="Confirm your password"
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
        <i v-else class="fas fa-user-plus"></i>
        <span>{{ loading ? 'Creating account...' : 'Create Account' }}</span>
      </button>

      <div class="auth-footer">
        <span>Already have an account?</span>
        <a href="#" class="auth-link" @click.prevent="emit('navigate', { view: 'login', email: form.email })">Sign in</a>
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
  }
});

const emit = defineEmits(['navigate']);

const form = reactive({
  name: '',
  email: props.email || '',
  password: '',
  password_confirmation: '',
  role: 'farmer'
});

const loading = ref(false);
const showPassword = ref(false);
const showConfirm = ref(false);
const errorMessage = ref('');
const successMessage = ref('');

watch(
  () => props.email,
  (next) => {
    if (next) {
      form.email = next;
    }
  }
);

const handleRegister = async () => {
  loading.value = true;
  errorMessage.value = '';
  successMessage.value = '';

  try {
    const { data } = await axios.post('/api/auth/register', {
      name: form.name,
      email: form.email,
      password: form.password,
      password_confirmation: form.password_confirmation,
      role: form.role
    });

    successMessage.value = data?.message ?? 'Registration successful! Please verify your email.';
    emit('navigate', { view: 'verify', email: form.email, successMessage: successMessage.value });
  } catch (error) {
    const message = error.response?.data?.message;
    const validationErrors = error.response?.data?.errors;

    if (validationErrors) {
      errorMessage.value = Object.values(validationErrors).flat().join(' ');
    } else if (message) {
      errorMessage.value = message;
    } else {
      errorMessage.value = 'Unable to create account. Please try again.';
    }
  } finally {
    loading.value = false;
  }
};
</script>

