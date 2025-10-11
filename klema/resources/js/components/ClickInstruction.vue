<template>
  <div class="click-instruction">
    📍 Click anywhere on the map to pin weather data
  </div>
</template>

<script setup>
// Pure presentational component
</script>

<style scoped>
.click-instruction {
  position: fixed;
  top: 80px;
  right: 20px;
  background: rgba(59, 130, 246, 0.9);
  color: white;
  padding: 8px 12px;
  border-radius: 8px;
  font-size: 12px;
  z-index: 1000;
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.2);
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.7;
  }
}
</style>