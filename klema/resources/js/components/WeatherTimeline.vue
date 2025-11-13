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

/* Responsive Design - Mobile First Approach */

/* Extra Small Devices (phones, up to 480px) */
@media (max-width: 480px) {
  .weather-timeline {
    bottom: 0.5rem;
    left: 0.5rem;
    right: 0.5rem;
    transform: none;
    padding: 0.5rem;
    gap: 0.375rem;
    max-width: calc(100vw - 1rem);
    border-radius: 0.75rem;
  }
  
  .weather-timeline.compact {
    padding: 0.375rem;
    gap: 0.375rem;
  }
}

/* Small Devices (landscape phones, 481px to 640px) */
@media (min-width: 481px) and (max-width: 640px) {
  .weather-timeline {
    bottom: 0.625rem;
    padding: 0.5rem 0.625rem;
    gap: 0.5rem;
  }
  
  .weather-timeline.compact {
    padding: 0.375rem 0.5rem;
    gap: 0.375rem;
  }
}

/* Medium Devices (tablets, 641px to 768px) */
@media (min-width: 641px) and (max-width: 768px) {
  .weather-timeline {
    bottom: 0.75rem;
    padding: 0.625rem 0.75rem;
    gap: 0.625rem;
  }
}

/* Standard Mobile (up to 768px) */
@media (max-width: 768px) {
  .weather-timeline {
    bottom: 0.625rem;
    padding: 0.5rem 0.625rem;
    gap: 0.5rem;
  }
  
  .weather-timeline.compact {
    padding: 0.375rem 0.5rem;
    gap: 0.375rem;
  }
}

/* Large Devices (desktops, 1024px and up) */
@media (min-width: 1024px) {
  .weather-timeline {
    bottom: 0.9375rem;
    padding: 0.75rem 1rem;
    gap: 0.75rem;
  }
}

/* Extra Large Devices (large desktops, 1440px and up) */
@media (min-width: 1440px) {
  .weather-timeline {
    bottom: 1rem;
    padding: 0.75rem 1rem;
    gap: 0.75rem;
  }
}

/* Zoom Support - Ensure proper scaling */
@media (min-resolution: 192dpi) {
  .weather-timeline {
    border-width: 1.5px;
  }
}
</style>