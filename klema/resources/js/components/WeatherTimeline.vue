<template>
  <div class="weather-timeline">
    <WeatherCard
      v-for="(day, index) in forecast"
      :key="day.date || index"
      :day="day"
      :get-day-label="getDayLabel"
      :get-weather-icon="getWeatherIcon"
    />
  </div>
</template>

<script setup>
import WeatherCard from './WeatherCard.vue';

defineProps({
  forecast: Array,
  getDayLabel: Function,
  getWeatherIcon: Function
});
</script>

<style scoped>
.weather-timeline {
  position: fixed;
  bottom: 15px;
  left: 50%;
  transform: translateX(-50%);
  display: flex;
  background: rgba(0, 0, 0, 0.9);
  border-radius: 15px;
  padding: 12px 16px;
  gap: 12px;
  z-index: 1000;
  backdrop-filter: blur(15px);
  border: 2px solid rgba(59, 130, 246, 0.3);
  box-shadow: 0 6px 24px rgba(0, 0, 0, 0.4);
  max-width: 95vw;
  overflow-x: auto;
}
</style>