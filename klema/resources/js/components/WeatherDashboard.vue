<template>
  <div class="weather-dashboard">
    <SearchBar 
      v-if="activeView === 'map' && !selectedDayDetail"
      v-model="searchLocation"
      @search="searchWeather"
      :is-loading="isLoadingWeather"
    />
    
    <ClickInstruction v-if="activeView === 'map' && !selectedDayDetail" />
    
    <WeatherLayerControls 
      v-if="activeView === 'map' && !selectedDayDetail"
      @toggle-layer="handleLayerToggle" 
    />

    <div class="map-wrapper">
      <WeatherMap
        ref="weatherMapRef"
        :is-loading="mapLoading"
        @map-click="handleMapClick"
        @map-ready="handleMapReady"
        @location-update="handleLocationUpdate"
      />

      <LoadingIndicator
        v-if="isLoadingWeather && activeView === 'map'"
        message="Fetching weather data..."
        subtitle="Loading forecast for pinned location"
      />

      <div v-if="activeView !== 'map' || selectedDayDetail" class="map-overlay"></div>
    </div>
    
    <WeatherTimeline
      v-if="activeView === 'map' && !selectedDayDetail"
      :forecast="forecast"
      :get-day-label="getDayLabel"
      :get-weather-icon="getWeatherIcon"
      @day-selected="handleTimelineSelection"
    />
    
    <TimelineLegend v-if="activeView === 'map' && !selectedDayDetail" />

    <transition name="overlay-fade">
      <div v-if="activeView !== 'map' && !selectedDayDetail" class="overlay-wrapper">
        <div class="overlay-panel">
          <div class="overlay-content">
            <DashboardView 
              v-if="activeView === 'dashboard'"
              :current-weather="currentWeather"
              :forecast="forecast"
              :get-day-label="getDayLabel"
              :get-weather-icon="getWeatherIcon"
              :farms="rawFarms"
              :soil-types="soilTypes"
              :farms-loading="farmMapLoading"
              :is-drawing="!!boundarySession"
              :is-placing-point="!!pointSession"
              @refresh-farms="refreshFarmLayers"
              @create-farm="createFarm"
              @save-farm="updateFarmDetails"
              @clear-boundary="removeFarmBoundary"
              @start-boundary="startBoundaryEditing"
              @finish-boundary="finishBoundaryEditing"
              @cancel-boundary="cancelBoundaryEditing"
              @start-point="startPointPlacement"
              @cancel-point="cancelPointPlacement"
            />

            <CalendarView 
              v-else-if="activeView === 'calendar'"
              :get-weather-icon="getWeatherIcon"
              :timeline="fullForecastTimeline"
            />

            <AlertsView 
              v-else-if="activeView === 'alerts'"
              :alerts="alerts"
              :alerts-loading="alertsLoading"
              :alerts-error="alertsError"
              :forecast-warnings="forecastWarnings"
              :notification-settings="notificationSettings"
              :settings-loading="settingsLoading"
              :settings-error="settingsError"
              :settings-saved="settingsSaved"
              :get-alert-type-info="getAlertTypeInfo"
              :format-alert-time="formatAlertTime"
              :get-setting-info="getSettingInfo"
              :resolve-alert="resolveAlert"
              :delete-alert="deleteAlert"
              :update-notification-setting="handleNotificationSettingUpdate"
              :fetch-active-alerts="fetchActiveAlerts"
              :refresh-forecast-warnings="refreshForecastWarnings"
              :load-notification-settings="loadSettings"
              :create-alert="handleCreateAlert"
              :farms="alertsFarms"
              :location-label="currentLocationLabel"
            />

            <SettingsView v-else-if="activeView === 'settings'" />
          </div>
        </div>
      </div>
    </transition>

    <GlobalAlertNotification />

    <DayDetailView
      v-if="selectedDayDetail"
      :day="selectedDay"
      :detail="selectedDayDetail"
      :hourly-data="selectedDayHourly"
      :location-label="currentLocationLabel"
      @close="closeDayDetail"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, watch, computed } from 'vue';
import axios from 'axios';
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
import DayDetailView from './Views/DayDetailView.vue';
import GlobalAlertNotification from './GlobalAlertNotification.vue';
import { useWeatherAPI } from '../composables/useWeatherAPI';
import { useWeatherUtils } from '../composables/useWeatherUtils';
import { useGlobalAlerts } from '../composables/useGlobalAlerts';
import { useFarmMap } from '../composables/useFarmMap';
import { ensureApiToken } from '../services/auth';
import { useAlerts } from '../composables/useAlerts';
import { useNotificationSettings } from '../composables/useNotificationSettings';

