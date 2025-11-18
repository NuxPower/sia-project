<template>
    <div class="dashboard-view">
      <!-- Top Weather Summary Card -->
<div 
        class="weather-summary-card"
        :class="[weatherEffectClass, { 'has-vanta': isVantaActive }]"
        ref="weatherCard"
      >
      <div class="location-info">
        <i class="fas fa-map-marker-alt"></i>
        <h2>{{ currentWeather?.name || 'Loading...' }}</h2>
      </div>
      
      <div class="current-weather" v-if="currentWeather">
        <div class="temperature-display">
          <span class="temp-value">{{ formatTemperature(currentWeather.main.temp) }}</span>
          <div class="weather-description">
            <i :class="getWeatherIcon({ condition: currentWeather.weather?.[0]?.main, icon: currentWeather.weather?.[0]?.icon })"></i>
            <span>{{ currentWeather.weather[0].description }}</span>
          </div>
        </div>
        
        <div class="weather-details">
          <div class="detail-item">
            <i class="fas fa-tint"></i>
            <span>{{ currentWeather.main.humidity }}%</span>
            <small>Humidity</small>
          </div>
          <div class="detail-item">
            <i class="fas fa-wind"></i>
            <span>{{ formatWindSpeed(currentWeather.wind.speed) }}</span>
            <small>Wind Speed</small>
          </div>
          <div class="detail-item">
            <i class="fas fa-compress-arrows-alt"></i>
            <span>{{ currentWeather.main.pressure }} mb</span>
            <small>Pressure</small>
          </div>
          <div class="detail-item">
            <i class="fas fa-eye"></i>
            <span>{{ (currentWeather.visibility / 1000).toFixed(1) }} km</span>
            <small>Visibility</small>
          </div>
        </div>
      </div>
    </div>

    <div class="forecast-grid">
      <h3>7-Day Forecast</h3>
      <div class="forecast-cards">
        <div 
          v-for="(day, index) in futureForecast" 
          :key="index"
          class="forecast-card"
          :class="{ 'today': day.isToday, 'history': day.isHistory }"
        >
          <div class="forecast-day">{{ getDayLabel(day) }}</div>
          <div class="forecast-date">{{ formatDate(day.date) }}</div>
          <div class="forecast-icon">
            <i :class="getWeatherIcon(day)"></i>
          </div>
          <div class="forecast-temp">
            <span class="temp-max">{{ formatTemperature(day.temp_max, { decimals: 0 }).replace('°C', '°').replace('°F', '°') }}</span>
            <span class="temp-min">{{ formatTemperature(day.temp_min, { decimals: 0 }).replace('°C', '°').replace('°F', '°') }}</span>
          </div>
          <div class="forecast-condition">{{ day.condition }}</div>
        </div>
      </div>
    </div>

    <!-- System Statistics -->
    <div v-if="systemStats" class="system-stats-section">
      <h3>
        <i class="fas fa-chart-line"></i>
        System Statistics
      </h3>
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon">
            <i class="fas fa-tractor"></i>
          </div>
          <div class="stat-content">
            <div class="stat-value">{{ systemStats.total_farms }}</div>
            <div class="stat-label">Total Farms</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">
            <i class="fas fa-exclamation-triangle"></i>
          </div>
          <div class="stat-content">
            <div class="stat-value">{{ systemStats.active_alerts }}</div>
            <div class="stat-label">Active Alerts</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">
            <i class="fas fa-calendar-check"></i>
          </div>
          <div class="stat-content">
            <div class="stat-value">{{ systemStats.recent_activities }}</div>
            <div class="stat-label">Recent Activities</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">
            <i class="fas fa-cloud-sun"></i>
          </div>
          <div class="stat-content">
            <div class="stat-value">{{ systemStats.weather_data_points }}</div>
            <div class="stat-label">Weather Data Points</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">
            <i class="fas fa-thermometer-half"></i>
          </div>
          <div class="stat-content">
            <div class="stat-value">{{ formatTemperature(systemStats.avg_temperature || 0) }}</div>
            <div class="stat-label">Avg Temperature</div>
          </div>
        </div>
      </div>
    </div>

    <section class="activities-section">
      <div class="activities-header">
        <div>
          <h3>
            <i class="fas fa-calendar-alt"></i>
            Activity Schedule
          </h3>
          <p>Monitor planned work and take quick actions on critical items.</p>
        </div>
        <button
          type="button"
          class="secondary-button"
          @click="refreshActivities"
          :disabled="activitiesLoading"
        >
          <i :class="activitiesLoading ? 'fas fa-spinner fa-spin' : 'fas fa-sync-alt'"></i>
          <span>{{ activitiesLoading ? 'Refreshing...' : 'Refresh' }}</span>
        </button>
      </div>

      <div class="activity-controls">
        <label class="control-field">
          <span>Status</span>
          <select v-model="activityStatusFilter">
            <option value="active">Active</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
            <option value="all">All</option>
          </select>
        </label>
        <label class="control-field">
          <span>Window</span>
          <select v-model="activityWindowFilter">
            <option value="upcoming">Upcoming (next 30 days)</option>
            <option value="recent">Recent (last 30 days)</option>
            <option value="all">All loaded</option>
          </select>
        </label>
        <label class="control-field grow">
          <span>Search</span>
          <div class="search-input">
            <i class="fas fa-search"></i>
            <input
              v-model="activitySearch"
              type="text"
              placeholder="Search activity, field, or notes"
            />
          </div>
        </label>
      </div>

      <transition name="fade">
        <div v-if="activityToast" class="activity-toast" :class="activityToast.type">
          <i :class="activityToast.icon"></i>
          <span>{{ activityToast.message }}</span>
        </div>
      </transition>

      <div v-if="activitiesError" class="activity-error-banner">
        <i class="fas fa-exclamation-triangle"></i>
        <span>{{ activitiesError }}</span>
      </div>
      <div v-else-if="activitiesLoading && !filteredActivities.length" class="activity-loading-pane">
        <i class="fas fa-spinner fa-spin"></i>
        <span>Loading activities...</span>
      </div>
      <div v-else-if="!filteredActivities.length" class="activity-empty-state">
        <i class="fas fa-clipboard-check"></i>
        <p>No activities match the selected filters.</p>
      </div>
      <div v-else class="activity-table-wrapper">
        <table class="activity-table">
          <thead>
            <tr>
              <th>Activity</th>
              <th>Field</th>
              <th>Start Date</th>
              <th>Status</th>
              <th>Weather</th>
              <th class="actions-col">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="activity in filteredActivities"
              :key="resolveActivityId(activity) ?? activity.start_date"
            >
              <td data-label="Activity">
                <div class="activity-name">
                  <strong>{{ activity.activity_type }}</strong>
                  <small v-if="activity.notes">{{ activity.notes }}</small>
                </div>
              </td>
              <td data-label="Field">
                <div class="activity-field">
                  {{ activity.field || '—' }}
                </div>
              </td>
              <td data-label="Start Date" class="start-date">{{ formatActivityDate(activity.start_date) }}</td>
              <td data-label="Status">
                <span :class="statusChipClass(activity.status)">
                  {{ (activity.status || 'pending').replace('_', ' ') }}
                </span>
              </td>
              <td data-label="Weather">
                <div v-if="activity.weather_warning" class="weather-warning-chip">
                  <i class="fas fa-exclamation-triangle"></i>
                  <span>{{ activity.weather_warning }}</span>
                </div>
                <span v-else class="no-warning">Clear</span>
              </td>
              <td class="activity-actions" data-label="Actions">
                <button type="button" class="table-button" @click="openActivityDetail(activity)">
                  <i class="fas fa-eye"></i>
                  <span>View</span>
                </button>
                <template v-if="isActivityActive(activity)">
                  <button
                    v-if="canFinishActivity(activity)"
                    type="button"
                    class="table-button success"
                    @click="changeActivityStatus(activity, 'completed')"
                    :disabled="isActivityBusy(activity)"
                  >
                    <i :class="isActivityBusy(activity) ? 'fas fa-spinner fa-spin' : 'fas fa-check'"></i>
                    <span>Finish</span>
                  </button>
                  <button
                    v-if="canCancelActivity(activity)"
                    type="button"
                    class="table-button warning"
                    @click="changeActivityStatus(activity, 'cancelled')"
                    :disabled="isActivityBusy(activity)"
                  >
                    <i :class="isActivityBusy(activity) ? 'fas fa-spinner fa-spin' : 'fas fa-ban'"></i>
                    <span>Cancel</span>
                  </button>
                  <button
                    type="button"
                    class="table-button danger"
                    @click="deleteActivity(activity)"
                    :disabled="isActivityBusy(activity)"
                  >
                    <i :class="isActivityBusy(activity, 'delete') ? 'fas fa-spinner fa-spin' : 'fas fa-trash'"></i>
                    <span>Delete</span>
                  </button>
                </template>
                <span v-else class="table-button disabled">
                  <i class="fas fa-ban"></i>
                  <span>No actions</span>
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <FarmManagementPanel
      class="dashboard-farm-panel"
      :farms="farms"
      :loading="farmsLoading"
      :is-drawing="isDrawing"
      :is-placing-point="isPlacingPoint"
      :soil-types="soilTypes"
      :on-locate-farm="onLocateFarm"
      variant="dashboard"
      @refresh="refresh"
      @create-farm="createFarm"
      @save-farm="updateFarm"
      @clear-boundary="clearBoundary"
      @start-boundary="startBoundary"
      @finish-boundary="finishBoundary"
      @cancel-boundary="cancelBoundary"
      @start-point="startPoint"
      @cancel-point="cancelPoint"
    />

    <transition name="modal-fade">
      <div
        v-if="showActivityDetail && selectedActivity"
        class="modal-backdrop"
        @click.self="closeActivityDetail"
      >
        <div class="modal-content activity-detail-modal">
          <div class="modal-header">
            <div>
              <p class="modal-eyebrow">Activity detail</p>
              <h3>{{ selectedActivity.activity_type }}</h3>
            </div>
            <button
              type="button"
              class="modal-close"
              aria-label="Close activity details"
              @click="closeActivityDetail"
            >
              <i class="fas fa-times"></i>
            </button>
          </div>

          <div class="activity-detail-body">
            <div class="detail-grid">
              <div class="detail-card">
                <span class="detail-label">Field</span>
                <strong>{{ selectedActivity.field || '—' }}</strong>
              </div>
              <div class="detail-card">
                <span class="detail-label">Start date</span>
                <strong>{{ formatActivityDate(selectedActivity.start_date) }}</strong>
              </div>
              <div class="detail-card">
                <span class="detail-label">Status</span>
                <span :class="statusChipClass(selectedActivity.status)">
                  {{ (selectedActivity.status || 'pending').replace('_', ' ') }}
                </span>
              </div>
              <div class="detail-card" v-if="selectedActivity.weather_warning">
                <span class="detail-label">Weather risk</span>
                <span class="detail-value warning">
                  <i class="fas fa-exclamation-triangle"></i>
                  {{ selectedActivity.weather_warning }}
                </span>
              </div>
            </div>

            <div class="detail-notes">
              <span class="detail-label">Notes</span>
              <p>{{ selectedActivity.notes || 'No additional notes.' }}</p>
            </div>
          </div>

          <div class="modal-footer">
            <button
              v-if="canFinishActivity(selectedActivity)"
              type="button"
              class="primary-button"
              @click="changeActivityStatus(selectedActivity, 'completed')"
            >
              <i class="fas fa-check"></i>
              <span>Mark as Finished</span>
            </button>
            <button
              v-if="canCancelActivity(selectedActivity)"
              type="button"
              class="danger-button ghost"
              @click="changeActivityStatus(selectedActivity, 'cancelled')"
            >
              <i class="fas fa-ban"></i>
              <span>Cancel</span>
            </button>
            <button type="button" class="secondary-button" @click="closeActivityDetail">
              Close
            </button>
          </div>
        </div>
      </div>
    </transition>

    <!-- Tips for Farming -->
    <div class="tips-section">
      <h3>
        <i class="fas fa-seedling"></i>
        Tips for Farming
      </h3>
      <div class="tips-list">
        <div class="tip-item">
          <i class="fas fa-check-circle"></i>
          <span>Keep an eye on weather forecasts and plan fieldwork during breaks in the rain.</span>
        </div>
        <div class="tip-item">
          <i class="fas fa-check-circle"></i>
          <span>Have an emergency plan for flash floods (moving livestock, securing equipment).</span>
        </div>
        <div class="tip-item">
          <i class="fas fa-check-circle"></i>
          <span>Plant trees or hedgerows around fields as windbreaks and to absorb excess water.</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, onMounted, onBeforeUnmount, watch, nextTick } from 'vue';
