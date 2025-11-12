<template>
  <div class="weather-timeline" :class="{ 'compact': isCompact }">
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

const isCompact = ref(true);

const handleDaySelect = (day) => {
  if (!day) return;
  emit('day-selected', day);
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
}
</style>