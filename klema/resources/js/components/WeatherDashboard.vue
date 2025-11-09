<template>
  <div class="weather-dashboard">
    <SearchBar 
      v-if="activeView === 'map'"
      v-model="searchLocation"
      @search="searchWeather"
      :is-loading="isLoadingWeather"
    />
    
    <ClickInstruction v-if="activeView === 'map'" />
    
    <WeatherLayerControls 
      v-if="activeView === 'map'"
      @toggle-layer="handleLayerToggle" 
    />

    <div class="map-wrapper">
      <WeatherMap
        ref="weatherMapRef"
        :is-loading="mapLoading"
        @map-click="handleMapClick"
        @map-ready="handleMapReady"
      />

      <LoadingIndicator
        v-if="isLoadingWeather && activeView === 'map'"
        message="Fetching weather data..."
        subtitle="Loading forecast for pinned location"
      />

      <div v-if="activeView !== 'map'" class="map-overlay"></div>
    </div>
    
    <WeatherTimeline
      v-if="activeView === 'map'"
      :forecast="forecast"
      :get-day-label="getDayLabel"
      :get-weather-icon="getWeatherIcon"
    />
    
    <TimelineLegend v-if="activeView === 'map'" />

    <transition name="overlay-fade">
      <div v-if="activeView !== 'map'" class="overlay-wrapper">
        <div class="overlay-panel">
          <div class="overlay-content">
            <DashboardView 
              v-if="activeView === 'dashboard'"
              :current-weather="currentWeather"
              :forecast="forecast"
              :get-day-label="getDayLabel"
              :get-weather-icon="getWeatherIcon"
            />

            <CalendarView 
              v-else-if="activeView === 'calendar'"
              :forecast="forecast"
              :get-weather-icon="getWeatherIcon"
            />

            <AlertsView v-else-if="activeView === 'alerts'" />

            <SettingsView v-else-if="activeView === 'settings'" />
          </div>
        </div>
      </div>
    </transition>

    <GlobalAlertNotification />
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
import { ensureApiToken } from '../services/auth';

const weatherMapRef = ref(null);
const searchLocation = ref('Maramag, Northern Mindanao');
const forecast = ref([]);
const currentWeather = ref(null);
const mapLoading = ref(true);
const isLoadingWeather = ref(false);
const activeView = ref('map');

const { 
  fetchWeatherByLocation, 
  fetchWeatherByCoordinates,
  createWeatherTimeline 
} = useWeatherAPI();

const { getDayLabel, getWeatherIcon } = useWeatherUtils();
const { checkWeatherConditions } = useGlobalAlerts();

const setActiveView = (view) => {
  activeView.value = view;
};

const handleLayerToggle = ({ layerId, active }) => {
  weatherMapRef.value?.toggleWeatherLayer(layerId, active);
};

const handleMapClick = async ({ lat, lng }) => {
  isLoadingWeather.value = true;
  try {
    const { current, history, forecastData } = await fetchWeatherByCoordinates(lat, lng);
    currentWeather.value = current;
    forecast.value = createWeatherTimeline(history, current, forecastData);
    
    if (current.name) {
      searchLocation.value = `${current.name}, ${current.sys.country}`;
    }
    
    weatherMapRef.value?.updateMarker(lat, lng, current);
    checkWeatherConditions({ current, history, forecast: forecastData });
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
    forecast.value = createWeatherTimeline(history, current, forecastData);
    
    if (current.coord) {
      weatherMapRef.value?.moveToLocation(current.coord.lat, current.coord.lon);
      weatherMapRef.value?.updateMarker(current.coord.lat, current.coord.lon, current);
    }
    
    checkWeatherConditions({ current, history, forecast: forecastData });
  } catch (error) {
    console.error('Error searching weather:', error);
    alert('Failed to fetch weather data. Please try again.');
  } finally {
    isLoadingWeather.value = false;
  }
};

const handleMapReady = async () => {
  mapLoading.value = false;
  await ensureApiToken(window.axios);
  await searchWeather();
};

onMounted(async () => {
  await ensureApiToken(window.axios);
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

.map-wrapper {
  position: absolute;
  inset: 0;
  z-index: 0;
}

.map-wrapper :deep(.weather-map-container) {
  width: 100%;
  height: 100%;
}

.map-overlay {
  position: absolute;
  inset: 0;
  background: rgba(15, 23, 42, 0.55);
  backdrop-filter: blur(6px);
  z-index: 2;
  pointer-events: auto;
}

.overlay-wrapper {
  position: absolute;
  inset: 0;
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 40px 40px 40px 140px;
  z-index: 3;
  pointer-events: none;
}

.overlay-panel {
  pointer-events: auto;
  width: 100%;
  height: calc(100vh - 60px);
  overflow: hidden;
  border-radius: 28px;
  background: rgba(12, 16, 24, 0.82);
  backdrop-filter: blur(18px);
  box-shadow: 0 30px 65px rgba(0, 0, 0, 0.45);
  padding: 0;
  position: relative;
}

.overlay-content {
  position: absolute;
  inset: 0;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.overlay-content :deep(.dashboard-view),
.overlay-content :deep(.settings-view),
.overlay-content :deep(.alerts-view),
.overlay-content :deep(.calendar-view) {
  flex: 1;
  width: 100%;
  margin: 0;
  overflow-y: auto;
  padding: 28px 36px 48px;
}

.overlay-fade-enter-active,
.overlay-fade-leave-active {
  transition: opacity 0.3s ease, transform 0.3s ease;
}

.overlay-fade-enter-from,
.overlay-fade-leave-to {
  opacity: 0;
  transform: translateY(12px);
}
</style>