import * as THREE from 'three';
import CLOUDS from 'vanta/dist/vanta.clouds.min.js';
import FOG from 'vanta/dist/vanta.fog.min.js';
import WAVES from 'vanta/dist/vanta.waves.min.js';
import FarmManagementPanel from '../FarmManagementPanel.vue';
import { useDisplaySettings } from '../../composables/useDisplaySettings';

const props = defineProps({
  currentWeather: Object,
  forecast: Array,
  getDayLabel: Function,
  getWeatherIcon: Function,
  farms: {
    type: Array,
    default: () => []
  },
  soilTypes: {
    type: Array,
    default: () => []
  },
  farmsLoading: {
    type: Boolean,
    default: false
  },
  isDrawing: {
    type: Boolean,
    default: false
  },
  isPlacingPoint: {
    type: Boolean,
    default: false
  },
  systemStats: {
    type: Object,
    default: null
  },
  activities: {
    type: Array,
    default: () => []
  },
  activitiesLoading: {
    type: Boolean,
    default: false
  },
  activitiesError: {
    type: String,
    default: ''
  },
  activityActionBusy: {
    type: Object,
    default: () => ({})
  },
  activityActionToast: {
    type: Object,
    default: null
  },
  onLocateFarm: {
    type: Function,
    default: null
  }
});

