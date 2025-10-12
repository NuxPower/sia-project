<template>
  <div class="weather-dashboard">
    <!-- Map View (default) -->
    <template v-if="activeView === 'map'">
      <SearchBar 
        v-model="searchLocation"
        @search="searchWeather"
        :is-loading="isLoadingWeather"
      />
      
      <ClickInstruction />
      
      <WeatherLayerControls 
        @toggle-layer="handleLayerToggle"
      />
      
      <WeatherMap
        ref="weatherMapRef"
        :is-loading="mapLoading"
        @map-click="handleMapClick"
        @map-ready="handleMapReady"
      />
      
      <LoadingIndicator
        v-if="isLoadingWeather"
        message="Fetching weather data..."
        subtitle="Loading forecast for pinned location"
      />
      
      <WeatherTimeline
        :forecast="forecast"
        :get-day-label="getDayLabel"
        :get-weather-icon="getWeatherIcon"
      />
      
      <TimelineLegend />
    </template>

    <!-- Dashboard View -->
    <DashboardView 
      v-else-if="activeView === 'dashboard'"
      :current-weather="currentWeather"
      :forecast="forecast"
      :get-day-label="getDayLabel"
      :get-weather-icon="getWeatherIcon"
    />

    <!-- Calendar View -->
    <CalendarView 
      v-else-if="activeView === 'calendar'"
      :forecast="forecast"
      :get-weather-icon="getWeatherIcon"
    />

    <!-- Alerts View -->
    <AlertsView v-else-if="activeView === 'alerts'" />

    <!-- Settings View -->
    <SettingsView v-else-if="activeView === 'settings'" />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import SearchBar from './SearchBar.vue';
import ClickInstruction from './ClickInstruction.vue';
import WeatherLayerControls from './WeatherLayerControls.vue';
import WeatherMap from './WeatherMap.vue';
import LoadingIndicator from './LoadingIndicator.vue';
import WeatherTimeline from './WeatherTimeline.vue';
import TimelineLegend from './TimelineLegend.vue';
import DashboardView from './Views/DashboardView.vue';
import CalendarView from './Views/CalendarView.vue';
import AlertsView from './Views/AlertsView.vue';
import SettingsView from './Views/SettingsView.vue';
import { useWeatherAPI } from '../composables/useWeatherAPI';
import { useWeatherUtils } from '../composables/useWeatherUtils';

const weatherMapRef = ref(null);
const searchLocation = ref('Maramag, Northern Mindanao');
const forecast = ref([]);
const currentWeather = ref(null);
const mapLoading = ref(true);
const isLoadingWeather = ref(false);
const activeView = ref('dashboard'); // Default to dashboard view

const { 
  fetchWeatherByLocation, 
  fetchWeatherByCoordinates,
  createWeatherTimeline 
} = useWeatherAPI();

const { getDayLabel, getWeatherIcon } = useWeatherUtils();

const setActiveView = (view) => {
  activeView.value = view;
  console.log('View changed to:', view);
};

const handleLayerToggle = ({ layerId, active }) => {
  weatherMapRef.value?.toggleWeatherLayer(layerId, active);
};

const handleMapClick = async ({ lat, lng }) => {
  isLoadingWeather.value = true;
  try {
    const { current, history, forecastData } = await fetchWeatherByCoordinates(lat, lng);
    currentWeather.value = current;
    const timeline = createWeatherTimeline(history, current, forecastData);
    forecast.value = timeline;
    
    if (current.name) {
      searchLocation.value = `${current.name}, ${current.sys.country}`;
    }
    
    weatherMapRef.value?.updateMarker(lat, lng, current);
  } catch (error) {
    console.error('Error fetching weather:', error);
    alert('Failed to fetch weather data. Please try again.');
  } finally {
    isLoadingWeather.value = false;
  }
};

const searchWeather = async () => {
  if (!searchLocation.value.trim()) return;
  
  isLoadingWeather.value = true;
  try {
    const { current, history, forecastData } = await fetchWeatherByLocation(searchLocation.value);
    currentWeather.value = current;
    const timeline = createWeatherTimeline(history, current, forecastData);
    forecast.value = timeline;
    
    if (current.coord) {
      weatherMapRef.value?.moveToLocation(current.coord.lat, current.coord.lon);
      weatherMapRef.value?.updateMarker(current.coord.lat, current.coord.lon, current);
    }
  } catch (error) {
    console.error('Error searching weather:', error);
    alert('Failed to fetch weather data. Please try again.');
  } finally {
    isLoadingWeather.value = false;
  }
};

const handleMapReady = () => {
  mapLoading.value = false;
  searchWeather();
};

onMounted(() => {
  // Expose to global scope for navbar integration
  window.vueApp = {
    setActiveView,
    searchWeather,
    searchLocation
  };
});
</script>

<style scoped>
.weather-dashboard {
  position: relative;
  width: 100vw;
  height: 100vh;
  background: linear-gradient(135deg, #0f172a, #1e293b);
  overflow: hidden;
}
</style>