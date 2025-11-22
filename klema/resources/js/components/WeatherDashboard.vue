<template>
  <div v-if="isAuthenticated" class="weather-dashboard">
    <SearchBar 
      v-if="activeView === 'map' && !selectedDayDetail && !isDrawingBoundary"
      v-model="searchLocation"
      v-model:mapOnly="isMapOnly"
      @search="searchWeather"
      :is-loading="isLoadingWeather"
    />
    
    <ClickInstruction v-if="activeView === 'map' && !selectedDayDetail && !isDrawingBoundary && !isMapOnly" />
    
    <WeatherLayerControls 
      v-if="activeView === 'map' && !selectedDayDetail && !isDrawingBoundary && !isMapOnly"
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
      v-if="activeView === 'map' && !selectedDayDetail && !isDrawingBoundary && !isMapOnly"
      :forecast="forecastTimeline"
      :get-day-label="getDayLabel"
      :get-weather-icon="getWeatherIcon"
      @day-selected="handleTimelineSelection"
    />
    
    <TimelineLegend v-if="activeView === 'map' && !selectedDayDetail && !isDrawingBoundary && !isMapOnly" />

    <transition name="overlay-fade" v-if="!isMobileLayout">
      <div
        v-if="activeView !== 'map' && !selectedDayDetail && !isDrawingBoundary"
        class="overlay-wrapper"
      >
        <div class="overlay-panel">
          <div class="overlay-content">
            <Transition name="view-slide-fade" mode="out-in">
              <component
                v-if="overlayViewConfig.component"
                :is="overlayViewConfig.component"
                v-bind="overlayViewConfig.props"
                v-on="overlayViewConfig.listeners"
                :key="overlayViewConfig.key"
              />
            </Transition>
          </div>
        </div>
      </div>
    </transition>

    <div
      v-else-if="activeView !== 'map' && !selectedDayDetail && !isDrawingBoundary"
      class="mobile-panel"
    >
      <div class="mobile-panel__header">
        <button class="mobile-panel__back" @click="setActiveView('map')">
          <i class="fas fa-arrow-left"></i>
          <span>Back to Map</span>
        </button>
        <h2 class="mobile-panel__title">{{ getMobilePanelTitle() }}</h2>
      </div>
      <div class="mobile-panel__content">
        <Transition name="view-slide-fade" mode="out-in">
          <component
            v-if="overlayViewConfig.component"
            :is="overlayViewConfig.component"
            v-bind="overlayViewConfig.props"
            v-on="overlayViewConfig.listeners"
            :key="overlayViewConfig.key"
          />
        </Transition>
      </div>
    </div>

    <GlobalAlertNotification />

    <DayDetailView
      v-if="selectedDayDetail"
      :day="selectedDay"
      :detail="selectedDayDetail"
      :hourly-data="selectedDayHourly"
      :location-label="currentLocationLabel"
      @close="closeDayDetail"
    />

    <CalendarActivityCreateView
      v-if="calendarDetail"
      :detail="calendarDetail"
      @close="closeCalendarDetail"
      @saved="handleCalendarActivitySaved"
    />
  </div>

  <div v-else class="auth-screen">
    <AuthContainer @login-success="handleLoginSuccess" />
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, watch, computed, nextTick, defineAsyncComponent } from 'vue';
import axios from 'axios';
import SearchBar from './SearchBar.vue';
import ClickInstruction from './ClickInstruction.vue';
import WeatherLayerControls from './WeatherLayerControls.vue';
import LoadingIndicator from './LoadingIndicator.vue';
import WeatherTimeline from './WeatherTimeline.vue';
import TimelineLegend from './TimelineLegend.vue';
import GlobalAlertNotification from './GlobalAlertNotification.vue';
import AuthContainer from './Auth/AuthContainer.vue';
import { useWeatherAPI } from '../composables/useWeatherAPI';
import { useWeatherUtils } from '../composables/useWeatherUtils';
import { useGlobalAlerts } from '../composables/useGlobalAlerts';
import { useFarmMap } from '../composables/useFarmMap';
import { ensureApiToken, getApiToken, revokeApiToken } from '../services/auth';
import { useAlerts } from '../composables/useAlerts';
import { 
  useNotificationSettings,
  DEFAULT_NOTIFICATION_SETTINGS,
  SUPPORTED_NOTIFICATION_SETTING_KEYS
} from '../composables/useNotificationSettings';