const { formatTemperature, formatWindSpeed } = useDisplaySettings();

const emit = defineEmits([
  'refresh-farms',
  'create-farm',
  'save-farm',
  'clear-boundary',
  'start-boundary',
  'finish-boundary',
  'cancel-boundary',
  'start-point',
  'cancel-point',
  'refresh-activities',
  'change-activity-status',
  'delete-activity'
]);

const farms = computed(() => props.farms ?? []);
const activities = computed(() => props.activities ?? []);

// Filter forecast to show only 7 days starting from tomorrow
const futureForecast = computed(() => {
  if (!Array.isArray(props.forecast) || props.forecast.length === 0) {
    return [];
  }

  const today = new Date();
  today.setHours(0, 0, 0, 0);
  
  // Filter out today and history days, only keep future days
  const futureDays = props.forecast.filter((day) => {
    if (!day?.date) return false;
    if (day.isHistory) return false;
    if (day.isToday) return false;
    
    // Parse the date string (format: "YYYY-MM-DD")
    const parts = day.date.split('-');
    if (parts.length !== 3) return false;
    
    const dayDate = new Date(
      parseInt(parts[0], 10),
      parseInt(parts[1], 10) - 1,
      parseInt(parts[2], 10)
    );
    dayDate.setHours(0, 0, 0, 0);
    
    // Only include days after today
    return dayDate > today;
  });
  
  // Sort by date and take first 7 days
  return futureDays
    .sort((a, b) => {
      const aParts = a.date.split('-');
      const bParts = b.date.split('-');
      const aDate = new Date(parseInt(aParts[0], 10), parseInt(aParts[1], 10) - 1, parseInt(aParts[2], 10));
      const bDate = new Date(parseInt(bParts[0], 10), parseInt(bParts[1], 10) - 1, parseInt(bParts[2], 10));
      return aDate - bDate;
    })
    .slice(0, 7);
});

const activityStatusFilter = ref('active');
const activityWindowFilter = ref('upcoming');
const activitySearch = ref('');
const selectedActivity = ref(null);
const showActivityDetail = ref(false);

const statusFilterMap = {
  active: ['pending', 'in_progress'],
  completed: ['completed'],
  cancelled: ['cancelled'],
  all: []
};

const toastIconMap = {
  success: 'fas fa-check-circle',
  error: 'fas fa-exclamation-circle',
  info: 'fas fa-info-circle'
};

const activityToast = computed(() => {
  if (!props.activityActionToast) {
    return null;
  }

  return {
    ...props.activityActionToast,
    icon: toastIconMap[props.activityActionToast.type] ?? 'fas fa-info-circle'
  };
});

const resolveActivityId = (activity) => activity?.id ?? activity?.activity_id ?? activity?.uuid ?? null;

const toDateOnly = (value) => {
  if (!value) {
    return null;
  }

  const parsed = value instanceof Date ? value : new Date(value);
  if (Number.isNaN(parsed)) {
    return null;
  }

  return new Date(parsed.getFullYear(), parsed.getMonth(), parsed.getDate());
};

const formatActivityDate = (value) => {
  const date = toDateOnly(value);
  if (!date) {
    return '—';
  }

  return date.toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric'
  });
};

const sortedActivities = computed(() => {
  return [...activities.value].sort((a, b) => {
    const aDate = toDateOnly(a?.start_date)?.getTime() ?? 0;
    const bDate = toDateOnly(b?.start_date)?.getTime() ?? 0;
    return aDate - bDate;
  });
});

const matchesWindowFilter = (activity) => {
  const date = toDateOnly(activity?.start_date);
  if (!date) {
    return true;
  }

  const today = new Date();
  const lowerBound = new Date(today.getFullYear(), today.getMonth(), today.getDate());

  if (activityWindowFilter.value === 'upcoming') {
    const start = new Date(lowerBound);
    start.setDate(start.getDate() - 2);
    const end = new Date(lowerBound);
    end.setDate(end.getDate() + 30);
    return date >= start && date <= end;
  }

  if (activityWindowFilter.value === 'recent') {
    const start = new Date(lowerBound);
    start.setDate(start.getDate() - 30);
    return date >= start;
  }

  return true;
};

const filteredActivities = computed(() => {
  const search = activitySearch.value.trim().toLowerCase();
  const statusList = statusFilterMap[activityStatusFilter.value] ?? [];

  return sortedActivities.value.filter((activity) => {
    if (statusList.length && !statusList.includes((activity?.status ?? '').toLowerCase())) {
      return false;
    }

    if (!matchesWindowFilter(activity)) {
      return false;
    }

    if (search) {
      const haystack = [
        activity?.activity_type ?? '',
        activity?.field ?? '',
        activity?.notes ?? ''
      ]
        .join(' ')
        .toLowerCase();

      if (!haystack.includes(search)) {
        return false;
      }
    }

    return true;
  });
});

