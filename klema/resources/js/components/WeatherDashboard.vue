<template>
  <div class="weather-dashboard">
    <SearchBar 
      v-if="activeView === 'map' && !selectedDayDetail && !isDrawingBoundary"
      v-model="searchLocation"
      @search="searchWeather"
      :is-loading="isLoadingWeather"
    />
    
    <ClickInstruction v-if="activeView === 'map' && !selectedDayDetail && !isDrawingBoundary" />
    
    <WeatherLayerControls 
      v-if="activeView === 'map' && !selectedDayDetail && !isDrawingBoundary"
      @toggle-layer="handleLayerToggle"
      @change-base-layer="handleBaseLayerChange" 
    />
    
    <!-- Drawing Mode Controls -->
    <div v-if="isDrawingBoundary" class="drawing-controls">
      <div class="drawing-controls__header">
        <i class="fas fa-draw-polygon"></i>
        <h3>Drawing Boundary</h3>
      </div>
      <div class="drawing-controls__info">
        <p>
          <i class="fas fa-mouse-pointer"></i>
          Click on the map to add vertices to the boundary
        </p>
        <p class="point-count">
          Points: <strong>{{ boundaryPointCount }}</strong>
          <span v-if="boundaryPointCount < 3" class="warning"> (minimum 3 required)</span>
        </p>
      </div>
      <div class="drawing-controls__actions">
        <button 
          class="drawing-controls__button drawing-controls__button--danger"
          @click="deleteLastBoundaryPoint"
          :disabled="boundaryPointCount === 0"
          title="Delete last point"
        >
          <i class="fas fa-undo"></i>
          Delete Last Point
        </button>
        <button 
          class="drawing-controls__button drawing-controls__button--success"
          @click="finishBoundaryEditing"
          :disabled="boundaryPointCount < 3"
          title="Finish drawing (requires at least 3 points)"
        >
          <i class="fas fa-check"></i>
          Finish Drawing
        </button>
        <button 
          class="drawing-controls__button drawing-controls__button--cancel"
          @click="cancelBoundaryEditing"
          title="Cancel drawing"
        >
          <i class="fas fa-times"></i>
          Cancel
        </button>
      </div>
    </div>

    <div class="map-wrapper">
      <WeatherMap
        ref="weatherMapRef"
        @map-click="handleMapClick"
        @map-ready="handleMapReady"
        @location-update="handleLocationUpdate"
      />

      <LoadingIndicator
        v-if="(mapLoading || isLoadingWeather) && activeView === 'map'"
        :message="getLoadingMessage()"
        :subtitle="getLoadingSubtitle()"
        icon="🌤️"
      />

      <div v-if="activeView !== 'map' || selectedDayDetail" class="map-overlay"></div>
    </div>
    
    <WeatherTimeline
      v-if="activeView === 'map' && !selectedDayDetail && !isDrawingBoundary"
      :forecast="forecastTimeline"
      :get-day-label="getDayLabel"
      :get-weather-icon="getWeatherIcon"
      @day-selected="handleTimelineSelection"
    />
    
    <TimelineLegend v-if="activeView === 'map' && !selectedDayDetail && !isDrawingBoundary" />

    <transition name="overlay-fade">
      <div v-if="activeView !== 'map' && !selectedDayDetail" class="overlay-wrapper">
        <div class="overlay-panel">
          <div class="overlay-content">
            <Transition name="view-slide-fade" mode="out-in">
              <component
                v-if="overlayViewConfig.component"
                :is="overlayViewConfig.component"
                v-bind="overlayViewConfig.props"
                :key="overlayViewConfig.key"
              />
            </Transition>
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
import { ref, onMounted, watch, computed, nextTick } from 'vue';
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
import ExportView from './Views/ExportView.vue';
import DayDetailView from './Views/DayDetailView.vue';
import GlobalAlertNotification from './GlobalAlertNotification.vue';
import { useWeatherAPI } from '../composables/useWeatherAPI';
import { useWeatherUtils } from '../composables/useWeatherUtils';
import { useGlobalAlerts } from '../composables/useGlobalAlerts';
import { useFarmMap } from '../composables/useFarmMap';
import { ensureApiToken } from '../services/auth';
import { useAlerts } from '../composables/useAlerts';
import { 
  useNotificationSettings,
  DEFAULT_NOTIFICATION_SETTINGS,
  SUPPORTED_NOTIFICATION_SETTING_KEYS
} from '../composables/useNotificationSettings';