const weatherMapRef = ref(null);
const DEFAULT_LOCATION = 'Maramag, Northern Mindanao';
const searchLocation = ref(DEFAULT_LOCATION);
const forecast = ref([]);
const fullForecastTimeline = ref([]);
const currentWeather = ref(null);
const mapLoading = ref(true);
const isLoadingWeather = ref(false);
const activeView = ref('map');
const boundarySession = ref(null);
const pointSession = ref(null);
let suppressNextLocationUpdate = false;
const latestForecastData = ref(null);
const farmPrefillComplete = ref(false);
const selectedDay = ref(null);
const selectedDayDetail = ref(null);
const selectedDayHourly = ref([]);

const { 
  fetchWeatherByLocation, 
  fetchWeatherByCoordinates,
  createWeatherTimeline 
} = useWeatherAPI();

const { getDayLabel, getWeatherIcon } = useWeatherUtils();
const { checkWeatherConditions, showSuccess, showError } = useGlobalAlerts();
const {
  farmFeatures,
  pointFeatures,
  rawFarms,
  soilTypes,
  loading: farmMapLoading,
  loadMapData
} = useFarmMap();
const {
  alerts,
  forecastWarnings,
  loading: alertsLoading,
  error: alertsError,
  fetchActiveAlerts,
  fetchForecastWarnings,
  createAlert,
  resolveAlert,
  deleteAlert,
  getAlertTypeInfo,
  formatAlertTime
} = useAlerts();
const {
  settings: notificationSettings,
  loading: settingsLoading,
  error: settingsError,
  loadSettings,
  updateSetting,
  getSettingInfo
} = useNotificationSettings();
const settingsSaved = ref(false);
const alertsInitialized = ref(false);

const alertsFarms = computed(() => rawFarms.value ?? []);
const farmCoordinateMap = computed(() => {
  return (rawFarms.value ?? []).reduce((map, farm) => {
    const name = farm?.farm_name?.trim().toLowerCase();
    const lat = parseFloat(farm?.latitude);
    const lon = parseFloat(farm?.longitude);
    if (name && Number.isFinite(lat) && Number.isFinite(lon)) {
      map[name] = { lat, lon, name: farm.farm_name };
    }
    return map;
  }, {});
});
const currentLocationLabel = computed(() => {
  if (currentWeather.value?.name) {
    return currentWeather.value.name;
  }
  return searchLocation.value;
});

watch(rawFarms, async (farms, previous) => {
  if (farms && farms.length > 0) {
    if (!farmPrefillComplete.value) {
      const prefilled = await initializeDefaultLocation();
      if (!prefilled) {
        await searchWeather();
      }
    }
  }
}, { immediate: true });

const setActiveView = (view) => {
  activeView.value = view;
};

const handleLayerToggle = ({ layerId, active }) => {
  weatherMapRef.value?.toggleWeatherLayer(layerId, active);
};