const isActivityBusy = (activity, action = null) => {
  const id = resolveActivityId(activity);
  if (!id) {
    return false;
  }

  const busyAction = props.activityActionBusy?.[id];
  if (!busyAction) {
    return false;
  }

  return action ? busyAction === action : true;
};

const refreshActivities = () => emit('refresh-activities');

const changeActivityStatus = (activity, status) => {
  const id = resolveActivityId(activity);
  if (!id || activity?.status === status) {
    return;
  }
  emit('change-activity-status', { activityId: id, status });
  if (showActivityDetail.value && resolveActivityId(selectedActivity.value) === id) {
    closeActivityDetail();
  }
};

const deleteActivity = (activity) => {
  const id = resolveActivityId(activity);
  if (!id) {
    return;
  }

  if (typeof window !== 'undefined' && !window.confirm('Delete this activity? This cannot be undone.')) {
    return;
  }

  emit('delete-activity', id);
};

const isActivityActive = (activity) => {
  const status = (activity?.status ?? '').toLowerCase();
  return status !== 'completed' && status !== 'cancelled';
};

const openActivityDetail = (activity) => {
  selectedActivity.value = activity;
  showActivityDetail.value = true;
};

const closeActivityDetail = () => {
  showActivityDetail.value = false;
  selectedActivity.value = null;
};

const canFinishActivity = (activity) => {
  const status = (activity?.status ?? '').toLowerCase();
  return status !== 'completed' && status !== 'cancelled';
};

const canCancelActivity = (activity) => {
  const status = (activity?.status ?? '').toLowerCase();
  return status !== 'cancelled' && status !== 'completed';
};

const statusChipClass = (status) => {
  const normalized = (status ?? '').toLowerCase();
  switch (normalized) {
    case 'completed':
      return 'status-chip success';
    case 'in_progress':
      return 'status-chip info';
    case 'cancelled':
      return 'status-chip danger';
    default:
      return 'status-chip neutral';
  }
};

const weatherCard = ref(null);
const isVantaActive = ref(false);
let vantaEffect = null;

const refresh = () => emit('refresh-farms');
const createFarm = (payload) => emit('create-farm', payload);
const updateFarm = (farmId, payload) => emit('save-farm', farmId, payload);
const clearBoundary = (farmId) => emit('clear-boundary', farmId);
const startBoundary = (farmId) => emit('start-boundary', farmId);
const finishBoundary = () => emit('finish-boundary');
const cancelBoundary = () => emit('cancel-boundary');
const startPoint = (farmId, payload) => emit('start-point', farmId, payload);
const cancelPoint = () => emit('cancel-point');

const destroyVanta = () => {
  if (vantaEffect) {
    vantaEffect.destroy();
    vantaEffect = null;
  }
  isVantaActive.value = false;
};

const selectVantaEffect = (condition = '') => {
  const normalized = condition.toLowerCase();

  if (normalized.includes('rain') || normalized.includes('thunder')) {
    return FOG;
  }

  if (normalized.includes('clear')) {
    return WAVES;
  }

  if (normalized.includes('cloud') || normalized.includes('mist') || normalized.includes('fog') || normalized.includes('haze')) {
    return CLOUDS;
  }

  return CLOUDS;
};

const initVanta = async () => {
  if (typeof window === 'undefined' || !weatherCard.value) {
    return;
  }

  destroyVanta();

  const condition = props.currentWeather?.weather?.[0]?.main ?? '';
  const effectType = selectVantaEffect(condition);

  if (!effectType) {
    return;
  }

  const getVantaViewportConfig = () => {
    if (typeof window === 'undefined') {
      return { minHeight: 200, scale: 1, scaleMobile: 1 };
    }

    if (window.innerWidth <= 480) {
      return { minHeight: 130, scale: 1, scaleMobile: 1 };
    }

    if (window.innerWidth <= 768) {
      return { minHeight: 160, scale: 1, scaleMobile: 1 };
    }

    return { minHeight: 200, scale: 1, scaleMobile: 1 };
  };

  try {
    // Ensure THREE.js is available before initializing
    if (!THREE) {
      console.warn('THREE.js not available for Vanta effect');
      return;
    }

    const { minHeight, scale, scaleMobile } = getVantaViewportConfig();
    
    // Check if element still exists before initializing
    if (!weatherCard.value) {
      return;
    }

    vantaEffect = effectType({
      el: weatherCard.value,
      THREE,
      mouseControls: false,
      touchControls: false,
      gyroControls: false,
      minHeight,
      minWidth: 200.0,
      scale,
      scaleMobile,
      color: 0x0077ff,
      backgroundAlpha: 0.0
    });

    isVantaActive.value = true;
  } catch (error) {
    console.warn('Failed to initialize Vanta effect:', error);
    destroyVanta();
  }
};

const scheduleVantaInit = () => {
  if (!weatherCard.value) {
    return;
  }

  // Optimized: Defer Vanta initialization to avoid blocking initial render
  // Use requestIdleCallback for better performance, fallback to setTimeout
  const deferredInit = () => {
    nextTick(() => {
      initVanta();
    });
  };

  if (typeof requestIdleCallback !== 'undefined') {
    // Use idle time to initialize Vanta (non-blocking)
    requestIdleCallback(deferredInit, { timeout: 300 });
  } else {
    // Fallback: Defer to next animation frame
    setTimeout(deferredInit, 100);
  }
};

let vantaInitTimeout = null;

onMounted(() => {
  // Don't block mount - schedule Vanta for later
  scheduleVantaInit();
});

onBeforeUnmount(() => {
  if (vantaInitTimeout) {
    clearTimeout(vantaInitTimeout);
    vantaInitTimeout = null;
  }
  destroyVanta();
});

watch(
  () => props.currentWeather?.weather?.[0]?.main,
  () => {
    // Debounce Vanta re-initialization to avoid excessive recreations
    if (vantaInitTimeout) {
      clearTimeout(vantaInitTimeout);
    }
    vantaInitTimeout = setTimeout(() => {
      scheduleVantaInit();
    }, 150);
  }
);

const formatDate = (date) => {
  if (!date) return '';
  const parts = date.split('-');
  const dateObj = new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2]));
  return dateObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
};