const weatherMapRef = ref(null);
const DEFAULT_LOCATION = 'Northern Mindanao';
const INITIAL_HISTORY_DAYS = 3;
const INITIAL_FORECAST_DAYS = 4;
const MAX_HISTORY_WINDOW = 90; // Increased from 30 to 90 days
const MAX_FORECAST_WINDOW = 16;
const searchLocation = ref(DEFAULT_LOCATION);
const forecast = ref([]);
const fullForecastTimeline = ref([]);
const currentWeather = ref(null);
const mapLoading = ref(true);
const isLoadingWeather = ref(false);
const activeView = ref('map');
const boundarySession = ref(null);
const pointSession = ref(null);
const isDrawingBoundary = computed(() => !!boundarySession.value);
const boundaryPointCount = ref(0);
let suppressNextLocationUpdate = false;
const latestHistoryData = ref([]);
const latestForecastData = ref([]);
const farmPrefillComplete = ref(false);
const selectedDay = ref(null);
const selectedDayDetail = ref(null);
const selectedDayHourly = ref([]);
const isExtendingHistory = ref(false);
const activeHistoryFetchKey = ref(null);
const currentHistoryContext = ref(null);
const isExtendingForecast = ref(false);
const activeForecastFetchKey = ref(null);
const currentForecastContext = ref(null);

