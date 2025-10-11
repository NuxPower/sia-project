<template>
  <div class="loading-indicator">
    <div class="loading-icon">{{ icon }}</div>
    <div class="loading-message">{{ message }}</div>
    <div v-if="subtitle" class="loading-subtitle">{{ subtitle }}</div>
  </div>
</template>

<script setup>
defineProps({
  message: {
    type: String,
    default: 'Loading...'
  },
  subtitle: String,
  icon: {
    type: String,
    default: '🌤️'
  }
});
</script>

<style scoped>
.loading-indicator {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  background: rgba(0, 0, 0, 0.9);
  color: white;
  padding: 30px 40px;
  border-radius: 16px;
  border: 2px solid rgba(59, 130, 246, 0.3);
  backdrop-filter: blur(15px);
  text-align: center;
  z-index: 2000;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
}

.loading-icon {
  font-size: 48px;
  margin-bottom: 15px;
}

.loading-message {
  font-size: 20px;
  margin-bottom: 10px;
}

.loading-subtitle {
  font-size: 14px;
  color: #9ca3af;
  margin-top: 10px;
}
</style>