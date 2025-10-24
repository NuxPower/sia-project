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
    
    <!-- Global Alert Notifications -->
    <GlobalAlertNotification />
    
    <!-- Test Alert Button (temporary) -->
    <div class="test-alert-button">
      <button @click="handleTestAlert" class="test-button" title="Test Global Alert">
        <i class="fas fa-bell"></i>
      </button>
    </div>
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
import GlobalAlertNotification from './GlobalAlertNotification.vue';
import { useWeatherAPI } from '../composables/useWeatherAPI';
import { useWeatherUtils } from '../composables/useWeatherUtils';
import { useGlobalAlerts } from '../composables/useGlobalAlerts';

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
const { fetchRealWeatherAlerts, showWeatherAlert, showIrrigationAlert, showHarvestAlert, checkWeatherConditions } = useGlobalAlerts();

// Test function to fetch and check real weather alerts
const handleTestAlert = async () => {
  console.log('Test button clicked - fetching real weather data!');
  
  try {
    // Fetch real weather data and generate alerts
    await fetchRealWeatherAlerts();
  } catch (error) {
    console.error('Error fetching real weather alerts:', error);
  }
};

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
    
    // Check for weather alerts with real data
    const weatherData = { current, history, forecast: forecastData };
    checkWeatherConditions(weatherData);
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
    
    // Check for weather alerts with real data
    const weatherData = { current, history, forecast: forecastData };
    checkWeatherConditions(weatherData);
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

/* Test Alert Button */
.test-alert-button {
  position: fixed;
  bottom: 100px;
  left: 20px;
  z-index: 10000;
}

.test-button {
  width: 60px;
  height: 60px;
  background: rgba(239, 68, 68, 0.8);
  border: 2px solid rgba(239, 68, 68, 0.3);
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.3s ease;
  color: white;
  font-size: 20px;
  backdrop-filter: blur(15px);
  box-shadow: 0 8px 32px rgba(239, 68, 68, 0.3);
  animation: pulse 2s infinite;
}

.test-button:hover {
  background: rgba(239, 68, 68, 1);
  border-color: rgba(239, 68, 68, 0.6);
  transform: scale(1.1);
  box-shadow: 0 12px 40px rgba(239, 68, 68, 0.5);
  animation: none;
}

@keyframes pulse {
  0% {
    box-shadow: 0 8px 32px rgba(239, 68, 68, 0.3);
  }
  50% {
    box-shadow: 0 8px 32px rgba(239, 68, 68, 0.6);
  }
  100% {
    box-shadow: 0 8px 32px rgba(239, 68, 68, 0.3);
  }
}

/* Mobile responsive */
@media (max-width: 768px) {
  .test-alert-button {
    bottom: 80px;
    left: 10px;
  }
  
  .test-button {
    width: 50px;
    height: 50px;
    font-size: 18px;
  }
}
</style>