const { 
  fetchWeatherByLocation, 
  fetchWeatherByCoordinates,
  createWeatherTimeline,
  fetchWeatherHistory,
  fetchWeatherForecast
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

const snapshotNotificationPreferences = () => {
  return Array.from(SUPPORTED_NOTIFICATION_SETTING_KEYS).reduce((acc, key) => {
    const currentValue = notificationSettings[key];
    if (typeof currentValue === 'boolean') {
      acc[key] = currentValue;
    } else {
      acc[key] = DEFAULT_NOTIFICATION_SETTINGS[key];
    }
    return acc;
  }, {});
};

const normalizeForecastArray = (source) => {
  if (Array.isArray(source)) {
    return source;
  }

  if (Array.isArray(source?.forecast)) {
    return source.forecast;
  }

  if (Array.isArray(source?.daily)) {
    return source.daily;
  }

  if (Array.isArray(source?.list)) {
    return source.list;
  }

  return [];
};

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

const currentUser = ref(null);
const systemStats = ref(null);

// Fetch user info and system stats
const fetchUserInfo = async () => {
  try {
    await ensureApiToken(axios);
    const response = await axios.get('/api/auth/me');
    if (response.data?.user) {
      currentUser.value = response.data.user;
      
      // Fetch system stats
      try {
        const statsResponse = await axios.get('/api/admin/stats');
        if (statsResponse.data?.success && statsResponse.data?.stats) {
          systemStats.value = statsResponse.data.stats;
        }
      } catch (err) {
        console.warn('Could not fetch system stats:', err);
      }
    }
  } catch (error) {
    console.warn('Could not fetch user info:', error);
  }
};

const locateFarm = (farmId) => {
  const farm = rawFarms.value.find(f => f.farm_id === farmId);
  if (!farm) return;

  // Switch to map view if not already there
  if (activeView.value !== 'map') {
    setActiveView('map');
    // Wait for the map view to be rendered
    nextTick(() => {
      setTimeout(() => {
        if (weatherMapRef.value) {
          focusMapOnFarm(farm);
          updateWeatherForLocation(farm.latitude, farm.longitude, farm.farm_name);
        }
      }, 150);
    });
  } else {
    // Map view is already active, focus immediately
    focusMapOnFarm(farm);
    updateWeatherForLocation(farm.latitude, farm.longitude, farm.farm_name);
  }
};

const overlayViewConfig = computed(() => {
  switch (activeView.value) {
    case 'dashboard':
      return {
        key: 'dashboard',
        component: DashboardView,
        props: {
          currentWeather: currentWeather.value,
          forecast: forecast.value,
          getDayLabel,
          getWeatherIcon,
          farms: rawFarms.value,
          soilTypes: soilTypes.value,
          farmsLoading: farmMapLoading.value,
          isDrawing: !!boundarySession.value,
          isPlacingPoint: !!pointSession.value,
          systemStats: systemStats.value,
          onRefreshFarms: refreshFarmLayers,
          onCreateFarm: createFarm,
          onSaveFarm: updateFarmDetails,
          onClearBoundary: removeFarmBoundary,
          onStartBoundary: startBoundaryEditing,
          onFinishBoundary: finishBoundaryEditing,
          onCancelBoundary: cancelBoundaryEditing,
          onStartPoint: startPointPlacement,
          onCancelPoint: cancelPointPlacement,
          onLocateFarm: locateFarm
        }
      };
    case 'calendar':
      return {
        key: 'calendar',
        component: CalendarView,
        props: {
          getWeatherIcon,
          timeline: fullForecastTimeline.value
        }
      };
    case 'alerts':
      return {
        key: 'alerts',
        component: AlertsView,
        props: {
          alerts: alerts.value,
          alertsLoading: alertsLoading.value,
          alertsError: alertsError.value,
          forecastWarnings: forecastWarnings.value,
          notificationSettings: notificationSettings,
          settingsLoading: settingsLoading.value,
          settingsError: settingsError.value,
          settingsSaved: settingsSaved.value,
          getAlertTypeInfo,
          formatAlertTime,
          getSettingInfo,
          resolveAlert,
          deleteAlert,
          updateNotificationSetting: handleNotificationSettingUpdate,
          fetchActiveAlerts,
          refreshForecastWarnings,
          loadNotificationSettings: loadSettings,
          createAlert: handleCreateAlert,
          farms: alertsFarms.value,
          locationLabel: currentLocationLabel.value
        }
      };
    case 'settings':
      return {
        key: 'settings',
        component: SettingsView,
        props: {
          farms: rawFarms.value
        }
      };
    case 'exports':
      return {
        key: 'exports',
        component: ExportView,
        props: {
          farms: rawFarms.value
        }
      };
    default:
      return {
        key: '',
        component: null,
        props: {}
      };
  }
});

const normalizeCoordinate = (value) => {
  if (value === null || value === undefined) {
    return null;
  }

  const numeric = Number(value);
  return Number.isFinite(numeric) ? numeric : null;
};

const buildBaseSource = ({ lat, lon, location }) => {
  const normalizedLat = normalizeCoordinate(lat);
  const normalizedLon = normalizeCoordinate(lon);
  const normalizedLocation = typeof location === 'string' ? location.trim() : '';

  const hasCoords = Number.isFinite(normalizedLat) && Number.isFinite(normalizedLon);
  const hasLocation = normalizedLocation.length > 0;

  if (!hasCoords && !hasLocation) {
    return null;
  }

  return {
    lat: hasCoords ? normalizedLat : null,
    lon: hasCoords ? normalizedLon : null,
    location: hasLocation ? normalizedLocation : null
  };
};

const buildHistorySource = ({ lat, lon, location, farmId, initialHistoryLength = 0 }) => {
  const base = buildBaseSource({ lat, lon, location });
  if (!base) {
    return null;
  }

  // If farmId not provided but we have coordinates, try to find matching farm
  let resolvedFarmId = farmId;
  if (!resolvedFarmId && base.lat !== null && base.lon !== null && rawFarms.value?.length) {
    const matchingFarm = rawFarms.value.find(farm => {
      const farmLat = parseFloat(farm.latitude);
      const farmLon = parseFloat(farm.longitude);
      if (Number.isNaN(farmLat) || Number.isNaN(farmLon)) return false;
      // Match if within ~0.1 degrees (roughly 11km)
      return Math.abs(farmLat - base.lat) < 0.1 && Math.abs(farmLon - base.lon) < 0.1;
    });
    if (matchingFarm) {
      resolvedFarmId = matchingFarm.farm_id;
    }
  }

  return {
    ...base,
    farmId: resolvedFarmId,
    initialHistoryLength
  };
};

const buildForecastSource = ({ lat, lon, location, initialForecastLength = 0 }) => {
  const base = buildBaseSource({ lat, lon, location });
  if (!base) {
    return null;
  }

  return {
    ...base,
    initialForecastLength
  };
};

const buildSourceKey = (source) => {
  if (!source) {
    return null;
  }

  if (Number.isFinite(source.lat) && Number.isFinite(source.lon)) {
    return `coord:${source.lat.toFixed(4)},${source.lon.toFixed(4)}`;
  }

  if (source.location) {
    return `loc:${source.location.toLowerCase()}`;
  }

  return null;
};

const refreshTimeline = (history, forecastData, reuseTimeline = fullForecastTimeline.value) => {
  const effectiveHistory = Array.isArray(history)
    ? history
    : (Array.isArray(latestHistoryData.value) ? latestHistoryData.value : []);
  const effectiveForecast = Array.isArray(forecastData)
    ? forecastData
    : (Array.isArray(latestForecastData.value) ? latestForecastData.value : []);

  latestHistoryData.value = Array.isArray(effectiveHistory) ? [...effectiveHistory] : [];
  latestForecastData.value = Array.isArray(effectiveForecast) ? [...effectiveForecast] : [];

  const timelineOptions = {
    historyWindow: MAX_HISTORY_WINDOW,
    reuseHistory: reuseTimeline ?? fullForecastTimeline.value
  };

  fullForecastTimeline.value = createWeatherTimeline(
    latestHistoryData.value,
    currentWeather.value,
    latestForecastData.value,
    timelineOptions
  );
  forecast.value = createWeatherTimeline(
    latestHistoryData.value,
    currentWeather.value,
    latestForecastData.value,
    { windowSize: 7, ...timelineOptions }
  );
};

// Computed property for bottom panel timeline - use fullForecastTimeline but slice to 7 days for display
// This ensures the bottom panel shows the same data as calendar and dashboard
const forecastTimeline = computed(() => {
  if (!Array.isArray(fullForecastTimeline.value) || fullForecastTimeline.value.length === 0) {
    return [];
  }
  
  // Find today's index
  const todayIndex = fullForecastTimeline.value.findIndex(entry => entry?.isToday);
  if (todayIndex === -1) {
    // If today not found, return first 7 days
    return fullForecastTimeline.value.slice(0, 7);
  }
  
  // Return 7 days centered around today
  // We want 3 days before, today, and 3 days after (total 7)
  const halfWindow = 3; // Days before/after today
  const totalDays = 7;
  
  // Calculate start position to center today
  let start = todayIndex - halfWindow;
  
  // Adjust if we're too close to the beginning
  if (start < 0) {
    start = 0;
  }
  
  // Adjust if we're too close to the end
  const maxStart = fullForecastTimeline.value.length - totalDays;
  if (maxStart > 0 && start > maxStart) {
    start = maxStart;
  }
  
  const end = Math.min(fullForecastTimeline.value.length, start + totalDays);
  return fullForecastTimeline.value.slice(start, end);
});

const scheduleExtendedHistoryFetch = (source) => {
  if (!source) {
    currentHistoryContext.value = null;
    return;
  }

  const key = buildSourceKey(source);
  currentHistoryContext.value = key;

  if (!key) {
    return;
  }

  if (source.initialHistoryLength >= MAX_HISTORY_WINDOW) {
    return;
  }

  if (Array.isArray(fullForecastTimeline.value)) {
    const existingHistoryCount = fullForecastTimeline.value.filter(
      (entry) => entry?.isHistory && !entry?.noData
    ).length;

    if (existingHistoryCount >= MAX_HISTORY_WINDOW) {
      return;
    }
  }

  if (activeHistoryFetchKey.value === key) {
    return;
  }

  activeHistoryFetchKey.value = key;
  isExtendingHistory.value = true;

  fetchWeatherHistory({
    lat: source.lat ?? undefined,
    lon: source.lon ?? undefined,
    location: source.location ?? undefined,
    farmId: source.farmId ?? undefined,
    days: MAX_HISTORY_WINDOW
  })
    .then((extendedHistory) => {
      if (currentHistoryContext.value !== key) {
        return;
      }

      if (Array.isArray(extendedHistory) && extendedHistory.length) {
        refreshTimeline(extendedHistory, latestForecastData.value, fullForecastTimeline.value);
        if (selectedDay.value?.date) {
          hydrateSelectedDay(selectedDay.value.date);
        }
      }
    })
    .catch((error) => {
      console.warn('Failed to extend weather history:', error);
    })
    .finally(() => {
      if (activeHistoryFetchKey.value === key) {
        activeHistoryFetchKey.value = null;
        isExtendingHistory.value = false;
      }
    });
};

const scheduleExtendedForecastFetch = (source) => {
  if (!source) {
    currentForecastContext.value = null;
    return;
  }

  const key = buildSourceKey(source);
  currentForecastContext.value = key;

  if (!key) {
    return;
  }

  if (source.initialForecastLength >= MAX_FORECAST_WINDOW) {
    return;
  }

  if (Array.isArray(fullForecastTimeline.value)) {
    const existingForecastCount = fullForecastTimeline.value.filter(
      (entry) => entry?.isFuture
    ).length;

    if (existingForecastCount >= MAX_FORECAST_WINDOW) {
      return;
    }
  }

  if (activeForecastFetchKey.value === key) {
    return;
  }

  activeForecastFetchKey.value = key;
  isExtendingForecast.value = true;

  fetchWeatherForecast({
    lat: source.lat ?? undefined,
    lon: source.lon ?? undefined,
    location: source.location ?? undefined,
    days: MAX_FORECAST_WINDOW
  })
    .then((extendedForecast) => {
      if (currentForecastContext.value !== key) {
        return;
      }

      if (Array.isArray(extendedForecast) && extendedForecast.length) {
        refreshTimeline(latestHistoryData.value, extendedForecast, fullForecastTimeline.value);
      }
    })
    .catch((error) => {
      console.warn('Failed to extend weather forecast:', error);
    })
    .finally(() => {
      if (activeForecastFetchKey.value === key) {
        activeForecastFetchKey.value = null;
        isExtendingForecast.value = false;
      }
    });
};

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
  
  // Update sidebar active state to match
  const viewMap = {
    'dashboard': 0,
    'map': 1,
    'calendar': 2,
    'alerts': 3,
    'exports': 4,
    'settings': 5
  };
  
  const sidebar = document.getElementById('sidebar');
  if (sidebar) {
    const icons = sidebar.querySelectorAll('.navbar-icon');
    icons.forEach((icon, index) => {
      icon.classList.remove('active');
      if (index === viewMap[view]) {
        icon.classList.add('active');
      }
    });
  }
};