const WeatherMap = defineAsyncComponent(() => import('./WeatherMap.vue'));
const DashboardView = defineAsyncComponent(() => import('./Views/DashboardView.vue'));
const CalendarView = defineAsyncComponent(() => import('./Views/CalendarView.vue'));
const CalendarActivityCreateView = defineAsyncComponent(() => import('./Views/CalendarActivityCreateView.vue'));
const AlertsView = defineAsyncComponent(() => import('./Views/AlertsView.vue'));
const SettingsView = defineAsyncComponent(() => import('./Views/SettingsView.vue'));
const ExportView = defineAsyncComponent(() => import('./Views/ExportView.vue'));
const DayDetailView = defineAsyncComponent(() => import('./Views/DayDetailView.vue'));
const UsersView = defineAsyncComponent(() => import('./Views/UsersView.vue'));

const MOBILE_BREAKPOINT = 900;
const weatherMapRef = ref(null);
const hasStoredToken = () => {
  try {
    return !!getApiToken();
  } catch (error) {
    return false;
  }
};
const isAuthenticated = ref(hasStoredToken());
const isMobileLayout = ref(false);
const updateViewportMode = () => {
  if (typeof window === 'undefined') {
    isMobileLayout.value = false;
    return;
  }
  isMobileLayout.value = window.innerWidth <= MOBILE_BREAKPOINT;
};
updateViewportMode();
const toggleDrawingModeClass = (isActive) => {
  if (typeof document === 'undefined') {
    return;
  }
  document.body.classList.toggle('drawing-boundary-mode', !!isActive);
};

const toggleMapOnlyClass = (isActive) => {
  if (typeof document === 'undefined') {
    return;
  }
  document.body.classList.toggle('map-only-mode', !!isActive);
};

const applyAuthBodyClass = (authState) => {
  if (typeof document === 'undefined') {
    return;
  }
  if (authState) {
    document.body.classList.remove('auth-mode');
  } else {
    document.body.classList.add('auth-mode');
  }
};
applyAuthBodyClass(isAuthenticated.value);
watch(isAuthenticated, (next) => {
  applyAuthBodyClass(next);
});
const APP_SETTINGS_STORAGE_KEY = 'appSettings';
const DEFAULT_LOCATION_STRING = 'Butuan, Caraga, PH'; // Default fallback string
const getDefaultLocation = () => {
  if (typeof window !== 'undefined' && 'geolocation' in navigator) {
    return new Promise((resolve) => {
      navigator.geolocation.getCurrentPosition(
        pos => resolve(`${pos.coords.latitude},${pos.coords.longitude}`),
        () => resolve(DEFAULT_LOCATION_STRING),
        { enableHighAccuracy: true, maximumAge: 30000, timeout: 15000 }
      );
    });
  }
  return Promise.resolve(DEFAULT_LOCATION_STRING);
};
const INITIAL_HISTORY_DAYS = 3; // 3 days before today
const INITIAL_FORECAST_DAYS = 7; // 7 days after today (API max is 5 days, so we'll get 4-5 future days)
const MAX_HISTORY_WINDOW = 90; // Increased from 30 to 90 days
const MAX_FORECAST_WINDOW = 16;
const searchLocation = ref(DEFAULT_LOCATION_STRING);
const forecast = ref([]);
const fullForecastTimeline = ref([]);
const currentWeather = ref(null);
const mapLoading = ref(true);
const isLoadingWeather = ref(false);
const activeView = ref('map');
const isMapOnly = ref(false);
const boundarySession = ref(null);
const pointSession = ref(null);
const isDrawingBoundary = computed(() => !!boundarySession.value);
const boundaryPointCount = ref(0);
let suppressNextLocationUpdate = false;
const latestHistoryData = ref([]);
const latestForecastData = ref([]);
const farmPrefillComplete = ref(false);
let initialLocationInitPromise = null;
const selectedDay = ref(null);
const selectedDayDetail = ref(null);
const selectedDayHourly = ref([]);
const calendarDetail = ref(null);
const calendarRefreshToken = ref(0);
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
const dashboardActivities = ref([]);
const dashboardActivitiesLoading = ref(false);
const dashboardActivitiesError = ref('');
const activityActionBusy = ref({});
const activityActionToast = ref(null);
const lastActivityFetchTs = ref(0);
const ACTIVITY_LOOKBACK_DAYS = 30;
const ACTIVITY_LOOKAHEAD_DAYS = 60;
let activityToastTimeoutId = null;

