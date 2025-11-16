<template>
  <div class="auth-wrapper">
    <div class="auth-content">
      <div class="auth-card">
        <div v-if="statusMessage" class="auth-alert auth-alert--success">
          <i class="fas fa-check-circle"></i>
          <span>{{ statusMessage }}</span>
        </div>

        <div v-if="error" class="auth-alert auth-alert--error">
          <i class="fas fa-exclamation-circle"></i>
          <span>{{ error }}</span>
        </div>

        <div class="auth-card__header">
          <div class="auth-card__badge">
            <i class="fas fa-seedling text-3xl text-blue-200"></i>
          </div>
          <div>
            <h1 class="auth-card__title">Welcome back</h1>
            <p class="auth-card__subtitle">
              Sign in to continue to the KLEMA dashboard
            </p>
          </div>
        </div>

        <form class="auth-form" @submit.prevent="handleLogin">
          <div class="auth-form__group">
            <label for="email" class="auth-form__label">Email address</label>
            <div class="auth-input-wrapper">
              <i class="fas fa-envelope"></i>
              <input
                id="email"
                v-model="form.email"
                type="email"
                class="auth-input"
                placeholder="someone@example.com"
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
                placeholder="Enter your password"
                required
                autocomplete="current-password"
              >
              <button
                type="button"
                class="password-toggle"
                @click="showPassword = !showPassword"
                aria-label="Toggle password visibility"
              >
                <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
              </button>
            </div>
          </div>

          <div class="auth-form__meta">
            <label class="auth-checkbox">
              <input v-model="form.remember" type="checkbox">
              <span>Remember me</span>
            </label>
            <a href="#" class="auth-link" @click.prevent="showForgotPassword">Forgot password?</a>
          </div>

          <button type="submit" class="auth-button" :disabled="loading">
            <i v-if="loading" class="fas fa-spinner fa-spin"></i>
            <i v-else class="fas fa-sign-in-alt"></i>
            <span>{{ loading ? 'Signing in...' : 'Sign in' }}</span>
          </button>
        </form>

        <div class="auth-footer">
          <span>Don't have an account?</span>
          <a href="#" class="auth-link" @click.prevent="showRegisterForm">Create one</a>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, watch } from 'vue';
import { setApiToken } from '../services/auth';
import axios from 'axios';

const props = defineProps({
  successMessage: {
    type: String,
    default: ''
  },
  email: {
    type: String,
    default: ''
  }
});

const emit = defineEmits(['login-success', 'navigate']);

const form = reactive({
  email: props.email || '',
  password: '',
  remember: false,
  device_name: 'Mobile App'
});

const loading = ref(false);
const error = ref(null);
const showPassword = ref(false);
const statusMessage = ref(props.successMessage);

watch(
  () => props.successMessage,
  (value) => {
    statusMessage.value = value;
  }
);

watch(
  () => props.email,
  (value) => {
    if (typeof value === 'string') {
      form.email = value;
    }
  }
);

const showRegisterForm = () => {
  emit('navigate', { view: 'register', email: form.email });
};

const showForgotPassword = () => {
  emit('navigate', { view: 'forgot', email: form.email });
};

const handleLogin = async () => {
  loading.value = true;
  error.value = null;

  try {
    const response = await axios.post('/api/auth/login', {
      email: form.email,
      password: form.password,
      device_name: form.device_name,
      remember: form.remember,
      create_session: true
    });

    if (response.data.success && response.data.token) {
      setApiToken(response.data.token);
      emit('login-success', response.data.user);
    } else {
      error.value = 'Login failed. Please check your credentials.';
    }
  } catch (err) {
    console.error('Login error:', err);

    if (err.response?.status === 423) {
      emit('navigate', { view: 'verify', email: form.email });
      error.value = err.response?.data?.message || 'Please verify your email address before logging in.';
    } else if (err.response?.status === 422) {
      error.value = err.response.data?.message || 'Invalid email or password.';
    } else {
      error.value = err.response?.data?.message || 'Login failed. Please try again.';
    }
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
/* Add your original CSS styles here - keeping the structure responsive */
/* Please paste your original styles and I'll make them responsive while maintaining your colors */

/* Reset and base styles */
* {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}

/* Wrapper - Full viewport with scrolling */
.auth-wrapper {
  min-height: 100vh;
  min-height: 100dvh;
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
}

/* Content container */
.auth-content {
  width: 100%;
  max-width: 28rem;
  margin: auto;
}

/* Card */
.auth-card {
  width: 100%;
  padding: 1.5rem;
}

/* Input wrapper */
.auth-input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
  overflow: hidden;
}

.auth-input-wrapper > i {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  z-index: 1;
  pointer-events: none;
}

.auth-input {
  width: 100%;
  padding: 0.75rem 3rem 0.75rem 3.50rem;
  box-sizing: border-box;
}

/* Password toggle */
.password-toggle {
  position: absolute;
  top: 50%;
  right: 0.75rem;
  transform: translateY(-50%);
  width: 2rem;
  height: 2rem;
  background: none;
  border: none;
  cursor: pointer;
  padding: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2;
}

/* Form */
.auth-form {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.auth-form__group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

/* Meta section */
.auth-form__meta {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.auth-checkbox {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
}

.auth-checkbox input[type="checkbox"] {
  width: 1rem;
  height: 1rem;
  cursor: pointer;
}

/* Button */
.auth-button {
  width: 100%;
  padding: 0.875rem 1.5rem;
  border: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.625rem;
  margin-top: 0.5rem;
}

.auth-button:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

/* Footer */
.auth-footer {
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  text-align: center;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.375rem;
  flex-wrap: wrap;
}

/* Alerts */
.auth-alert {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.875rem 1rem;
  margin-bottom: 1.25rem;
  line-height: 1.4;
}

.auth-alert i {
  flex-shrink: 0;
}

.auth-alert span {
  flex: 1;
  word-break: break-word;
}

/* Header */
.auth-card__header {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1.75rem;
}

.auth-card__badge {
  width: 3rem;
  height: 3rem;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.auth-card__title {
  margin-bottom: 0.25rem;
  line-height: 1.2;
}

.auth-card__subtitle {
  line-height: 1.4;
}

/* Tablet styles */
@media (min-width: 640px) {
  .auth-card {
    padding: 2rem;
  }
}

/* Desktop styles */
@media (min-width: 1024px) {
  .auth-card {
    padding: 2.5rem;
  }

  .auth-form {
    gap: 1.5rem;
  }

  .auth-card__header {
    margin-bottom: 2rem;
  }
}

/* Small mobile optimization */
@media (max-width: 375px) {
  .auth-card {
    padding: 1.25rem;
  }

  .auth-card__badge {
    width: 2.5rem;
    height: 2.5rem;
  }

  .auth-input {
    padding: 0.625rem 3rem 0.625rem 2.5rem;
  }

  .auth-button {
    padding: 0.75rem 1.25rem;
  }
}

/* Landscape mobile */
@media (max-height: 600px) and (orientation: landscape) {
  .auth-wrapper {
    align-items: flex-start;
  }

  .auth-content {
    padding: 0.5rem 0;
  }

  .auth-card {
    padding: 1.25rem;
  }

  .auth-card__header {
    margin-bottom: 1rem;
  }

  .auth-form {
    gap: 1rem;
  }

  .auth-footer {
    margin-top: 1rem;
    padding-top: 1rem;
  }
}
</style>