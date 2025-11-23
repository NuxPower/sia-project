<template>
  <div class="auth-wrapper">
    <div class="auth-content">
      <transition name="auth-fade" mode="out-in">
        <component
          :is="activeComponent"
          :key="currentView"
          v-bind="componentProps"
          @login-success="handleLoginSuccess"
          @navigate="handleNavigate"
        />
      </transition>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import LoginView from '../LoginView.vue';
import RegisterView from './RegisterView.vue';
import VerifyEmailView from './VerifyEmailView.vue';
import ForgotPasswordView from './ForgotPasswordView.vue';
import ResetPasswordView from './ResetPasswordView.vue';

const emit = defineEmits(['login-success']);

const currentView = ref('login');
const pendingEmail = ref('');
const resetToken = ref('');
const successMessage = ref('');

const viewRegistry = {
  login: LoginView,
  register: RegisterView,
  verify: VerifyEmailView,
  forgot: ForgotPasswordView,
  reset: ResetPasswordView
};

const detectInitialState = () => {
  if (typeof window === 'undefined') {
    return;
  }

  const url = new URL(window.location.href);

  if (url.pathname.startsWith('/password/reset/')) {
    currentView.value = 'reset';
    resetToken.value = decodeURIComponent(url.pathname.split('/').pop() || '');
    pendingEmail.value = url.searchParams.get('email') ?? '';
  } else if (url.searchParams.get('view')) {
    const requestedView = url.searchParams.get('view');
    if (requestedView && viewRegistry[requestedView]) {
      currentView.value = requestedView;
      // Extract token from query params if view is 'reset'
      if (requestedView === 'reset' && url.searchParams.has('token')) {
        resetToken.value = decodeURIComponent(url.searchParams.get('token') || '');
      }
    }
    pendingEmail.value = url.searchParams.get('email') ?? '';
  }

  if (url.searchParams.get('verified') === '1') {
    successMessage.value = 'Email verified successfully. You can sign in now.';
  }

  if (url.searchParams.has('email')) {
    pendingEmail.value = url.searchParams.get('email') ?? pendingEmail.value;
  }

  // Clean URL to keep SPA routes tidy
  if (window.location.pathname !== '/app') {
    window.history.replaceState(null, '', '/app');
  } else if (url.search) {
    window.history.replaceState(null, '', '/app');
  }
};

detectInitialState();

const activeComponent = computed(() => viewRegistry[currentView.value] ?? LoginView);
const componentProps = computed(() => {
  const props = {};

  if (currentView.value === 'login') {
    props.successMessage = successMessage.value;
    props.email = pendingEmail.value;
  }

  if (currentView.value === 'register') {
    props.email = pendingEmail.value;
  }

  if (currentView.value === 'verify') {
    props.email = pendingEmail.value;
  }

  if (currentView.value === 'forgot') {
    props.email = pendingEmail.value;
  }

  if (currentView.value === 'reset') {
    props.email = pendingEmail.value;
    props.token = resetToken.value;
  }

  return props;
});

const handleLoginSuccess = (user) => {
  emit('login-success', user);
};

const handleNavigate = (payload = {}) => {
  if (payload.successMessage) {
    successMessage.value = payload.successMessage;
    currentView.value = payload.view && viewRegistry[payload.view] ? payload.view : 'login';
  } else if (payload.view && viewRegistry[payload.view]) {
    currentView.value = payload.view;
    if (payload.view !== 'login') {
      successMessage.value = '';
    }
  }

  if (Object.prototype.hasOwnProperty.call(payload, 'email')) {
    pendingEmail.value = payload.email ?? '';
  }

  if (Object.prototype.hasOwnProperty.call(payload, 'token')) {
    resetToken.value = payload.token ?? '';
  }
};
</script>

<style scoped>
.auth-fade-enter-active,
.auth-fade-leave-active {
  transition: opacity 0.3s ease, transform 0.3s ease;
}

.auth-fade-enter-from,
.auth-fade-leave-to {
  opacity: 0;
  transform: translateY(10px);
}

:global(.auth-wrapper) {
  min-height: 100vh;
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0;
  position: relative;
}

:global(.auth-content) {
  position: relative;
  z-index: 1;
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
}

:global(.auth-card) {
  width: min(440px, 100%);
  background: linear-gradient(160deg, rgba(15, 23, 42, 0.85), rgba(30, 41, 59, 0.78));
  border: 1px solid rgba(148, 163, 184, 0.16);
  border-radius: 28px;
  padding: 48px 42px;
  box-shadow: 0 40px 70px rgba(8, 15, 35, 0.45);
  backdrop-filter: blur(22px);
}

:global(.auth-card__header) {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 18px;
  margin-bottom: 36px;
  text-align: center;
}