const weatherEffectClass = computed(() => {
  const condition = props.currentWeather?.weather?.[0]?.main?.toLowerCase();

  if (!condition) return '';

  if (['rain', 'drizzle', 'thunderstorm'].some((type) => condition.includes(type))) {
    return 'is-raining';
  }

  if (['snow', 'sleet'].some((type) => condition.includes(type))) {
    return 'is-snowing';
  }

  if (['clear'].some((type) => condition.includes(type))) {
    return 'is-clear';
  }

  if (['clouds', 'mist', 'fog', 'haze'].some((type) => condition.includes(type))) {
    return 'is-cloudy';
  }

  return '';
});
</script>

<style scoped>
.dashboard-view {
  width: 100%;
  margin: 0;
  padding: 0;
  overflow-y: auto;
  height: 100%;
  
  /* Hide scrollbar */
  scrollbar-width: none; /* Firefox */
  -ms-overflow-style: none; /* IE and Edge */
}

.dashboard-view::-webkit-scrollbar {
  display: none; /* Chrome, Safari, Opera */
}

.dashboard-farm-panel {
  margin-bottom: 24px;
}

.weather-summary-card {
  background: rgba(0, 0, 0, 0.6);
  border-radius: 20px;
  padding: 28px;
  margin-bottom: 22px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(10px);
  position: relative;
  overflow: hidden;
}

.weather-summary-card::before {
  content: '';
  position: absolute;
  inset: -40%;
  background: radial-gradient(circle at 20% 20%, rgba(59, 130, 246, 0.35), transparent 55%),
              radial-gradient(circle at 80% 30%, rgba(96, 165, 250, 0.25), transparent 60%),
              radial-gradient(circle at 50% 80%, rgba(56, 189, 248, 0.2), transparent 65%);
  filter: blur(40px);
  animation: pulseGlow 12s ease-in-out infinite;
  opacity: 0.9;
  z-index: 0;
}

.weather-summary-card::after {
  content: '';
  position: absolute;
  inset: 0;
  z-index: 0;
  pointer-events: none;
  opacity: 0;
  transition: opacity 0.6s ease, transform 1.2s ease;
  background-repeat: repeat;
  background-size: cover;
}

.weather-summary-card.is-raining::after {
  background-image: repeating-linear-gradient(
    160deg,
    rgba(59, 130, 246, 0.35) 0px,
    rgba(59, 130, 246, 0.35) 1px,
    transparent 1px,
    transparent 16px
  );
  animation: rainfall 0.9s linear infinite;
  opacity: 0.7;
}

.weather-summary-card.is-snowing::after {
  background-image:
    radial-gradient(rgba(255, 255, 255, 0.7) 20%, transparent 60%),
    radial-gradient(rgba(255, 255, 255, 0.6) 18%, transparent 60%);
  background-size: 8px 10px, 10px 12px;
  background-position: 0 -20px, 50px -40px;
  animation: snowfall 12s linear infinite;
  opacity: 0.8;
  filter: blur(0.3px);
}

.weather-summary-card.is-clear::after {
  background-image:
    radial-gradient(circle at 20% 20%, rgba(253, 224, 71, 0.45), transparent 60%),
    radial-gradient(circle at 70% 30%, rgba(59, 130, 246, 0.25), transparent 70%);
  animation: sunGlow 14s ease-in-out infinite;
  opacity: 0.55;
}

.weather-summary-card.is-cloudy::after {
  background-image:
    radial-gradient(circle at 15% 40%, rgba(148, 163, 184, 0.35), transparent 70%),
    radial-gradient(circle at 60% 60%, rgba(148, 163, 184, 0.28), transparent 65%);
  animation: cloudDrift 18s linear infinite;
  opacity: 0.6;
}

.weather-summary-card > *:not(.vanta-canvas) {
  position: relative;
  z-index: 1;
}

.weather-summary-card .vanta-canvas {
  position: absolute !important;
  inset: 0;
  z-index: 0;
  pointer-events: none;
}

.weather-summary-card.has-vanta::before,
.weather-summary-card.has-vanta::after {
  opacity: 0;
}

.location-info {
  display: flex;
  align-items: center;
  gap: 10px;
  color: white;
  margin-bottom: 20px;
}

.location-info i {
  color: #3b82f6;
  font-size: 24px;
  -webkit-text-stroke: 0.6px rgba(15, 23, 42, 0.65);
  text-shadow:
    0 0 6px rgba(15, 23, 42, 0.4),
    0 0 12px rgba(15, 23, 42, 0.35);
}

.location-info h2 {
  margin: 0;
  font-size: 28px;
}

.current-weather {
  display: grid;
  grid-template-columns: minmax(220px, 1fr) 1fr;
  align-items: center;
  gap: 24px;
}

.temperature-display {
  display: flex;
  flex-direction: column;
  gap: 10px;
  justify-content: center;
}

.temp-value {
  font-size: 72px;
  font-weight: bold;
  color: #3b82f6;
  display: block;
}

.weather-description {
  display: flex;
  align-items: center;
  gap: 10px;
  color: white;
  font-size: 20px;
  text-transform: capitalize;
  margin-top: 10px;
}

.weather-description i {
  font-size: 32px;
  color: #60a5fa;
  -webkit-text-stroke: 0.6px rgba(15, 23, 42, 0.6);
  text-shadow:
    0 0 8px rgba(15, 23, 42, 0.4),
    0 0 14px rgba(15, 23, 42, 0.32);
}

.weather-details {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 16px;
}

.detail-item {
  display: grid;
  grid-template-columns: auto 1fr;
  grid-template-rows: auto auto;
  align-items: center;
  column-gap: 12px;
  row-gap: 4px;
  padding: 14px 16px;
  background: rgba(255, 255, 255, 0.05);
  border-radius: 16px;
  border: 1px solid rgba(255, 255, 255, 0.12);
}

.detail-item i {
  font-size: 20px;
  color: #60a5fa;
  grid-row: span 2;
}

.detail-item span {
  font-size: 18px;
  font-weight: 700;
  color: #f3f4f6;
  line-height: 1.2;
}

.detail-item small {
  font-size: 11px;
  letter-spacing: 0.4px;
  text-transform: uppercase;
  color: #cbd5f5;
}