const formatDateParam = (date) => {
  if (!(date instanceof Date) || Number.isNaN(date.getTime())) {
    return null;
  }

  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
};

const buildActivityWindow = () => {
  const today = new Date();
  const from = new Date(today);
  from.setDate(from.getDate() - ACTIVITY_LOOKBACK_DAYS);

  const to = new Date(today);
  to.setDate(to.getDate() + ACTIVITY_LOOKAHEAD_DAYS);

  return {
    from_date: formatDateParam(from),
    to_date: formatDateParam(to)
  };
};

const setActivityBusy = (activityId, action) => {
  activityActionBusy.value = {
    ...(activityActionBusy.value ?? {}),
    [activityId]: action || true
  };
};

const clearActivityBusy = (activityId) => {
  const next = { ...(activityActionBusy.value ?? {}) };
  delete next[activityId];
  activityActionBusy.value = next;
};

const showActivityToast = (type, message) => {
  if (activityToastTimeoutId) {
    clearTimeout(activityToastTimeoutId);
  }

  const toast = {
    id: Date.now(),
    type,
    message
  };
  activityActionToast.value = toast;

  activityToastTimeoutId = setTimeout(() => {
    if (activityActionToast.value?.id === toast.id) {
      activityActionToast.value = null;
    }
  }, 5000);
};

const fetchDashboardActivities = async (force = false) => {
  if (dashboardActivitiesLoading.value && !force) {
    return;
  }

  dashboardActivitiesLoading.value = true;
  dashboardActivitiesError.value = '';

  try {
    await ensureApiToken();
    const params = {
      limit: 100,
      sort: 'asc',
      ...buildActivityWindow()
    };
    const { data } = await axios.get('/api/activities', { params });
    dashboardActivities.value = Array.isArray(data?.activities) ? data.activities : [];
    lastActivityFetchTs.value = Date.now();
  } catch (error) {
    console.error('Failed to load dashboard activities', error);
    dashboardActivitiesError.value =
      error.response?.data?.message || 'Unable to load activities right now.';
  } finally {
    dashboardActivitiesLoading.value = false;
  }
};

const ensureDashboardActivitiesLoaded = (force = false) => {
  const now = Date.now();
  const staleThreshold = 1000 * 60 * 5; // 5 minutes

  if (
    force ||
    !dashboardActivities.value.length ||
    now - lastActivityFetchTs.value > staleThreshold
  ) {
    fetchDashboardActivities(true);
  }
};

const handleActivityStatusChange = async ({ activityId, status }) => {
  if (!activityId || !status) {
    return;
  }

  setActivityBusy(activityId, status);
  try {
    await ensureApiToken();
    await axios.patch(`/api/activities/${activityId}`, { status });
    showActivityToast('success', status === 'completed' ? 'Activity marked as finished.' : 'Activity updated.');
    await fetchDashboardActivities(true);
  } catch (error) {
    console.error('Failed to update activity status', error);
    showActivityToast(
      'error',
      error.response?.data?.message || 'Unable to update the activity status.'
    );
  } finally {
    clearActivityBusy(activityId);
  }
};

const handleActivityDelete = async (activityId) => {
  if (!activityId) {
    return;
  }

  setActivityBusy(activityId, 'delete');
  try {
    await ensureApiToken();
    await axios.delete(`/api/activities/${activityId}`);
    showActivityToast('success', 'Activity deleted.');
    await fetchDashboardActivities(true);
  } catch (error) {
    console.error('Failed to delete activity', error);
    showActivityToast(
      'error',
      error.response?.data?.message || 'Unable to delete the activity.'
    );
  } finally {
    clearActivityBusy(activityId);
  }
};

// Optimized: Only load dashboard data when view is actually active (lazy loading)
watch(
  activeView,
  (view) => {
    if (view === 'dashboard') {
      // Use requestIdleCallback or setTimeout to defer non-critical operations
      const loadDashboardData = () => {
        ensureDashboardActivitiesLoaded();
        // Fetch system stats when dashboard is shown - force refresh to get latest data
        fetchSystemStats(true); // Force refresh to get updated stats
        if (!systemStats.value) {
          fetchUserInfo();
        }
      };
      
      // Defer to avoid blocking view switch
      if (typeof requestIdleCallback !== 'undefined') {
        requestIdleCallback(loadDashboardData, { timeout: 100 });
      } else {
        setTimeout(loadDashboardData, 0);
      }
    }
  }
);

