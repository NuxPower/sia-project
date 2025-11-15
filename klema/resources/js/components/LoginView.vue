<template>
  <div class="login-container">
    <div class="login-card">
      <div class="login-header">
        <i class="fas fa-seedling"></i>
        <h1>Welcome to KLEMA</h1>
        <p>Sign in to access your dashboard</p>
      </div>

      <div v-if="error" class="alert alert-error">
        <i class="fas fa-exclamation-circle"></i>
        {{ error }}
      </div>

      <form @submit.prevent="handleLogin" class="login-form">
        <div class="form-group">
          <label for="email">Email address</label>
          <div class="input-wrapper">
            <i class="fas fa-envelope"></i>
            <input
              id="email"
              v-model="form.email"
              type="email"
              placeholder="someone@example.com"
              required
              autocomplete="email"
            >
          </div>
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <div class="input-wrapper">
            <i class="fas fa-lock"></i>
            <input
              id="password"
              v-model="form.password"
              type="password"
              placeholder="Enter your password"
              required
              autocomplete="current-password"
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

        <div class="form-meta">
          <label class="checkbox">
            <input v-model="form.remember" type="checkbox">
            <span>Remember me</span>
          </label>
        </div>

        <button type="submit" class="btn btn-primary" :disabled="loading">
          <i v-if="loading" class="fas fa-spinner fa-spin"></i>
          <i v-else class="fas fa-sign-in-alt"></i>
          <span>{{ loading ? 'Signing in...' : 'Sign in' }}</span>
        </button>
      </form>

      <div class="login-footer">
        <span>Don't have an account?</span>
        <a href="#" @click.prevent="showRegister = true" class="link">Create one</a>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { setApiToken } from '../services/auth';
import axios from 'axios';

const emit = defineEmits(['login-success', 'show-register']);

const form = reactive({
  email: '',
  password: '',
  remember: false,
  device_name: 'Mobile App'
});

const loading = ref(false);
const error = ref(null);
const showPassword = ref(false);
const showRegister = ref(false);

const handleLogin = async () => {
  loading.value = true;
  error.value = null;

  try {
    // Use API route for mobile
    const response = await axios.post('/api/auth/login', {
      email: form.email,
      password: form.password,
      device_name: form.device_name,
      remember: form.remember
    });

    if (response.data.success && response.data.token) {
      // Store the token
      setApiToken(response.data.token);
      
      // Emit success event
      emit('login-success', response.data.user);
      
      // Reload the app
      window.location.reload();
    } else {
      error.value = 'Login failed. Please check your credentials.';
    }
  } catch (err) {
    console.error('Login error:', err);
    
    if (err.response?.status === 423) {
      error.value = 'Please verify your email address before logging in.';
    } else if (err.response?.status === 422) {
      error.value = err.response.data?.message || 'Invalid email or password.';
    } else {
      error.value = err.response?.data?.message || 'Login failed. Please try again.';
    }
  } finally {
    loading.value = false;
  }
};

if (showRegister.value) {
  emit('show-register');
}
</script>

<style scoped>
.login-container {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.login-card {
  background: white;
  border-radius: 12px;
  padding: 40px;
  width: 100%;
  max-width: 400px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
}

.login-header {
  text-align: center;
  margin-bottom: 30px;
}

.login-header i {
  font-size: 48px;
  color: #667eea;
  margin-bottom: 16px;
}

.login-header h1 {
  margin: 0 0 8px 0;
  color: #1a202c;
  font-size: 24px;
}

.login-header p {
  margin: 0;
  color: #718096;
  font-size: 14px;
}

.login-form {
  margin-bottom: 24px;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  color: #4a5568;
  font-weight: 500;
  font-size: 14px;
}

.input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.input-wrapper i {
  position: absolute;
  left: 12px;
  color: #a0aec0;
  z-index: 1;
}

.input-wrapper input {
  width: 100%;
  padding: 12px 12px 12px 40px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 14px;
  transition: border-color 0.2s;
}

.input-wrapper input:focus {
  outline: none;
  border-color: #667eea;
}

.password-toggle {
  position: absolute;
  right: 12px;
  background: none;
  border: none;
  color: #a0aec0;
  cursor: pointer;
  padding: 4px;
}

.form-meta {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.checkbox {
  display: flex;
  align-items: center;
  cursor: pointer;
}

.checkbox input {
  margin-right: 8px;
}

.btn {
  width: 100%;
  padding: 12px;
  border: none;
  border-radius: 8px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  transition: all 0.2s;
}

.btn-primary {
  background: #667eea;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: #5568d3;
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.alert {
  padding: 12px;
  border-radius: 8px;
  margin-bottom: 20px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.alert-error {
  background: #fed7d7;
  color: #c53030;
}

.login-footer {
  text-align: center;
  color: #718096;
  font-size: 14px;
}

.login-footer .link {
  color: #667eea;
  text-decoration: none;
  font-weight: 500;
}

.login-footer .link:hover {
  text-decoration: underline;
}
</style>