.forecast-grid {
  margin-bottom: 30px;
}

.forecast-grid h3 {
  color: white;
  font-size: 24px;
  margin-bottom: 20px;
}

.forecast-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
  gap: 15px;
  position: relative;
  padding-top: 6px;
  margin-top: -6px;
  overflow: visible;
}

.forecast-card {
  background: rgba(0, 0, 0, 0.6);
  border-radius: 16px;
  padding: 20px;
  text-align: center;
  border: 1px solid rgba(255, 255, 255, 0.1);
  transition: all 0.3s ease;
}

.forecast-card:hover {
  transform: translateY(-5px);
  border-color: rgba(59, 130, 246, 0.5);
  box-shadow: 0 8px 24px rgba(59, 130, 246, 0.2);
}

.forecast-card.today {
  background: rgba(59, 130, 246, 0.2);
  border: 2px solid rgba(59, 130, 246, 0.6);
}

.forecast-card.history {
  opacity: 0.6;
}

.forecast-day {
  color: white;
  font-weight: bold;
  font-size: 14px;
  margin-bottom: 5px;
}

.forecast-date {
  color: #9ca3af;
  font-size: 12px;
  margin-bottom: 15px;
}

.forecast-icon {
  font-size: 36px;
  color: #60a5fa;
  margin-bottom: 15px;
}

.forecast-temp {
  display: flex;
  justify-content: center;
  gap: 10px;
  margin-bottom: 10px;
}

.temp-max {
  color: white;
  font-weight: bold;
  font-size: 16px;
}

.temp-min {
  color: #9ca3af;
  font-size: 14px;
}

.forecast-condition {
  color: #9ca3af;
  font-size: 12px;
}

.tips-section {
  background: rgba(34, 197, 94, 0.1);
  border-radius: 16px;
  padding: 25px;
  border: 1px solid rgba(34, 197, 94, 0.3);
}

.tips-section h3 {
  color: white;
  font-size: 20px;
  margin-bottom: 20px;
  display: flex;
  align-items: center;
  gap: 10px;
}

.tips-section h3 i {
  color: #22c55e;
}

.system-stats-section {
  background: rgba(59, 130, 246, 0.1);
  border-radius: 16px;
  padding: 25px;
  border: 1px solid rgba(59, 130, 246, 0.3);
  margin-bottom: 30px;
}

.system-stats-section h3 {
  color: white;
  font-size: 20px;
  margin-bottom: 20px;
  display: flex;
  align-items: center;
  gap: 10px;
}

.system-stats-section h3 i {
  color: #3b82f6;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 15px;
}

.stat-card {
  background: rgba(255, 255, 255, 0.05);
  border-radius: 12px;
  padding: 20px;
  display: flex;
  align-items: center;
  gap: 15px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  transition: transform 0.3s ease, border-color 0.3s ease;
}

.stat-card:hover {
  transform: translateY(-3px);
  border-color: rgba(59, 130, 246, 0.5);
}

.stat-icon {
  font-size: 32px;
  color: #3b82f6;
  flex-shrink: 0;
}

.stat-content {
  flex: 1;
}