// Fetch user info and system stats (only called when needed)
const fetchUserInfo = async () => {
  try {
    await ensureApiToken(axios);
    const response = await axios.get('/api/auth/me');
    if (response.data?.user) {
      currentUser.value = response.data.user;
      
      // Fetch system stats separately and asynchronously
      fetchSystemStats();
    }
  } catch (error) {
    // If token is invalid (401), clear auth state and show login
    if (error.response?.status === 401) {
      revokeApiToken();
      currentUser.value = null;
      isAuthenticated.value = false;
      applyAuthBodyClass(false);
      console.warn('Token expired or invalid, please log in again');
    } else {
      console.warn('Could not fetch user info:', error);
    }
  }
};

// Separate system stats fetch for better performance
const fetchSystemStats = async (forceRefresh = false, temperatureDays = null) => {
  // Always fetch if forcing refresh or temperature filter is specified
  if (!forceRefresh && temperatureDays === null && systemStats.value) {
    // Skip if already loaded and no filter/refresh requested
    return;
  }
  
  try {
    await ensureApiToken(axios);
    // Add timestamp to prevent caching and include temperature period
    const params = {};
    if (forceRefresh || temperatureDays !== null) {
      params._t = Date.now(); // Force refresh when filter changes
    }
    if (temperatureDays !== null) {
      params.temperature_days = temperatureDays;
    }
    const statsResponse = await axios.get('/api/admin/stats', { params });
    if (statsResponse.data?.success && statsResponse.data?.stats) {
      systemStats.value = statsResponse.data.stats;
    }
  } catch (err) {
    console.warn('Could not fetch system stats:', err);
  }
};

// Handler for refreshing system stats with temperature filter
const handleRefreshSystemStats = (temperatureDays) => {
  fetchSystemStats(true, temperatureDays);
};

