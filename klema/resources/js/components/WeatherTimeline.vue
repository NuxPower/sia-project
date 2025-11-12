<template>
  <div class="weather-timeline" :class="{ 'compact': isCompact }">
    <button 
      class="toggle-compact-button" 
      @click="toggleCompact"
      :title="isCompact ? 'Expand forecast' : 'Compact forecast'"
      aria-label="Toggle forecast size"
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
      @select="handleDaySelect"
    />
  </div>
</template>

<script setup>
import { ref } from 'vue';
import WeatherCard from './WeatherCard.vue';

const props = defineProps({
  forecast: Array,
  getDayLabel: Function,
  getWeatherIcon: Function
});

const emit = defineEmits(['day-selected']);

const isCompact = ref(false);

const toggleCompact = () => {
  isCompact.value = !isCompact.value;
};

const handleDaySelect = (day) => {
  if (!day) return;
  emit('day-selected', day);
};

// No need for mobile detection anymore since button is always available
</script>

<style scoped>
.weather-timeline {
  position: fixed;
  bottom: 20px;
  left: 50%;
  transform: translateX(-50%);
  z-index: 1000;
  display: flex;
  align-items: center;
  gap: 16px;
  background: linear-gradient(135deg, rgba(15, 23, 42, 0.95), rgba(30, 41, 59, 0.95));
  border-radius: 20px;
  padding: 16px 20px;
  backdrop-filter: blur(20px);
  border: 2px solid rgba(59, 130, 246, 0.4);
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(59, 130, 246, 0.1);
  max-width: 95vw;
  overflow-x: auto;
  overflow-y: hidden;
  transition: all 0.3s ease;
  scroll-behavior: smooth;
  -webkit-overflow-scrolling: touch;
}

.weather-timeline.compact {
  padding: 12px 16px;
  gap: 12px;
  border-radius: 16px;
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

.toggle-compact-button {
  background: rgba(59, 130, 246, 0.2);
  border: 1px solid rgba(59, 130, 246, 0.3);
  color: white;
  width: 26px;
  height: 26px;
  min-width: 26px;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 10px;
  flex-shrink: 0;
  padding: 0;
  margin-right: 2px;
}

.toggle-compact-button:hover {
  background: rgba(59, 130, 246, 0.35);
  border-color: rgba(59, 130, 246, 0.5);
  transform: scale(1.05);
}

.toggle-compact-button:active {
  transform: scale(0.98);
}

@media (max-width: 768px) {
  .weather-timeline {
    bottom: 85px;
    left: 10px;
    right: 10px;
    transform: none;
    padding: 12px 14px;
    gap: 10px;
    z-index: 1001;
    max-width: calc(100vw - 20px);
    border-radius: 16px;
  }
  
  .weather-timeline.compact {
    padding: 8px 10px;
    gap: 6px;
    border-radius: 14px;
  }
  
  .toggle-compact-button {
    width: 26px;
    height: 26px;
    min-width: 26px;
    font-size: 10px;
    margin-right: 3px;
  }
}

@media (max-width: 480px) {
  .weather-timeline {
    bottom: 80px;
    left: 8px;
    right: 8px;
    padding: 10px 12px;
    gap: 8px;
    max-width: calc(100vw - 16px);
    border-radius: 14px;
  }
  
  .weather-timeline.compact {
    padding: 6px 8px;
    gap: 5px;
    border-radius: 12px;
  }
  
  .toggle-compact-button {
    width: 24px;
    height: 24px;
    min-width: 24px;
    font-size: 9px;
    margin-right: 2px;
  }
}
</style>