const handleLayerToggle = ({ layerId, active }) => {
  weatherMapRef.value?.toggleWeatherLayer(layerId, active);
};

const handleBaseLayerChange = ({ layerId }) => {
  weatherMapRef.value?.setBaseLayer(layerId);
};

const handleMapClick = async ({ lat, lng }) => {
  // If drawing boundary, update point count after a small delay to allow point to be added
  if (isDrawingBoundary.value) {
    setTimeout(() => {
      updateBoundaryPointCount();
    }, 50);
    return;
  }
  
  // If placing point, don't update weather
  if (pointSession.value) {
    return;
  }
  
  isLoadingWeather.value = true;
  try {
    const { current, history, forecastData } = await fetchWeatherByCoordinates(
      lat,
      lng,
      { days: INITIAL_FORECAST_DAYS, historyDays: INITIAL_HISTORY_DAYS }
    );
    currentWeather.value = current;
    refreshTimeline(history, forecastData, fullForecastTimeline.value);

    if (current.name) {
      searchLocation.value = `${current.name}, ${current.sys.country}`;
    }

    // Try to find matching farm by coordinates
    let clickedFarmId = null;
    if (rawFarms.value?.length) {
      const matchingFarm = rawFarms.value.find(farm => {
        const farmLat = parseFloat(farm.latitude);
        const farmLon = parseFloat(farm.longitude);
        if (Number.isNaN(farmLat) || Number.isNaN(farmLon)) return false;
        // Match if within ~0.1 degrees (roughly 11km)
        return Math.abs(farmLat - lat) < 0.1 && Math.abs(farmLon - lng) < 0.1;
      });
      if (matchingFarm) {
        clickedFarmId = matchingFarm.farm_id;
      }
    }

    const historySource = buildHistorySource({
      lat,
      lon: lng,
      location: current?.name ?? searchLocation.value,
      farmId: clickedFarmId,
      initialHistoryLength: Array.isArray(history) ? history.length : 0
    });
    scheduleExtendedHistoryFetch(historySource);
    const forecastSource = buildForecastSource({
      lat,
      lon: lng,
      location: current?.name ?? searchLocation.value,
      initialForecastLength: Array.isArray(forecastData) ? forecastData.length : 0
    });
    scheduleExtendedForecastFetch(forecastSource);

    weatherMapRef.value?.updateMarker(lat, lng, current);
    checkWeatherConditions(
      { current, history, forecast: forecastData },
      snapshotNotificationPreferences()
    );
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
      ({ current, history, forecastData } = await fetchWeatherByCoordinates(
        farmMatch.lat,
        farmMatch.lon,
        { days: INITIAL_FORECAST_DAYS, historyDays: INITIAL_HISTORY_DAYS }
      ));
    } else {
      ({ current, history, forecastData } = await fetchWeatherByLocation(
        trimmed,
        { days: INITIAL_FORECAST_DAYS, historyDays: INITIAL_HISTORY_DAYS }
      ));
    }

    currentWeather.value = current;
    refreshTimeline(history, forecastData, fullForecastTimeline.value);

    // Find farm_id if we matched a farm
    let matchedFarmId = null;
    if (farmMatch && rawFarms.value?.length) {
      const matchedFarm = rawFarms.value.find(f => 
        f.farm_name?.toLowerCase() === trimmed.toLowerCase()
      );
      if (matchedFarm) {
        matchedFarmId = matchedFarm.farm_id;
      }
    }

    const historySource = buildHistorySource({
      lat: farmMatch ? farmMatch.lat : current?.coord?.lat,
      lon: farmMatch ? farmMatch.lon : current?.coord?.lon,
      location: trimmed,
      farmId: matchedFarmId,
      initialHistoryLength: Array.isArray(history) ? history.length : 0
    });
    scheduleExtendedHistoryFetch(historySource);
    const forecastSource = buildForecastSource({
      lat: farmMatch ? farmMatch.lat : current?.coord?.lat,
      lon: farmMatch ? farmMatch.lon : current?.coord?.lon,
      location: trimmed,
      initialForecastLength: Array.isArray(forecastData) ? forecastData.length : 0
    });
    scheduleExtendedForecastFetch(forecastSource);

    if (current.coord) {
      weatherMapRef.value?.moveToLocation(current.coord.lat, current.coord.lon);
      weatherMapRef.value?.updateMarker(current.coord.lat, current.coord.lon, current);
    }
 
    if (!farmPrefillComplete.value && rawFarms.value?.length) {
      farmPrefillComplete.value = true;
    }
    
    checkWeatherConditions(
      { current, history, forecast: forecastData },
      snapshotNotificationPreferences()
    );
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
  
  // If we haven't initialized location yet (farms might not be loaded), try now
  if (!farmPrefillComplete.value) {
    const prefilled = await initializeDefaultLocation();
    if (!prefilled) {
      await searchWeather();
    }
  }
  
  await initializeAlertsPanel();
};