const handleMapClick = async ({ lat, lng }) => {
  isLoadingWeather.value = true;
  try {
    const { current, history, forecastData } = await fetchWeatherByCoordinates(lat, lng, { days: 16 });
    currentWeather.value = current;
    const previousTimeline = fullForecastTimeline.value;
    const timelineOptions = Array.isArray(history) && history.length
      ? { reuseHistory: previousTimeline }
      : {};
    fullForecastTimeline.value = createWeatherTimeline(history, current, forecastData, timelineOptions);
    forecast.value = createWeatherTimeline(history, current, forecastData, { windowSize: 7, ...timelineOptions });
    latestForecastData.value = forecastData;
    
    if (current.name) {
      searchLocation.value = `${current.name}, ${current.sys.country}`;
    }
    
    weatherMapRef.value?.updateMarker(lat, lng, current);
    checkWeatherConditions({ current, history, forecast: forecastData });
    await fetchForecastWarnings({ forecast: forecastData });
    if (selectedDay.value?.date) {
      hydrateSelectedDay(selectedDay.value.date);
    }
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
    const trimmed = searchLocation.value.trim();
    const farmMatch = farmCoordinateMap.value[trimmed.toLowerCase()];

    let current, history, forecastData;

    if (farmMatch) {
      ({ current, history, forecastData } = await fetchWeatherByCoordinates(farmMatch.lat, farmMatch.lon, { days: 16 }));
    } else {
      ({ current, history, forecastData } = await fetchWeatherByLocation(trimmed, { days: 16 }));
    }

    currentWeather.value = current;
    const previousTimeline = fullForecastTimeline.value;
    const timelineOptions = Array.isArray(history) && history.length
      ? { reuseHistory: previousTimeline }
      : {};
    fullForecastTimeline.value = createWeatherTimeline(history, current, forecastData, timelineOptions);
    forecast.value = createWeatherTimeline(history, current, forecastData, { windowSize: 7, ...timelineOptions });
    latestForecastData.value = forecastData;
    
    if (current.coord) {
      weatherMapRef.value?.moveToLocation(current.coord.lat, current.coord.lon);
      weatherMapRef.value?.updateMarker(current.coord.lat, current.coord.lon, current);
    }
 
    if (!farmPrefillComplete.value && rawFarms.value?.length) {
      farmPrefillComplete.value = true;
    }
    
    checkWeatherConditions({ current, history, forecast: forecastData });
    await fetchForecastWarnings({ forecast: forecastData });
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
  const prefilled = await initializeDefaultLocation();
  if (!prefilled) {
    await searchWeather();
  }
  await refreshFarmLayers();
  await initializeAlertsPanel();
};

const initializeDefaultLocation = async () => {
  if (rawFarms.value?.length) {
    const firstFarm = rawFarms.value[0];
    const name = firstFarm?.farm_name || DEFAULT_LOCATION;
    searchLocation.value = name;

    const lat = parseFloat(firstFarm?.latitude);
    const lon = parseFloat(firstFarm?.longitude);
    if (!Number.isNaN(lat) && !Number.isNaN(lon)) {
      try {
        const { current, history, forecastData } = await fetchWeatherByCoordinates(lat, lon, { days: 16 });
        currentWeather.value = current;
        const previousTimeline = fullForecastTimeline.value;
        fullForecastTimeline.value = createWeatherTimeline(history, current, forecastData, { reuseHistory: previousTimeline });
        forecast.value = createWeatherTimeline(history, current, forecastData, { windowSize: 7, reuseHistory: previousTimeline });
        latestForecastData.value = forecastData;
        weatherMapRef.value?.moveToLocation(lat, lon);
        weatherMapRef.value?.updateMarker(lat, lon, current);
        checkWeatherConditions({ current, history, forecast: forecastData });
        await fetchForecastWarnings({ forecast: forecastData });
        farmPrefillComplete.value = true;
        return true;
      } catch (error) {
        console.warn('Failed to prefill weather for first farm coordinates:', error);
      }
    }
  }

  searchLocation.value = DEFAULT_LOCATION;
  return false;
};

onMounted(async () => {
  await ensureApiToken(window.axios);
  const prefilled = await initializeDefaultLocation();
  if (!prefilled) {
    await searchWeather();
  }
  window.vueApp = {
    setActiveView,
    searchWeather,
    searchLocation
  };
  await refreshFarmLayers();
  await initializeAlertsPanel();
});

watch([farmFeatures, pointFeatures], () => {
  renderFarmLayers();
});

const refreshFarmLayers = async ({ reloadData = true } = {}) => {
  if (reloadData) {
    await loadMapData();
    if (!farmPrefillComplete.value) {
      const prefilled = await initializeDefaultLocation();
      if (!prefilled) {
        await searchWeather();
      }
    }
  }
  renderFarmLayers();
};

const renderFarmLayers = () => {
  weatherMapRef.value?.renderFarmOverlays({
    farmFeatures: farmFeatures.value,
    pointFeatures: pointFeatures.value
  });
};

const createFarm = async (payload) => {
  await axios.post('/api/farms', payload);
  await refreshFarmLayers();
};

const updateFarmDetails = async (farmId, payload) => {
  await axios.patch(`/api/farms/${farmId}`, payload);
  await refreshFarmLayers();
};

const removeFarmBoundary = async (farmId) => {
  await axios.patch(`/api/farms/${farmId}`, { boundary: null });
  await refreshFarmLayers();
};

const startBoundaryEditing = (farmId) => {
  if (activeView.value !== 'map') {
    setActiveView('map');
  }

  const farm = rawFarms.value.find(f => f.farm_id === farmId);
  if (!farm) return;

  if (boundarySession.value?.cancel) {
    boundarySession.value.cancel();
  }

  focusMapOnFarm(farm);
  updateWeatherForLocation(farm.latitude, farm.longitude, farm.farm_name);

  const initialCoordinates = extractBoundaryCoordinates(farm.boundary);

  boundarySession.value = weatherMapRef.value?.startBoundaryDrawing({
    initialCoordinates,
    onComplete: async (coords) => {
      await axios.patch(`/api/farms/${farmId}`, { boundary: coords });
      boundarySession.value = null;
      await refreshFarmLayers();
    },
    onCancel: () => {
      boundarySession.value = null;
    }
  });
};

const focusMapOnFarm = (farm) => {
  if (!farm) return;

  const lat = parseFloat(farm.latitude);
  const lng = parseFloat(farm.longitude);

  if (Number.isFinite(lat) && Number.isFinite(lng)) {
    weatherMapRef.value?.moveToLocation(lat, lng, 14);
    const latestWeather = farm.weather_data?.[0];

    const markerWeather = latestWeather ?? (currentWeather.value && farm.farm_name === currentWeather.value?.name
      ? {
          temperature: currentWeather.value.main?.temp,
          humidity: currentWeather.value.main?.humidity,
          pressure: currentWeather.value.main?.pressure,
          wind_speed: currentWeather.value.wind?.speed,
          condition: currentWeather.value.weather?.[0]?.main,
          icon: currentWeather.value.weather?.[0]?.icon,
        }
      : null);

    const condition = markerWeather?.condition
      || latestWeather?.condition
      || currentWeather.value?.weather?.[0]?.main
      || 'Farm';

    const description = markerWeather?.description
      || (farm.soil_type ? `${farm.soil_type} soil` : 'Farm location');

    weatherMapRef.value?.updateMarker(lat, lng, {
      name: farm.farm_name,
      main: {
        temp: markerWeather?.temperature ?? currentWeather.value?.main?.temp ?? 0,
        humidity: markerWeather?.humidity ?? currentWeather.value?.main?.humidity ?? 0,
        pressure: markerWeather?.pressure ?? currentWeather.value?.main?.pressure ?? 0,
      },
      weather: [
        {
          main: condition,
          description,
          icon: markerWeather?.icon ?? currentWeather.value?.weather?.[0]?.icon ?? '01d'
        }
      ],
      wind: {
        speed: markerWeather?.wind_speed ?? currentWeather.value?.wind?.speed ?? 0
      },
    });
  }
};

const updateWeatherForLocation = async (lat, lng, name = null) => {
  if (!Number.isFinite(parseFloat(lat)) || !Number.isFinite(parseFloat(lng))) {
    return;
  }

  isLoadingWeather.value = true;

  try {
    const { current, history, forecastData } = await fetchWeatherByCoordinates(lat, lng, { days: 16 });
    currentWeather.value = {
      ...current,
      name: name ?? current?.name ?? 'Farm Location'
    };
    const previousTimeline = fullForecastTimeline.value;
    fullForecastTimeline.value = createWeatherTimeline(history, currentWeather.value, forecastData, { reuseHistory: previousTimeline });
    forecast.value = createWeatherTimeline(history, currentWeather.value, forecastData, { windowSize: 7, reuseHistory: previousTimeline });
    latestForecastData.value = forecastData;
    checkWeatherConditions({ current: currentWeather.value, history, forecast: forecastData });
    await fetchForecastWarnings({ forecast: forecastData });

    suppressNextLocationUpdate = true;
    weatherMapRef.value?.updateMarker(lat, lng, currentWeather.value);
    if (selectedDay.value?.date) {
      hydrateSelectedDay(selectedDay.value.date);
    }
  } catch (error) {
    console.error('Error updating weather for farm location:', error);
  } finally {
    isLoadingWeather.value = false;
  }
};

const finishBoundaryEditing = () => {
  boundarySession.value?.finish?.();
};

const cancelBoundaryEditing = () => {
  boundarySession.value?.cancel?.();
  boundarySession.value = null;
};

const extractBoundaryCoordinates = (boundary) => {
  if (!boundary?.coordinates?.[0]) {
    return [];
  }

  return boundary.coordinates[0]
    .slice(0, -1)
    .map(([lng, lat]) => ({ lat, lng }));
};

const startPointPlacement = (farmId, payload) => {
  if (activeView.value !== 'map') {
    setActiveView('map');
  }

  if (pointSession.value) {
    weatherMapRef.value?.cancelPointPlacement();
    pointSession.value = null;
  }

  pointSession.value = {
    farmId,
    payload
  };

  weatherMapRef.value?.startPointPlacement({
    onPlace: async ({ lat, lng }) => {
      try {
        await axios.post(`/api/farms/${farmId}/points`, {
          ...payload,
          latitude: lat,
          longitude: lng,
        });
        await refreshFarmLayers();
      } finally {
        pointSession.value = null;
      }
    }
  });
};

const cancelPointPlacement = () => {
  weatherMapRef.value?.cancelPointPlacement();
  pointSession.value = null;
};

const handleLocationUpdate = ({ lat, lon }) => {
  if (suppressNextLocationUpdate) {
    suppressNextLocationUpdate = false;
    return;
  }

  if (!pointSession.value && !boundarySession.value) {
    return;
  }

  updateWeatherForLocation(lat, lon);
};

const initializeAlertsPanel = async () => {
  if (alertsInitialized.value) return;
  alertsInitialized.value = true;
  try {
    await Promise.all([
      fetchActiveAlerts(),
      loadSettings()
    ]);
    if (!latestForecastData.value) {
      await refreshForecastWarnings();
    }
  } catch (error) {
    console.error('Failed to initialize alerts data:', error);
  }
};

const refreshForecastWarnings = async () => {
  try {
    if (latestForecastData.value) {
      await fetchForecastWarnings({ forecast: latestForecastData.value });
      return;
    }

    const targetLocation = searchLocation.value || 'Butuan, Caraga, PH';
    const { forecastData } = await fetchWeatherByLocation(targetLocation);
    latestForecastData.value = forecastData;
    await fetchForecastWarnings({ forecast: forecastData });
  } catch (error) {
    console.error('Failed to refresh forecast warnings:', error);
    forecastWarnings.value = [];
  }
};

const handleNotificationSettingUpdate = async (settingName, value) => {
  try {
    await updateSetting(settingName, value);
    settingsSaved.value = true;
    setTimeout(() => {
      settingsSaved.value = false;
    }, 3000);
  } catch (error) {
    console.error('Failed to update notification setting:', error);
    throw error;
  }
};

const handleCreateAlert = async (payload) => {
  try {
    const alert = await createAlert(payload);
    showSuccess('Alert Created', 'The alert has been added successfully.');
    return alert;
  } catch (error) {
    console.error('Failed to create alert:', error);
    showError('Alert Creation Failed', 'Unable to create the alert. Please try again.');
    throw error;
  }
};

const handleTimelineSelection = (day) => {
  if (!day?.date) return;
  selectedDayDetail.value = null;
  selectedDayHourly.value = [];
  selectedDay.value = day;
  hydrateSelectedDay(day.date);
};

const hydrateSelectedDay = (date) => {
  if (!date || !latestForecastData.value) {
    selectedDayDetail.value = null;
    selectedDayHourly.value = [];
    return;
  }

  const detail = latestForecastData.value.find((entry) => {
    if (!entry) return false;
    if (entry.date) {
      return entry.date === date;
    }

    if (entry.dt) {
      const entryDate = new Date(entry.dt * 1000);
      const targetDate = new Date(date);
      return entryDate.getFullYear() === targetDate.getFullYear()
        && entryDate.getMonth() === targetDate.getMonth()
        && entryDate.getDate() === targetDate.getDate();
    }

    return false;
  });

  const aggregatedEntry = fullForecastTimeline.value?.find?.((entry) => entry?.date === date) || null;

  selectedDayDetail.value = detail || aggregatedEntry || null;

  const hourlySource = detail?.hourly
    || detail?.hours
    || detail?.data
    || aggregatedEntry?.hourly
    || [];
  selectedDayHourly.value = Array.isArray(hourlySource) ? hourlySource : [];
};

const closeDayDetail = () => {
  selectedDay.value = null;
  selectedDayDetail.value = null;
  selectedDayHourly.value = [];
};
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

.farm-management-panel {
  position: absolute;
  top: 20px;
  left: 20px;
  z-index: 5;
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