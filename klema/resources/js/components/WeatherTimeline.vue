<template>
  <div class="weather-timeline" :class="{ 'compact': isCompact }">
    <button 
      class="toggle-compact-button" 
      @click="toggleCompact"
      :title="isCompact ? 'Expand timeline' : 'Compact timeline'"
    >
      <i :class="isCompact ? 'fas fa-expand-alt' : 'fas fa-compress-alt'"></i>
    </button>
    
    <WeatherCard
      v-for="(day, index) in forecast"
      :key="day.date || index"
      :day="day"
      :get-day-label="getDayLabel"
      :get-weather-icon="getWeatherIcon"
      :is-compact="isCompact"
    />
  </div>
</template>

<script setup>
import { ref } from 'vue';
import WeatherCard from './WeatherCard.vue';

defineProps({
  forecast: Array,
  getDayLabel: Function,
  getWeatherIcon: Function
});

const isCompact = ref(false);

const toggleCompact = () => {
  isCompact.value = !isCompact.value;
};
</script>

<style scoped>
.weather-timeline {
  position: fixed;
  bottom: 15px;
  left: 50%;
  transform: translateX(-50%);
  z-index: 1000;
  display: flex;
  align-items: center;
  gap: 12px;
  background: rgba(0, 0, 0, 0.9);
  border-radius: 15px;
  padding: 12px 16px;
  backdrop-filter: blur(15px);
  border: 2px solid rgba(59, 130, 246, 0.3);
  box-shadow: 0 6px 24px rgba(0, 0, 0, 0.4);
  max-width: 95vw;
  overflow-x: auto;
  transition: all 0.3s ease;
}

.weather-timeline.compact {
  padding: 8px 12px;
  gap: 8px;
}

.toggle-compact-button {
  background: rgba(59, 130, 246, 0.3);
  border: 2px solid rgba(59, 130, 246, 0.5);
  color: white;
  width: 36px;
  height: 36px;
  min-width: 36px;
  border-radius: 10px;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  flex-shrink: 0;
}

.toggle-compact-button:hover {
  background: rgba(59, 130, 246, 0.5);
  transform: scale(1.1);
}

.weather-timeline::-webkit-scrollbar {
  height: 6px;
}

.weather-timeline::-webkit-scrollbar-track {
  background: rgba(0, 0, 0, 0.3);
  border-radius: 3px;
}

.weather-timeline::-webkit-scrollbar-thumb {
  background: rgba(59, 130, 246, 0.5);
  border-radius: 3px;
}

.weather-timeline::-webkit-scrollbar-thumb:hover {
  background: rgba(59, 130, 246, 0.7);
}

@media (max-width: 768px) {
  .weather-timeline {
    bottom: 10px;
    padding: 8px 10px;
    gap: 8px;
  }
  
  .weather-timeline.compact {
    padding: 6px 8px;
    gap: 6px;
  }
  
  .toggle-compact-button {
    width: 32px;
    height: 32px;
    min-width: 32px;
    font-size: 12px;
  }
}
</style>