const handleLoginSuccess = async (user) => {
  currentUser.value = user;
  isAuthenticated.value = true;
  applyAuthBodyClass(true);
  await bootstrapApp();
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

const handleCalendarDayOpen = (detail) => {
  if (detail?.mode === 'view') {
    if (detail?.forecast) {
      selectedDay.value = {
        date: detail.date,
        label: getDayLabel({ date: detail.date }),
      };
      selectedDayDetail.value = detail.forecast;
      selectedDayHourly.value = detail.forecast?.hourly ?? [];
    }
    return;
  }

  if (detail && detail.date) {
    calendarDetail.value = detail;
  } else {
    const today = new Date();
    const fallbackDate = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
    calendarDetail.value = { date: fallbackDate };
  }
};

const closeCalendarDetail = () => {
  calendarDetail.value = null;
};

const handleCalendarActivitySaved = () => {
  calendarRefreshToken.value += 1;
  ensureDashboardActivitiesLoaded(true);
  closeCalendarDetail();
};

const overlayViewConfig = computed(() => {
  switch (activeView.value) {
    case 'dashboard':
      return {
        key: 'dashboard',
        component: DashboardView,
        props: {
          currentWeather: currentWeather.value,
          forecast: fullForecastTimeline.value,
          getDayLabel,
          getWeatherIcon,
          farms: rawFarms.value,
          soilTypes: soilTypes.value,
          farmsLoading: farmMapLoading.value,
          isDrawing: !!boundarySession.value,
          isPlacingPoint: !!pointSession.value,
          systemStats: systemStats.value,
          activities: dashboardActivities.value,
          activitiesLoading: dashboardActivitiesLoading.value,
          activitiesError: dashboardActivitiesError.value,
          activityActionBusy: activityActionBusy.value,
          activityActionToast: activityActionToast.value,
          onRefreshFarms: refreshFarmLayers,
          onCreateFarm: createFarm,
          onSaveFarm: updateFarmDetails,
          onClearBoundary: removeFarmBoundary,
          onStartBoundary: startBoundaryEditing,
          onFinishBoundary: finishBoundaryEditing,
          onCancelBoundary: cancelBoundaryEditing,
          onStartPoint: startPointPlacement,
          onCancelPoint: cancelPointPlacement,
          onLocateFarm: locateFarm,
          onRefreshActivities: () => ensureDashboardActivitiesLoaded(true),
          onChangeActivityStatus: handleActivityStatusChange,
          onDeleteActivity: handleActivityDelete
        },
        listeners: {
          'refresh-system-stats': handleRefreshSystemStats
        }
      };
    case 'calendar':
      return {
        key: `calendar-${calendarRefreshToken.value}`,
        component: CalendarView,
        props: {
          getWeatherIcon,
          timeline: fullForecastTimeline.value,
          refreshToken: calendarRefreshToken.value
        },
        listeners: {
          'open-day': handleCalendarDayOpen
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
        },
        listeners: {}
      };
    case 'settings':
      return {
        key: 'settings',
        component: SettingsView,
        props: {
          farms: rawFarms.value
        },
        listeners: {}
      };
    case 'users':
      return {
        key: 'users',
        component: UsersView,
        props: {},
        listeners: {}
      };
    case 'exports':
      return {
        key: 'exports',
        component: ExportView,
        props: {
          farms: rawFarms.value
        },
        listeners: {}
      };
    default:
      return {
        key: '',
        component: null,
        props: {},
        listeners: {}
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

watch(
  isDrawingBoundary,
  (next) => {
    toggleDrawingModeClass(next);
  },
  { immediate: true }
);

watch(
  isMapOnly,
  (isActive) => {
    toggleMapOnlyClass(isActive);
  },
  { immediate: true }
);

watch(activeView, (view) => {
  if (view !== 'calendar') {
    calendarDetail.value = null;
  }
});

const setActiveView = (view) => {
  activeView.value = view;
  
  // Update sidebar active state to match
  const viewMap = {
    'dashboard': 0,
    'map': 1,
    'calendar': 2,
    'alerts': 3,
    'exports': 4,
    'settings': 5,
    'users': 6
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

const searchWeather = async (locationOverride = null) => {
  const rawInput = locationOverride ?? searchLocation.value ?? DEFAULT_LOCATION_STRING;
  const normalizedInput = typeof rawInput === 'string'
    ? rawInput
    : String(rawInput || DEFAULT_LOCATION_STRING);
  const trimmed = normalizedInput.trim();

  if (!trimmed) {
    return false;
  }

  if (searchLocation.value !== trimmed) {
    searchLocation.value = trimmed;
  } else {
    // Ensure we store the trimmed variant for future lookups
    searchLocation.value = trimmed;
  }
  
  isLoadingWeather.value = true;
  try {
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
 
    if (!farmPrefillComplete.value) {
      farmPrefillComplete.value = true;
    }
    
    checkWeatherConditions(
      { current, history, forecast: forecastData },
      snapshotNotificationPreferences()
    );
    await fetchForecastWarnings({ forecast: forecastData });
    return true;
  } catch (error) {
    console.error('Error searching weather:', error);
    alert('Failed to fetch weather data. Please try again.');
    return false;
  } finally {
    isLoadingWeather.value = false;
  }
};

const handleMapReady = async () => {
  mapLoading.value = false;
  await ensureApiToken(window.axios);
  
  // If we haven't initialized location yet (farms might not be loaded), try now
  if (!farmPrefillComplete.value) {
    const appSettings = loadAppSettings();
    const prefilled = await initializeDefaultLocation({ settingsOverride: appSettings });
    if (!prefilled && !hasPendingFarmPreference(appSettings)) {
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
    const stored = window.localStorage?.getItem(APP_SETTINGS_STORAGE_KEY);
    if (stored) {
      return JSON.parse(stored);
    }
  } catch (error) {
    console.error('Failed to load app settings:', error);
  }
  
  return null;
};

function hasPendingFarmPreference(settingsOverride = null) {
  const effectiveSettings = settingsOverride ?? loadAppSettings();
  return Boolean(
    effectiveSettings &&
    effectiveSettings.locationType === 'farm' &&
    effectiveSettings.selectedFarmId &&
    !(rawFarms.value?.length > 0)
  );
}

const initializeDefaultLocation = async ({ force = false, settingsOverride = null } = {}) => {
  if (farmPrefillComplete.value && !force) {
    return true;
  }

  if (!force && initialLocationInitPromise) {
    return initialLocationInitPromise;
  }

  if (force) {
    initialLocationInitPromise = null;
  }

  const runInitialization = async () => {
    const appSettings = settingsOverride ?? loadAppSettings();
    if (hasPendingFarmPreference(appSettings)) {
      return false;
    }

    const tryCoordinates = async ({ lat, lon, name, zoom = 12 }) => {
      const parsedLat = Number.parseFloat(lat);
      const parsedLon = Number.parseFloat(lon);
      if (!Number.isFinite(parsedLat) || !Number.isFinite(parsedLon)) {
        return false;
      }

      const success = await updateWeatherForLocation(parsedLat, parsedLon, name);
      if (success) {
        weatherMapRef.value?.moveToLocation(parsedLat, parsedLon, zoom);
        farmPrefillComplete.value = true;
      }
      return success;
    };

    if (appSettings) {
      if (appSettings.locationType === 'farm' && appSettings.selectedFarmId && rawFarms.value?.length) {
        const selectedFarm = rawFarms.value.find(
          (farm) => String(farm?.farm_id) === String(appSettings.selectedFarmId)
        );
        if (selectedFarm) {
          const name = selectedFarm?.farm_name || DEFAULT_LOCATION_STRING;
          searchLocation.value = name;

          const coordsLoaded = await tryCoordinates({
            lat: selectedFarm?.latitude,
            lon: selectedFarm?.longitude,
            name
          });
          if (coordsLoaded) {
            return true;
          }
        }
      }

      if (appSettings.locationType === 'custom' && appSettings.defaultLocation) {
        const location = appSettings.defaultLocation.trim();
        if (location) {
          searchLocation.value = location;
          const coordMatch = location.match(/^(-?\d+\.?\d*),\s*(-?\d+\.?\d*)$/);
          if (coordMatch) {
            const coordsLoaded = await tryCoordinates({
              lat: coordMatch[1],
              lon: coordMatch[2],
              name: location
            });
            if (coordsLoaded) {
              return true;
            }
          } else {
            const success = await searchWeather(location);
            if (success) {
              farmPrefillComplete.value = true;
              return true;
            }
          }
        }
      }
    }

    if (rawFarms.value?.length) {
      const firstFarm = rawFarms.value[0];
      const name = firstFarm?.farm_name || DEFAULT_LOCATION_STRING;
      searchLocation.value = name;

      const coordsLoaded = await tryCoordinates({
        lat: firstFarm?.latitude,
        lon: firstFarm?.longitude,
        name,
        zoom: 10
      });
      if (coordsLoaded) {
        return true;
      }
    }

    searchLocation.value = DEFAULT_LOCATION_STRING;
    const fallbackSuccess = await searchWeather(DEFAULT_LOCATION_STRING);
    if (fallbackSuccess) {
      farmPrefillComplete.value = true;
      return true;
    }

    return false;
  };

  initialLocationInitPromise = runInitialization();

  try {
    return await initialLocationInitPromise;
  } finally {
    initialLocationInitPromise = null;
  }
};

const handleSettingsRefresh = (settingsPayload = null) => {
  if (!isAuthenticated.value) {
    return;
  }
  farmPrefillComplete.value = false;
  initializeDefaultLocation({ force: true, settingsOverride: settingsPayload ?? null });
};

const handleAppSettingsUpdated = (event) => {
  handleSettingsRefresh(event?.detail ?? null);
};

const handleStorageSettingsChange = (event) => {
  if (event.key === APP_SETTINGS_STORAGE_KEY) {
    handleSettingsRefresh();
  }
};

const registerGlobalHandlers = () => {
  if (typeof window === 'undefined') {
    return;
  }

  window.vueApp = window.vueApp || {};
  window.vueApp.setActiveView = setActiveView;
  window.vueApp.searchWeather = searchWeather;
  window.vueApp.searchLocation = searchLocation;
};

registerGlobalHandlers();

const bootstrapInProgress = ref(false);
const bootstrapApp = async () => {
  if (bootstrapInProgress.value) {
    return;
  }
  bootstrapInProgress.value = true;
  try {
    await ensureApiToken(window.axios);
    
    // Optimized: Only fetch user info if needed (not blocking)
    // System stats will be fetched lazily when dashboard is shown
    fetchUserInfo().catch(err => {
      console.warn('Non-critical: Could not fetch user info:', err);
    });

    // Load farms first so we can use saved farm selection
    await refreshFarmLayers({ reloadData: true });

    // Initialize alerts panel in parallel with weather (doesn't depend on weather)
    const alertsPromise = initializeAlertsPanel().catch(err => {
      console.warn('Non-critical: Could not initialize alerts panel:', err);
    });

    // Now initialize default location (which can use saved settings)
    const weatherPromise = (async () => {
      const prefilled = await initializeDefaultLocation();
      if (!prefilled) {
        await searchWeather();
      }
    })().catch(err => {
      console.error('Failed to initialize weather:', err);
    });

    registerGlobalHandlers();

    // Wait for both to complete (in parallel)
    await Promise.all([alertsPromise, weatherPromise]);
  } finally {
    bootstrapInProgress.value = false;
  }
};

const handleAuthRequiredEvent = () => {
  revokeApiToken();
  currentUser.value = null;
  isAuthenticated.value = false;
  applyAuthBodyClass(false);
};

if (typeof window !== 'undefined') {
  window.addEventListener('auth:required', handleAuthRequiredEvent);
  window.addEventListener('appSettingsUpdated', handleAppSettingsUpdated);
  window.addEventListener('storage', handleStorageSettingsChange);
}

onBeforeUnmount(() => {
  if (typeof window !== 'undefined') {
    window.removeEventListener('auth:required', handleAuthRequiredEvent);
    window.removeEventListener('resize', updateViewportMode);
    window.removeEventListener('appSettingsUpdated', handleAppSettingsUpdated);
    window.removeEventListener('storage', handleStorageSettingsChange);
  }
  applyAuthBodyClass(true);
  toggleDrawingModeClass(false);
  toggleMapOnlyClass(false);
});

const getMobilePanelTitle = () => {
  switch (activeView.value) {
    case 'dashboard':
      return 'Dashboard';
    case 'calendar':
      return 'Calendar';
    case 'alerts':
      return 'Alerts';
    case 'exports':
      return 'Exports';
    case 'settings':
      return 'Settings';
    case 'users':
      return 'Users';
    default:
      return 'Details';
  }
};

onMounted(async () => {
  if (typeof window !== 'undefined') {
    window.addEventListener('resize', updateViewportMode);
    updateViewportMode();
  }

  if (!isAuthenticated.value) {
    return;
  }

  // Validate token before bootstrapping app
  // If token is invalid, fetchUserInfo will clear auth state
  try {
    await ensureApiToken(window.axios);
    const response = await window.axios.get('/api/auth/me');
    if (response.data?.user) {
      currentUser.value = response.data.user;
      await bootstrapApp();
    } else {
      // No user data, clear auth
      revokeApiToken();
      isAuthenticated.value = false;
      applyAuthBodyClass(false);
    }
  } catch (error) {
    // Token is invalid (401) or other error - clear auth and show login
    // Note: The axios interceptor will also handle 401, but we handle it here
    // to prevent unnecessary API calls during bootstrap
    if (error.response?.status === 401 || !error.response) {
      revokeApiToken();
      currentUser.value = null;
      isAuthenticated.value = false;
      applyAuthBodyClass(false);
      console.warn('Token expired or invalid, please log in again');
    } else {
      // Other error - still try to bootstrap but log the error
      console.error('Error validating token:', error);
      await bootstrapApp();
    }
  }
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
    return false;
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
    return true;
  } catch (error) {
    console.error('Error updating weather for farm location:', error);
    return false;
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

.auth-screen {
  min-height: 100vh;
  background: #0f172a;
}

.mobile-panel {
  position: fixed;
  inset: 0;
  background: linear-gradient(135deg, #0f172a, #1e293b);
  z-index: 4000;
  display: flex;
  flex-direction: column;
}

.mobile-panel__header {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 18px 20px;
  background: rgba(15, 23, 42, 0.95);
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.45);
}

.mobile-panel__back {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  border: none;
  background: rgba(59, 130, 246, 0.15);
  color: #93c5fd;
  padding: 10px 14px;
  border-radius: 999px;
  font-weight: 600;
  cursor: pointer;
}

.mobile-panel__back i {
  font-size: 14px;
}

.mobile-panel__title {
  margin: 0;
  color: #e2e8f0;
  font-size: 16px;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.mobile-panel__content {
  flex: 1;
  overflow-y: auto;
  padding: 20px;
}

/* Responsive Design - Mobile First Approach */

/* Extra Small Devices (phones, up to 480px) */
@media (max-width: 480px) {
  .drawing-controls {
    top: auto;
    bottom: 10px;
    right: 10px;
    left: 10px;
    max-width: none;
    min-width: auto;
    padding: 12px;
    border-radius: 12px;
    animation: slideInUp 0.3s ease-out;
  }

  .drawing-controls__header h3 {
    font-size: 16px;
  }

  .drawing-controls__info p {
    font-size: 12px;
  }

  .drawing-controls__button {
    padding: 8px 12px;
    font-size: 12px;
  }

  .overlay-wrapper {
    padding: 10px;
  }

  .overlay-panel {
    border-radius: 16px;
    height: calc(100vh - 20px);
  }

  .overlay-content :deep(.dashboard-view),
  .overlay-content :deep(.settings-view),
  .overlay-content :deep(.alerts-view),
  .overlay-content :deep(.exports-view) {
    padding: 16px;
  }

  .overlay-content :deep(.calendar-view) {
    padding: 16px;
    width: 100%;
    max-width: none;
    margin: 0;
  }
}

/* Small Devices (landscape phones, 481px to 640px) */
@media (min-width: 481px) and (max-width: 640px) {
  .drawing-controls {
    top: auto;
    bottom: 15px;
    right: 15px;
    left: 15px;
    max-width: none;
    min-width: auto;
    padding: 16px;
  }

  .overlay-wrapper {
    padding: 15px;
  }

  .overlay-content :deep(.dashboard-view),
  .overlay-content :deep(.settings-view),
  .overlay-content :deep(.alerts-view),
  .overlay-content :deep(.exports-view) {
    padding: 20px;
  }

  .overlay-content :deep(.calendar-view) {
    padding: 20px;
    width: 100%;
    max-width: none;
    margin: 0;
  }
}

/* Medium Devices (tablets, 641px to 768px) */
@media (min-width: 641px) and (max-width: 768px) {
  .drawing-controls {
    top: auto;
    bottom: 20px;
    right: 20px;
    left: 20px;
    max-width: none;
    padding: 18px;
  }

  .overlay-wrapper {
    padding: 20px 20px 20px 100px;
  }
}

/* Standard Mobile (up to 768px) */
@media (max-width: 768px) {
  .drawing-controls {
    top: auto;
    bottom: 20px;
    right: 20px;
    left: 20px;
    max-width: none;
    animation: slideInUp 0.3s ease-out;
  }

  .overlay-wrapper {
    padding: 20px 20px 20px 80px;
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

/* Large Devices (desktops, 1024px and up) */
@media (min-width: 1024px) {
  .overlay-wrapper {
    padding: clamp(30px, 3vw, 40px) clamp(30px, 3vw, 40px) clamp(30px, 3vw, 40px) clamp(100px, 10vw, 140px);
  }
}

/* Extra Large Devices (large desktops, 1440px and up) */
@media (min-width: 1440px) {
  .drawing-controls {
    min-width: 300px;
    max-width: 360px;
  }

  .overlay-wrapper {
    padding: clamp(40px, 3.5vw, 50px) clamp(40px, 3.5vw, 50px) clamp(40px, 3.5vw, 50px) clamp(120px, 11vw, 160px);
  }
}

/* Zoom Support - Ensure elements scale properly */
@media (min-resolution: 192dpi) {
  .drawing-controls {
    border-width: 1.5px;
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
  padding: clamp(20px, 3vw, 40px) clamp(20px, 3vw, 40px) clamp(20px, 3vw, 40px) clamp(80px, 10vw, 140px);
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
  align-items: stretch;
}

.overlay-content :deep(.dashboard-view),
.overlay-content :deep(.settings-view),
.overlay-content :deep(.alerts-view),
.overlay-content :deep(.exports-view) {
  flex: 1;
  width: 100%;
  max-width: none;
  margin: 0;
  overflow-y: auto;
  padding: 28px 36px 48px;
}

.overlay-content :deep(.calendar-view) {
  flex: 1;
  width: 100%;
  max-width: none;
  margin: 0;
  overflow-y: auto;
  padding: 28px 36px 48px;
  box-sizing: border-box;
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