:global(.auth-card__badge) {
  width: 80px;
  height: 80px;
  border-radius: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(140deg, rgba(59, 130, 246, 0.25), rgba(37, 99, 235, 0.5));
  border: 1px solid rgba(59, 130, 246, 0.35);
  box-shadow: 0 18px 45px rgba(37, 99, 235, 0.28);
}

:global(.auth-card__title) {
  font-size: 28px;
  font-weight: 700;
  margin: 0;
  color: #f8fafc;
}

:global(.auth-card__subtitle) {
  margin: 0;
  font-size: 14px;
  color: rgba(226, 232, 240, 0.7);
  letter-spacing: 0.02em;
}

:global(.auth-form) {
  display: flex;
  flex-direction: column;
  gap: 22px;
}

:global(.auth-form__group) {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

:global(.auth-form__label) {
  font-size: 14px;
  font-weight: 600;
  color: rgba(226, 232, 240, 0.85);
  letter-spacing: 0.02em;
}

:global(.auth-input-wrapper) {
  position: relative;
  display: flex;
  align-items: center;
  border-radius: 16px;
  background: rgba(15, 23, 42, 0.55);
  border: 1px solid rgba(148, 163, 184, 0.18);
  transition: border 0.2s ease, box-shadow 0.2s ease;
  overflow: hidden;
}

:global(.auth-input-wrapper:focus-within) {
  border-color: rgba(59, 130, 246, 0.55);
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
}

:global(.auth-input-wrapper i) {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: rgba(148, 163, 184, 0.65);
  font-size: 15px;
  pointer-events: none;
}

:global(.auth-input) {
  flex: 1 1 auto;
  background: transparent;
  border: none;
  outline: none;
  padding: 16px 56px 16px 56px;
  font-size: 15px;
  color: #f8fafc;
  width: 100%;
  box-sizing: border-box;
}

:global(.auth-input::placeholder) {
  color: rgba(148, 163, 184, 0.6);
}

:global(.auth-form__meta) {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  font-size: 13px;
  color: rgba(226, 232, 240, 0.75);
}

:global(.auth-checkbox) {
  display: flex;
  align-items: center;
  gap: 10px;
}

:global(.auth-checkbox input) {
  width: 16px;
  height: 16px;
  border-radius: 4px;
  border: 1px solid rgba(148, 163, 184, 0.4);
  background: rgba(15, 23, 42, 0.7);
  accent-color: #3b82f6;
}

:global(.auth-link) {
  color: #60a5fa;
  text-decoration: none;
  transition: color 0.2s ease;
}

:global(.auth-link:hover) {
  color: #93c5fd;
}

:global(.auth-button) {
  width: 100%;
  padding: 15px 20px;
  border-radius: 16px;
  border: none;
  font-size: 15px;
  font-weight: 600;
  color: #f8fafc;
  background: linear-gradient(135deg, #3b82f6, #2563eb);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  box-shadow: 0 25px 45px rgba(37, 99, 235, 0.4);
  cursor: pointer;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

:global(.auth-button:hover:not(:disabled)) {
  transform: translateY(-2px);
  box-shadow: 0 30px 60px rgba(37, 99, 235, 0.45);
}

:global(.auth-button:disabled) {
  opacity: 0.6;
  cursor: not-allowed;
}

:global(.auth-footer) {
  text-align: center;
  margin-top: 28px;
  font-size: 14px;
  color: rgba(226, 232, 240, 0.75);
}

:global(.password-toggle) {
  position: absolute;
  top: 50%;
  right: 0.75rem;
  transform: translateY(-50%);
  width: 34px;
  height: 34px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: none;
  border: none;
  color: rgba(148, 163, 184, 0.7);
  cursor: pointer;
  padding: 0;
  font-size: 16px;
  transition: color 0.2s ease;
  z-index: 2;
}

:global(.password-toggle:hover) {
  color: rgba(226, 232, 240, 0.95);
}

:global(.auth-alert) {
  margin-bottom: 16px;
  padding: 14px 18px;
  border-radius: 14px;
  font-size: 14px;
  display: flex;
  align-items: center;
  gap: 10px;
  border: 1px solid rgba(148, 163, 184, 0.2);
}

:global(.auth-alert--info) {
  background: rgba(59, 130, 246, 0.18);
  color: #cbd5f5;
  border-color: rgba(59, 130, 246, 0.35);
}

:global(.auth-alert--success) {
  background: rgba(34, 197, 94, 0.18);
  color: #bbf7d0;
  border-color: rgba(34, 197, 94, 0.35);
}

:global(.auth-alert--error) {
  background: rgba(248, 113, 113, 0.2);
  color: #fecaca;
  border-color: rgba(248, 113, 113, 0.35);
}

@media (max-width: 540px) {
  :global(.auth-card) {
    padding: 38px 26px;
    border-radius: 22px;
  }

  :global(.auth-card__title) {
    font-size: 24px;
  }
}
</style>