.stat-value {
  color: white;
  font-size: 24px;
  font-weight: bold;
  margin-bottom: 4px;
}
.stat-label {
  color: #9ca3af;
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.tips-list {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.tip-item {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  color: white;
  font-size: 14px;
  line-height: 1.6;
}

.tip-item i {
  color: #22c55e;
  margin-top: 3px;
  flex-shrink: 0;
}

.activities-section {
  margin: 30px 0;
  background: rgba(0, 0, 0, 0.55);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 20px;
  padding: 24px;
}

.activities-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 20px;
  margin-bottom: 20px;
}

.activities-header h3 {
  margin: 0;
  font-size: 20px;
  color: white;
}

.activities-header p {
  margin: 6px 0 0;
  color: #cbd5f5;
  font-size: 14px;
}

.activity-controls {
  display: flex;
  flex-wrap: wrap;
  gap: 16px;
  margin-bottom: 18px;
}

.control-field {
  display: flex;
  flex-direction: column;
  gap: 6px;
  min-width: 180px;
}

.control-field.grow {
  flex: 1;
}

.control-field span {
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: #9ca3af;
}

.control-field select,
.search-input input {
  background: rgba(15, 23, 42, 0.65);
  border: 1px solid rgba(148, 163, 184, 0.35);
  color: #e2e8f0;
  border-radius: 12px;
  padding: 10px 12px;
  font-size: 14px;
}

.search-input {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 12px;
  border-radius: 12px;
  border: 1px solid rgba(148, 163, 184, 0.35);
  background: rgba(15, 23, 42, 0.65);
}

.search-input i {
  color: #94a3b8;
}

.search-input input {
  border: none;
  background: transparent;
  padding: 0;
  width: 100%;
}

.activity-toast {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 14px;
  border-radius: 12px;
  margin-bottom: 12px;
  font-size: 14px;
}

.activity-toast.success {
  background: rgba(16, 185, 129, 0.15);
  border: 1px solid rgba(16, 185, 129, 0.35);
  color: #bbf7d0;
}

.activity-toast.error {
  background: rgba(239, 68, 68, 0.15);
  border: 1px solid rgba(239, 68, 68, 0.35);
  color: #fecaca;
}

.activity-error-banner,
.activity-loading-pane,
.activity-empty-state {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 16px;
  border-radius: 14px;
  border: 1px solid rgba(148, 163, 184, 0.25);
  margin-bottom: 12px;
  background: rgba(15, 23, 42, 0.5);
  color: #e2e8f0;
}

.activity-empty-state {
  flex-direction: column;
  text-align: center;
}

.activity-empty-state i {
  font-size: 32px;
  color: #94a3b8;
}

.activity-table-wrapper {
  overflow-x: auto;
}

.activity-table {
  width: 100%;
  border-collapse: collapse;
}
.activity-field {
  color: white;
}
.activity-table th,
.activity-table td {
  padding: 14px 12px;
  text-align: left;
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.activity-table td.start-date {
  color: #fed7aa;
  font-weight: 600;
  letter-spacing: 0.02em;
}

.activity-table th {
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: #94a3b8;
}

.activity-name strong {
  display: block;
  color: #f3f4f6;
}

.activity-name small {
  color: #cbd5f5;
  font-size: 12px;
}

.status-chip {
  display: inline-flex;
  align-items: center;
  padding: 4px 10px;
  border-radius: 999px;
  font-size: 12px;
  text-transform: capitalize;
  border: 1px solid transparent;
}

.status-chip.success {
  background: rgba(16, 185, 129, 0.15);
  border-color: rgba(16, 185, 129, 0.35);
  color: #6ee7b7;
}

.status-chip.info {
  background: rgba(59, 130, 246, 0.15);
  border-color: rgba(59, 130, 246, 0.35);
  color: #bfdbfe;
}

.status-chip.danger {
  background: rgba(239, 68, 68, 0.15);
  border-color: rgba(239, 68, 68, 0.35);
  color: #fecaca;
}

.status-chip.neutral {
  background: rgba(148, 163, 184, 0.2);
  border-color: rgba(148, 163, 184, 0.35);
  color: #e5e7eb;
}

.weather-warning-chip {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: rgba(250, 204, 21, 0.12);
  color: #fde68a;
  border: 1px solid rgba(250, 204, 21, 0.35);
  padding: 4px 10px;
  border-radius: 10px;
  font-size: 12px;
}

.no-warning {
  color: #9ca3af;
  font-size: 13px;
}

.activity-actions {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.table-button {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: flex-start;
  gap: 10px;
  padding: 10px 14px;
  border-radius: 12px;
  border: 1px solid rgba(255, 255, 255, 0.05);
  font-size: 13px;
  font-weight: 600;
  letter-spacing: 0.01em;
  cursor: pointer;
  background: rgba(15, 23, 42, 0.65);
  color: #e2e8f0;
  transition: background 0.15s ease, border-color 0.15s ease, transform 0.15s ease;
}

.table-button i {
  font-size: 14px;
  opacity: 0.8;
}

.table-button:hover:not(:disabled) {
  background: rgba(59, 130, 246, 0.12);
  border-color: rgba(59, 130, 246, 0.3);
  transform: translateX(2px);
}

.table-button.success {
  background: rgba(16, 185, 129, 0.14);
  border-color: rgba(16, 185, 129, 0.4);
  color: #6ee7b7;
}

.table-button.warning {
  background: rgba(250, 204, 21, 0.14);
  border-color: rgba(250, 204, 21, 0.4);
  color: #fde68a;
}

.table-button.danger {
  background: rgba(239, 68, 68, 0.14);
  border-color: rgba(239, 68, 68, 0.4);
  color: #fecaca;
}

.table-button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.table-button.disabled {
  background: rgba(148, 163, 184, 0.15);
  border-color: rgba(148, 163, 184, 0.35);
  color: #94a3b8;
  cursor: default;
  pointer-events: none;
}

.activity-detail-modal {
  max-width: 480px;
}

.activity-detail-body {
  display: flex;
  flex-direction: column;
  gap: 12px;
  padding: 16px 0;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  font-size: 14px;
}

.detail-label {
  color: #9ca3af;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  font-size: 12px;
}

.detail-value {
  color: #e5e7eb;
  text-align: right;
}

.detail-value.warning {
  color: #fde68a;
  display: inline-flex;
  gap: 6px;
  align-items: center;
}

.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(2, 6, 23, 0.78);
  backdrop-filter: blur(6px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 60;
  padding: 24px;
}

.modal-content {
  width: min(560px, 95vw);
  background: rgba(15, 23, 42, 0.9);
  border: 1px solid rgba(148, 163, 184, 0.25);
  border-radius: 24px;
  box-shadow: 0 25px 60px rgba(0, 0, 0, 0.45);
  padding: 24px;
  position: relative;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 20px;
  margin-bottom: 16px;
}

.modal-eyebrow {
  text-transform: uppercase;
  letter-spacing: 0.08em;
  font-size: 11px;
  color: #94a3b8;
  margin: 0 0 4px;
}

.modal-header h3 {
  margin: 0;
  font-size: 22px;
}

.modal-close {
  background: rgba(15, 23, 42, 0.5);
  border: 1px solid rgba(148, 163, 184, 0.4);
  color: #e2e8f0;
  border-radius: 999px;
  width: 36px;
  height: 36px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}

.activity-detail-body {
  display: flex;
  flex-direction: column;
  gap: 18px;
  margin-bottom: 18px;
}

.detail-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
  gap: 12px;
}

.detail-card {
  background: rgba(15, 23, 42, 0.65);
  border: 1px solid rgba(148, 163, 184, 0.25);
  border-radius: 12px;
  padding: 12px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.detail-card strong,
.detail-card span {
  color: #f3f4f6;
}

.detail-notes {
  border-radius: 16px;
  background: rgba(15, 23, 42, 0.6);
  border: 1px solid rgba(59, 130, 246, 0.25);
  padding: 16px;
}

.detail-notes p {
  margin: 6px 0 0;
  color: #cbd5f5;
  line-height: 1.5;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

.primary-button,
.secondary-button,
.danger-button {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  border-radius: 12px;
  border: 1px solid transparent;
  padding: 10px 16px;
  font-size: 14px;
  cursor: pointer;
  transition: transform 0.2s ease, border-color 0.2s ease;
}

.primary-button {
  background: linear-gradient(120deg, #2563eb, #3b82f6);
  color: white;
  border-color: rgba(59, 130, 246, 0.6);
}

.secondary-button {
  background: rgba(15, 23, 42, 0.6);
  border-color: rgba(148, 163, 184, 0.35);
  color: #e2e8f0;
}

.danger-button {
  background: rgba(239, 68, 68, 0.2);
  border-color: rgba(239, 68, 68, 0.5);
  color: #fecaca;
}

.danger-button.ghost {
  background: transparent;
}

/* Responsive Design - Mobile First Approach */

@media (max-width: 640px) {
  .dashboard-view {
    padding: 0.75rem 0.5rem;
  }

  .weather-summary-card {
    padding: 0.55rem 0.7rem;
    border-radius: 0.85rem;
    margin-bottom: 0.75rem;
  }

  .weather-summary-card::before {
    inset: -20%;
  }

  .weather-summary-card.has-vanta .vanta-canvas {
    opacity: 0.8;
  }

  .current-weather {
    grid-template-columns: 1fr;
    gap: 0.75rem;
  }

  .temp-value {
    font-size: 2.2rem;
    text-align: left;
  }

  .weather-details {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.5rem;
  }

  .detail-item {
    flex-direction: row;
    justify-content: space-between;
    align-items: center;
    padding: 0.65rem 0.75rem;
  }

  .detail-item span {
    font-size: 0.95rem;
  }

  .activities-header {
    flex-direction: column;
    align-items: stretch;
  }

  .activities-header .secondary-button {
    width: 100%;
    justify-content: center;
  }

  .activity-controls {
    flex-direction: column;
    gap: 12px;
  }

  .control-field {
    min-width: unset;
    width: 100%;
  }

  .forecast-cards {
    display: flex;
    overflow-x: auto;
    gap: 0.85rem;
    padding-bottom: 0.5rem;
    scroll-snap-type: x mandatory;
  }

  .forecast-card {
    flex: 0 0 160px;
    scroll-snap-align: start;
  }

  .activity-table,
  .activity-table tbody,
  .activity-table tr,
  .activity-table td {
    display: block;
    width: 100%;
  }

  .activity-table thead {
    display: none;
  }

  .activity-table tr {
    background: rgba(15, 23, 42, 0.75);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 18px;
    padding: 14px 16px;
    margin-bottom: 16px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.35);
  }

  .activity-table td {
    border: none;
    padding: 8px 0;
  }

  .activity-table td::before {
    content: attr(data-label);
    display: block;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #94a3b8;
    margin-bottom: 2px;
  }

  .activity-actions {
    flex-direction: column;
    gap: 10px;
    margin-top: 8px;
  }

  .activity-actions .table-button,
  .activity-actions .table-button.success,
  .activity-actions .table-button.warning,
  .activity-actions .table-button.danger {
    width: 100%;
    justify-content: center;
  }

  .activity-actions .table-button.disabled {
    width: 100%;
  }
}

/* Extra Small Devices (phones, up to 480px) */
@media (max-width: 480px) {
  .dashboard-view {
    padding: 0.75rem 0.5rem;
  }

  .weather-summary-card {
    padding: 1.25rem;
    margin-bottom: 1.25rem;
    border-radius: 1rem;
  }

  .location-info h2 {
    font-size: 1.25rem;
  }

  .location-info i {
    font-size: 1.125rem;
  }

  .temp-value {
    font-size: 3rem;
  }

  .weather-description {
    font-size: 0.875rem;
  }

  .weather-description i {
    font-size: 1.5rem;
  }

  .current-weather {
    flex-direction: column;
    gap: 1.5rem;
  }

  .weather-details {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.75rem;
  }

  .detail-item {
    padding: 0.75rem;
  }

  .detail-item i {
    font-size: 1.125rem;
  }

  .detail-item span {
    font-size: 1rem;
  }

  .forecast-grid h3 {
    font-size: 1.125rem;
    margin-bottom: 1rem;
  }

  .forecast-cards {
    gap: 0.75rem;
  }

  .forecast-card {
    padding: 1rem;
    flex: 0 0 140px;
  }

  .forecast-icon {
    font-size: 1.5rem;
  }

  .temp-max {
    font-size: 0.875rem;
  }

  .temp-min {
    font-size: 0.75rem;
  }

  .system-stats-section,
  .tips-section {
    padding: 1rem;
    margin-bottom: 1.25rem;
  }

  .stats-grid {
    grid-template-columns: 1fr;
    gap: 0.75rem;
  }

  .stat-card {
    padding: 1rem;
  }

  .stat-icon {
    font-size: 1.5rem;
  }

  .stat-value {
    font-size: 1.125rem;
  }
}

/* Small Devices (landscape phones, 481px to 640px) */
@media (min-width: 481px) and (max-width: 640px) {
  .dashboard-view {
    padding: 1.25rem;
  }

  .weather-summary-card {
    padding: 1.5rem;
  }

  .temp-value {
    font-size: 3.5rem;
  }

  .weather-details {
    grid-template-columns: repeat(2, 1fr);
  }

  .forecast-cards {
    gap: 1rem;
  }

  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

/* Medium Devices (tablets, 641px to 768px) */
@media (min-width: 641px) and (max-width: 768px) {
  .dashboard-view {
    padding: 1.5rem;
  }

  .weather-summary-card {
    padding: 1.75rem;
  }

  .temp-value {
    font-size: 4rem;
  }

  .forecast-cards {
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
  }

  .stats-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}

/* Standard Mobile (up to 768px) */
@media (max-width: 768px) {
  .dashboard-view {
    padding: 0.9rem 0.6rem;
  }
  
  .current-weather {
    flex-direction: column;
    gap: 1.5rem;
  }
  
  .weather-details {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1rem;
  }
  
  .forecast-cards {
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
  }
}

/* Large Devices (desktops, 1024px and up) */
@media (min-width: 1024px) {
  .weather-details {
    grid-template-columns: repeat(2, 1fr);
  }

  .forecast-cards {
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
  }

  .stats-grid {
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  }
}

/* Extra Large Devices (large desktops, 1440px and up) */
@media (min-width: 1440px) {
  .dashboard-view {
    padding: 2.5rem;
  }

  .weather-summary-card {
    padding: 2rem;
  }

  .forecast-cards {
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
  }
}

/* Zoom Support - Use relative units for better scaling */
@media (min-resolution: 192dpi) {
  .weather-summary-card {
    border-width: 1px;
  }
}

@keyframes rainfall {
  from {
    background-position: 0 -40px;
  }
  to {
    background-position: 0 40px;
  }
}

@keyframes snowfall {
  from {
    background-position: 0 -60px, 40px -80px;
  }
  to {
    background-position: 0 80px, 40px 60px;
  }
}

@keyframes sunGlow {
  0%, 100% {
    opacity: 0.45;
    transform: scale(1);
  }
  50% {
    opacity: 0.7;
    transform: scale(1.08);
  }
}

@keyframes cloudDrift {
  from {
    background-position: 0 0, 60px 20px;
  }
  to {
    background-position: 80px 30px, 140px 60px;
  }
}

@keyframes pulseGlow {
  0%, 100% {
    transform: scale(1);
    opacity: 0.85;
  }
  50% {
    transform: scale(1.08) translate(2%, -3%);
    opacity: 1;
  }
}
</style>