const loadAppSettings = () => {
  if (typeof window === 'undefined') {
    return null;
  }
  
  try {
    const stored = window.localStorage?.getItem('appSettings');
    if (stored) {
      return JSON.parse(stored);
    }
  } catch (error) {
    console.error('Failed to load app settings:', error);
  }
  
  return null;
};

const initializeDefaultLocation = async () => {
  // Load saved settings
  const appSettings = loadAppSettings();
  
  // Check if user has saved location preferences
  if (appSettings) {
    // If location type is 'farm' and a farm is selected
    if (appSettings.locationType === 'farm' && appSettings.selectedFarmId && rawFarms.value?.length) {
      const selectedFarm = rawFarms.value.find(f => f.farm_id === appSettings.selectedFarmId);
      if (selectedFarm) {
        const name = selectedFarm?.farm_name || DEFAULT_LOCATION;
        searchLocation.value = name;

        const lat = parseFloat(selectedFarm?.latitude);
        const lon = parseFloat(selectedFarm?.longitude);
        if (!Number.isNaN(lat) && !Number.isNaN(lon)) {
          try {
            await updateWeatherForLocation(lat, lon, name);
            if (weatherMapRef.value) {
              weatherMapRef.value.moveToLocation(lat, lon, 12);
            }
            farmPrefillComplete.value = true;
            return true;
          } catch (error) {
            console.error('Failed to load weather for selected farm:', error);
          }
        }
      }
    }
    
    // If location type is 'custom' and defaultLocation is set
    if (appSettings.locationType === 'custom' && appSettings.defaultLocation) {
      const location = appSettings.defaultLocation.trim();
      if (location) {
        searchLocation.value = location;
        // Check if it's coordinates (format: "lat, lng" or "lat,lng")
        const coordMatch = location.match(/^(-?\d+\.?\d*),\s*(-?\d+\.?\d*)$/);
        if (coordMatch) {
          const lat = parseFloat(coordMatch[1]);
          const lon = parseFloat(coordMatch[2]);
          if (!Number.isNaN(lat) && !Number.isNaN(lon)) {
            try {
              await updateWeatherForLocation(lat, lon);
              if (weatherMapRef.value) {
                weatherMapRef.value.moveToLocation(lat, lon, 12);
              }
              farmPrefillComplete.value = true;
              return true;
            } catch (error) {
              console.error('Failed to load weather for coordinates:', error);
            }
          }
        } else {
          // It's a location name, use searchWeather
          return false; // Let searchWeather handle it
        }
      }
    }
  }
  
  // Fallback to first farm if available
  if (rawFarms.value?.length) {
    const firstFarm = rawFarms.value[0];
    const name = firstFarm?.farm_name || DEFAULT_LOCATION;
    searchLocation.value = name;

    const lat = parseFloat(firstFarm?.latitude);
    const lon = parseFloat(firstFarm?.longitude);
    if (!Number.isNaN(lat) && !Number.isNaN(lon)) {
      try {
        const { current, history, forecastData } = await fetchWeatherByCoordinates(
          lat,
          lon,
          { days: INITIAL_FORECAST_DAYS, historyDays: INITIAL_HISTORY_DAYS }
        );
        currentWeather.value = current;
        refreshTimeline(history, forecastData, fullForecastTimeline.value);
        const historySource = buildHistorySource({
          lat,
          lon,
          location: name,
          farmId: firstFarm?.farm_id,
          initialHistoryLength: Array.isArray(history) ? history.length : 0
        });
        scheduleExtendedHistoryFetch(historySource);
        const forecastSource = buildForecastSource({
          lat,
          lon,
          location: name,
          initialForecastLength: Array.isArray(forecastData) ? forecastData.length : 0
        });
        scheduleExtendedForecastFetch(forecastSource);
        weatherMapRef.value?.moveToLocation(lat, lon);
        weatherMapRef.value?.updateMarker(lat, lon, current);
        checkWeatherConditions(
          { current, history, forecast: forecastData },
          snapshotNotificationPreferences()
        );
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
  await fetchUserInfo();
  await ensureApiToken(window.axios);
  
  // Load farms first so we can use saved farm selection
  await refreshFarmLayers({ reloadData: true });
  
  // Now initialize default location (which can use saved settings)
  const prefilled = await initializeDefaultLocation();
  if (!prefilled) {
    await searchWeather();
  }
  
  window.vueApp = {
    setActiveView,
    searchWeather,
    searchLocation
  };
  
  await initializeAlertsPanel();
});

watch([farmFeatures, pointFeatures], () => {
  renderFarmLayers();
});

const refreshFarmLayers = async ({ reloadData = true } = {}) => {
  if (reloadData) {
    await loadMapData();
    // After loading farms, try to initialize default location if not done yet
    // This ensures saved farm selection works even if farms load after initial mount
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
      boundaryPointCount.value = 0;
      await refreshFarmLayers();
    },
    onCancel: () => {
      boundarySession.value = null;
      boundaryPointCount.value = 0;
    }
  });
  
  // Update point count immediately after starting
  nextTick(() => {
    updateBoundaryPointCount();
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
    const { current, history, forecastData } = await fetchWeatherByCoordinates(
      lat,
      lng,
      { days: INITIAL_FORECAST_DAYS, historyDays: INITIAL_HISTORY_DAYS }
    );
    currentWeather.value = {
      ...current,
      name: name ?? current?.name ?? 'Farm Location'
    };
    refreshTimeline(history, forecastData, fullForecastTimeline.value);
    const historySource = buildHistorySource({
      lat,
      lon: lng,
      location: currentWeather.value.name,
      initialHistoryLength: Array.isArray(history) ? history.length : 0
    });
    scheduleExtendedHistoryFetch(historySource);
    const forecastSource = buildForecastSource({
      lat,
      lon: lng,
      location: currentWeather.value.name,
      initialForecastLength: Array.isArray(forecastData) ? forecastData.length : 0
    });
    scheduleExtendedForecastFetch(forecastSource);
    checkWeatherConditions(
      { current: currentWeather.value, history, forecast: forecastData },
      snapshotNotificationPreferences()
    );
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
  boundaryPointCount.value = 0;
};

const cancelBoundaryEditing = () => {
  boundarySession.value?.cancel?.();
  boundarySession.value = null;
  boundaryPointCount.value = 0;
};

const deleteLastBoundaryPoint = () => {
  if (weatherMapRef.value?.deleteLastBoundaryVertex) {
    weatherMapRef.value.deleteLastBoundaryVertex();
    updateBoundaryPointCount();
  }
};

const updateBoundaryPointCount = () => {
  if (weatherMapRef.value?.getBoundaryPointCount) {
    boundaryPointCount.value = weatherMapRef.value.getBoundaryPointCount();
  }
};

// Watch for boundary point changes - update count periodically while drawing
let boundaryPointInterval = null;
watch(isDrawingBoundary, (isDrawing) => {
  if (isDrawing) {
    updateBoundaryPointCount();
    
    // Update point count periodically while drawing
    if (boundaryPointInterval) {
      clearInterval(boundaryPointInterval);
    }
    boundaryPointInterval = setInterval(() => {
      if (isDrawingBoundary.value) {
        updateBoundaryPointCount();
      } else {
        clearInterval(boundaryPointInterval);
        boundaryPointInterval = null;
      }
    }, 200);
  } else {
    if (boundaryPointInterval) {
      clearInterval(boundaryPointInterval);
      boundaryPointInterval = null;
    }
    boundaryPointCount.value = 0;
  }
}, { immediate: true });


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
  const preferences = snapshotNotificationPreferences();

  try {
    const hasCachedForecast = Array.isArray(latestForecastData.value) && latestForecastData.value.length > 0;
    if (hasCachedForecast) {
      await fetchForecastWarnings({ forecast: latestForecastData.value, preferences });
      return;
    }

    const targetLocation = searchLocation.value || 'Butuan, Caraga, PH';
    const { forecastData } = await fetchWeatherByLocation(targetLocation);
    const normalizedForecast = normalizeForecastArray(forecastData);
    latestForecastData.value = normalizedForecast;
    await fetchForecastWarnings({ forecast: normalizedForecast, preferences });
  } catch (error) {
    console.error('Failed to refresh forecast warnings:', error);
    forecastWarnings.value = [];
  }
};

const handleNotificationSettingUpdate = async (settingName, value) => {
  try {
    await updateSetting(settingName, value);
    await refreshForecastWarnings();
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

  const historyDetail = Array.isArray(latestHistoryData.value)
    ? latestHistoryData.value.find((entry) => entry?.date === date)
    : null;

  const aggregatedEntry = fullForecastTimeline.value?.find?.((entry) => entry?.date === date) || null;

  selectedDayDetail.value = detail || historyDetail || aggregatedEntry || null;

  const hourlySource = detail?.hourly
    || historyDetail?.hourly
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

const getLoadingMessage = () => {
  if (mapLoading.value && isLoadingWeather.value) {
    return 'Loading map and weather data...';
  } else if (mapLoading.value) {
    return 'Loading Interactive Weather Map...';
  } else if (isLoadingWeather.value) {
    return 'Fetching weather data...';
  }
  return 'Loading...';
};

const getLoadingSubtitle = () => {
  if (mapLoading.value && isLoadingWeather.value) {
    return 'Initializing map layers and fetching forecast...';
  } else if (mapLoading.value) {
    return 'Enhanced weather layers loading...';
  } else if (isLoadingWeather.value) {
    return 'Loading forecast for pinned location';
  }
  return '';
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

/* Drawing Controls */
.drawing-controls {
  position: fixed;
  top: 20px;
  right: 20px;
  z-index: 2000;
  background: rgba(15, 23, 42, 0.95);
  backdrop-filter: blur(20px);
  border: 2px solid rgba(59, 130, 246, 0.5);
  border-radius: 16px;
  padding: 20px;
  min-width: 280px;
  max-width: 320px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
  animation: slideInRight 0.3s ease-out;
}

@keyframes slideInRight {
  from {
    opacity: 0;
    transform: translateX(20px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

.drawing-controls__header {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 14px;
  padding-bottom: 10px;
  border-bottom: 1px solid rgba(59, 130, 246, 0.3);
}

.drawing-controls__header i {
  font-size: 20px;
  color: #3b82f6;
}

.drawing-controls__header h3 {
  margin: 0;
  color: #fff;
  font-size: 18px;
  font-weight: 600;
}

.drawing-controls__info {
  margin-bottom: 16px;
}

.drawing-controls__info p {
  margin: 0 0 8px 0;
  color: #cbd5e1;
  font-size: 13px;
  display: flex;
  align-items: center;
  gap: 6px;
  line-height: 1.4;
}

.drawing-controls__info p i {
  color: #60a5fa;
  font-size: 14px;
  flex-shrink: 0;
}

.drawing-controls__info .point-count {
  font-size: 14px;
  font-weight: 500;
  color: #e2e8f0;
  margin-top: 8px;
}

.drawing-controls__info .point-count strong {
  color: #3b82f6;
  font-size: 16px;
}

.drawing-controls__info .point-count .warning {
  color: #fbbf24;
  font-size: 12px;
  font-weight: 400;
}

.drawing-controls__actions {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.drawing-controls__button {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 10px 16px;
  border: none;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  color: white;
}

.drawing-controls__button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  transform: none !important;
}

.drawing-controls__button:not(:disabled):hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

.drawing-controls__button--success {
  background: linear-gradient(135deg, #22c55e, #16a34a);
}

.drawing-controls__button--success:not(:disabled):hover {
  background: linear-gradient(135deg, #16a34a, #15803d);
}

.drawing-controls__button--danger {
  background: linear-gradient(135deg, #ef4444, #dc2626);
}

.drawing-controls__button--danger:not(:disabled):hover {
  background: linear-gradient(135deg, #dc2626, #b91c1c);
}

.drawing-controls__button--cancel {
  background: linear-gradient(135deg, #64748b, #475569);
}

.drawing-controls__button--cancel:not(:disabled):hover {
  background: linear-gradient(135deg, #475569, #334155);
}

.drawing-controls__button i {
  font-size: 14px;
}

/* Responsive: Move to bottom on smaller screens */
@media (max-width: 768px) {
  .drawing-controls {
    top: auto;
    bottom: 20px;
    right: 20px;
    left: 20px;
    max-width: none;
    animation: slideInUp 0.3s ease-out;
  }

  @keyframes slideInUp {
    from {
      opacity: 0;
      transform: translateY(20px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }
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

.view-slide-fade-enter-active,
.view-slide-fade-leave-active {
  transition: opacity 0.25s ease, transform 0.25s ease;
}

.view-slide-fade-enter-from,
.view-slide-fade-leave-to {
  opacity: 0;
  transform: translateY(10px);
}
</style>