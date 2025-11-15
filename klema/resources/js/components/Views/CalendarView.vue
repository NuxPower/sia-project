<template>
  <div class="calendar-view">
    <div class="calendar-header">
      <div class="calendar-nav">
        <button @click="previousMonth" class="nav-button" aria-label="Previous month">
          <i class="fas fa-chevron-left"></i>
        </button>
        <button @click="nextMonth" class="nav-button" aria-label="Next month">
          <i class="fas fa-chevron-right"></i>
        </button>
      </div>
      <h2>{{ currentMonthYear }}</h2>
      <button class="add-activity-button" type="button" @click="openCreateViewForDate()">
        <i class="fas fa-plus"></i>
        <span>Add New Activity</span>
      </button>
    </div>

    <div v-if="activityError" class="activity-feedback error">
      <i class="fas fa-exclamation-triangle"></i>
      <span>{{ activityError }}</span>
    </div>

    <div v-if="isLoadingActivities" class="activity-loading">
      <i class="fas fa-spinner fa-spin"></i>
      <span>Loading activities...</span>
    </div>

    <div class="calendar-grid">
      <div class="calendar-weekdays">
        <div v-for="day in weekdays" :key="day" class="weekday">{{ day }}</div>
      </div>
      
      <div class="calendar-days">
        <div
          v-for="(day, index) in calendarDays"
          :key="index"
          class="calendar-day"
          :class="{
            'other-month': !day.currentMonth,
            'today': day.isToday,
            'has-weather': day.hasWeather,
            'no-data': day.weather?.noData,
            'has-activities': day.activities?.length
          }"
          @click="handleDayClick(day)"
        >
          <div class="day-number">{{ day.date }}</div>
          <div v-if="day.hasWeather" class="day-weather">
            <i :class="getWeatherIcon({ condition: day.weather.condition, icon: day.weather.icon })"></i>
            <span class="day-temp">{{ day.weather.temp !== null && day.weather.temp !== undefined ? formatTemperature(day.weather.temp, { decimals: 0 }).replace('°C', '°').replace('°F', '°') : '—' }}</span>
          </div>
          <div v-else-if="day.weather?.noData" class="day-no-data">
            <span>No data available</span>
          </div>
          <div v-if="day.activities?.length" class="day-activities">
            <div
              v-for="(activity, activityIndex) in day.activities.slice(0, 3)"
              :key="activity.id ?? `${activity.start_date}-${activityIndex}`"
              class="activity-chip"
              :class="activity.status || 'pending'"
            >
              <span class="chip-title">{{ activity.activity_type }}</span>
              <span class="chip-field">{{ activity.field }}</span>
              <i
                v-if="activity.weather_warning"
                class="fas fa-exclamation-circle warning-icon"
                :title="activity.weather_warning"
              ></i>
            </div>
            <div v-if="day.activities.length > 3" class="activity-more">
              +{{ day.activities.length - 3 }} more
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="legend">
      <div class="legend-item">
        <div class="legend-dot today"></div>
        <span>Today</span>
      </div>
      <div class="legend-item">
        <div class="legend-dot has-forecast"></div>
        <span>Has Forecast</span>
      </div>
      <div class="legend-item">
        <div class="legend-dot has-activity"></div>
        <span>Has Activity</span>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import axios from 'axios';
import { ensureApiToken } from '../../services/auth';
import { useDisplaySettings } from '../../composables/useDisplaySettings';

const props = defineProps({
  getWeatherIcon: {
    type: Function,
    required: true
  },
  timeline: {
    type: Array,
    default: () => []
  },
  refreshToken: {
    type: Number,
    default: 0
  }
});

const emit = defineEmits(['open-day']);

const weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
const feedbackIconMap = {
  success: 'fas fa-check-circle',
  warning: 'fas fa-exclamation-triangle',
  error: 'fas fa-times-circle'
};
const CUSTOM_ACTIVITY_KEY = 'custom';

const fallbackActivityOptions = [
  { key: 'planting', label: 'Planting', description: 'Field prep, seedbed, transplanting.' },
  { key: 'irrigation', label: 'Irrigation', description: 'Irrigation or fertigation runs.' },
  { key: 'harvesting', label: 'Harvesting', description: 'Cutting, threshing, hauling.' },
  { key: 'fertilizing', label: 'Fertilizing', description: 'Broadcast or foliar applications.' },
  { key: 'pest_control', label: 'Pest & Disease Control', description: 'Spraying pesticides/fungicides.' }
];

const activityTypeOptions = ref([...fallbackActivityOptions]);
const activityTypeLoading = ref(false);
const activityTypeError = ref('');
const selectedActivityTypeKey = ref(fallbackActivityOptions[0]?.key ?? '');
const recommendation = ref(null);
const recommendationLoading = ref(false);
const recommendationError = ref('');
const advisorRequestToken = ref(0);

const currentDate = ref(new Date());
const activities = ref([]);
const isLoadingActivities = ref(false);
const activityError = ref('');
const activityFeedback = ref('');
const activityFeedbackType = ref('success');
const showActivityModal = ref(false);
const isSubmittingActivity = ref(false);
const formErrors = ref({});
const formSubmitError = ref('');

const forecastDays = computed(() => Array.isArray(props.timeline) ? props.timeline : []);

const { getWeatherIcon } = props;
const { formatTemperature } = useDisplaySettings();

function toDateOnly(value) {
  if (!value) return null;
  if (value instanceof Date) {
    return new Date(value.getFullYear(), value.getMonth(), value.getDate());
  }

  if (typeof value === 'string') {
    const [datePart] = value.split('T');
    const parts = datePart.split('-').map((segment) => parseInt(segment, 10));
    if (parts.length >= 3 && parts.every((part) => !Number.isNaN(part))) {
      return new Date(parts[0], parts[1] - 1, parts[2]);
    }

    const parsed = new Date(value);
    if (!Number.isNaN(parsed)) {
      return new Date(parsed.getFullYear(), parsed.getMonth(), parsed.getDate());
    }
  }

  return null;
}

function formatDateKey(value) {
  const date = toDateOnly(value);
  if (!date) {
    return '';
  }

  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

const defaultFormState = () => ({
  activity_type: '',
  field: '',
  start_date: formatDateKey(currentDate.value),
  notes: '',
  status: 'pending'
});

const selectedActivityOption = computed(() =>
  activityTypeOptions.value.find((option) => option.key === selectedActivityTypeKey.value)
);

function applySelectedActivityLabel() {
  if (selectedActivityTypeKey.value === CUSTOM_ACTIVITY_KEY) {
    return;
  }
  if (!selectedActivityTypeKey.value) {
    form.value.activity_type = '';
    return;
  }
  const match = selectedActivityOption.value;
  form.value.activity_type = match?.label ?? '';
}

const form = ref(defaultFormState());
applySelectedActivityLabel();

const recommendationStatusMap = {
  go: {
    label: 'Good window',
    icon: 'fas fa-check-circle',
    class: 'advisor-status success'
  },
  caution: {
    label: 'Proceed with caution',
    icon: 'fas fa-exclamation-triangle',
    class: 'advisor-status warning'
  },
  delay: {
    label: 'Delay activity',
    icon: 'fas fa-times-circle',
    class: 'advisor-status danger'
  },
  unknown: {
    label: 'Guidance unavailable',
    icon: 'fas fa-info-circle',
    class: 'advisor-status neutral'
  }
};

const recommendationStatusMeta = computed(() => {
  const status = recommendation.value?.status ?? 'unknown';
  return recommendationStatusMap[status] || recommendationStatusMap.unknown;
});

const currentMonthYear = computed(() => {
  return currentDate.value.toLocaleDateString('en-US', {
    month: 'long',
    year: 'numeric'
  });
});

const changeMonth = (delta) => {
  const baseDate = currentDate.value instanceof Date ? currentDate.value : new Date();
  const nextDate = new Date(baseDate.getFullYear(), baseDate.getMonth() + delta, 1);
  currentDate.value = nextDate;
};

const previousMonth = () => changeMonth(-1);
const nextMonth = () => changeMonth(1);

const activitiesByDate = computed(() => {
  const grouped = activities.value.reduce((map, activity) => {
    const key = formatDateKey(activity.start_date);
    if (!key) {
      return map;
    }

    if (!map[key]) {
      map[key] = [];
    }
    map[key].push(activity);
    return map;
  }, {});

  Object.values(grouped).forEach((list) => {
    list.sort((a, b) => {
      const aTime = toDateOnly(a.start_date)?.getTime() ?? 0;
      const bTime = toDateOnly(b.start_date)?.getTime() ?? 0;
      return aTime - bTime;
    });
  });

  return grouped;
});

const forecastByDate = computed(() => {
  return forecastDays.value.reduce((map, entry) => {
    if (!entry) return map;
    const dateKey = entry.date || (entry.dt ? new Date(entry.dt * 1000).toISOString().slice(0, 10) : null);
    if (dateKey) {
      map[dateKey] = entry;
    }
    return map;
  }, {});
});

const calendarDays = computed(() => {
  const year = currentDate.value.getFullYear();
  const month = currentDate.value.getMonth();

  const firstDay = new Date(year, month, 1);
  const lastDay = new Date(year, month + 1, 0);
  const startDay = firstDay.getDay();
  const daysInMonth = lastDay.getDate();

  const days = [];
  const today = new Date();
  today.setHours(0, 0, 0, 0);

  const addDay = (dateObj, { currentMonth }) => {
    const normalized = new Date(dateObj.getFullYear(), dateObj.getMonth(), dateObj.getDate());
    const dateKey = formatDateKey(normalized);
    const forecastEntry = forecastByDate.value[dateKey];

    let weatherSummary = null;
    let hasWeather = false;

    if (forecastEntry) {
      if (forecastEntry.noData || forecastEntry.isPlaceholder) {
        weatherSummary = {
          noData: true,
          message: forecastEntry.description || 'No data available',
          raw: forecastEntry
        };
      } else {
        const rawTemp =
          forecastEntry.temp_max
          ?? forecastEntry.temp_min
          ?? forecastEntry.main?.temp
          ?? forecastEntry.temperature
          ?? null;
        const temperature = rawTemp !== null && rawTemp !== undefined ? rawTemp : null;

        weatherSummary = {
          condition: forecastEntry.condition,
          temp: temperature,
          icon: forecastEntry.icon,
          raw: forecastEntry
        };
        hasWeather = true;
      }
    }

    return {
      date: normalized.getDate(),
      currentMonth,
      isToday: normalized.getTime() === today.getTime(),
      weather: weatherSummary,
      hasWeather,
      activities: activitiesByDate.value[dateKey] ?? [],
      fullDate: dateKey
    };
  };

  const prevMonthLastDay = new Date(year, month, 0).getDate();
  for (let i = startDay - 1; i >= 0; i--) {
    const dateObj = new Date(year, month - 1, prevMonthLastDay - i);
    days.push(addDay(dateObj, { currentMonth: false }));
  }

  for (let i = 1; i <= daysInMonth; i++) {
    const dateObj = new Date(year, month, i);
    days.push(addDay(dateObj, { currentMonth: true }));
  }

  const remainingDays = 42 - days.length;
  for (let i = 1; i <= remainingDays; i++) {
    const dateObj = new Date(year, month + 1, i);
    days.push(addDay(dateObj, { currentMonth: false }));
  }

  return days;
});

const fetchActivityTypes = async () => {
  activityTypeLoading.value = true;
  activityTypeError.value = '';
  try {
    await ensureApiToken();
    const axiosInstance = window.axios || axios;
    const { data } = await axiosInstance.get('/api/activities/meta');
    const options = Array.isArray(data?.types) && data.types.length ? data.types : fallbackActivityOptions;
    activityTypeOptions.value = options;

    if (!selectedActivityTypeKey.value && options.length) {
      selectedActivityTypeKey.value = options[0].key;
    }
    applySelectedActivityLabel();
  } catch (error) {
    console.error('Failed to load activity types', error);
    activityTypeError.value =
      error.response?.data?.message || 'Unable to load activity types right now.';
    activityTypeOptions.value = [...fallbackActivityOptions];
    if (!selectedActivityTypeKey.value && fallbackActivityOptions.length) {
      selectedActivityTypeKey.value = fallbackActivityOptions[0].key;
    }
    applySelectedActivityLabel();
  } finally {
    activityTypeLoading.value = false;
  }
};

const fetchActivityRecommendation = async () => {
  if (
    !selectedActivityTypeKey.value ||
    selectedActivityTypeKey.value === CUSTOM_ACTIVITY_KEY ||
    !form.value.start_date
  ) {
    recommendation.value = null;
    recommendationError.value = '';
    recommendationLoading.value = false;
    return;
  }

  const requestToken = Date.now();
  advisorRequestToken.value = requestToken;
  recommendationLoading.value = true;
  recommendationError.value = '';

  try {
    await ensureApiToken();
    const axiosInstance = window.axios || axios;
    const { data } = await axiosInstance.get('/api/activities/recommendation', {
      params: {
        activity_type: selectedActivityTypeKey.value,
        date: form.value.start_date
      }
    });

    if (advisorRequestToken.value !== requestToken) {
      return;
    }

    recommendation.value = data?.recommendation || null;
  } catch (error) {
    if (advisorRequestToken.value !== requestToken) {
      return;
    }
    console.error('Failed to fetch recommendation', error);
    recommendation.value = null;
    recommendationError.value =
      error.response?.data?.message ||
      error.response?.data?.errors?.activity_type?.[0] ||
      'Unable to fetch activity guidance.';
  } finally {
    if (advisorRequestToken.value === requestToken) {
      recommendationLoading.value = false;
    }
  }
};

const fetchActivities = async () => {
  isLoadingActivities.value = true;
  activityError.value = '';
  try {
    // Ensure API token is available before making the request
    await ensureApiToken();
    
    const params = {
      month: currentDate.value.getMonth() + 1,
      year: currentDate.value.getFullYear()
    };
    
    // Use window.axios to ensure we're using the configured instance with interceptors
    const axiosInstance = window.axios || axios;
    const { data } = await axiosInstance.get('/api/activities', { params });
    const fetched = Array.isArray(data?.activities) ? [...data.activities] : [];
    fetched.sort((a, b) => {
      const aTime = toDateOnly(a.start_date)?.getTime() ?? 0;
      const bTime = toDateOnly(b.start_date)?.getTime() ?? 0;
      return aTime - bTime;
    });
    activities.value = fetched;
  } catch (error) {
    console.error('Failed to load activities', error);
    activityError.value = error.response?.data?.message || 'Unable to load activities right now.';
  } finally {
    isLoadingActivities.value = false;
  }
};

const resetForm = (dateKey = formatDateKey(currentDate.value)) => {
  form.value = {
    ...defaultFormState(),
    start_date: dateKey
  };
  formErrors.value = {};
  formSubmitError.value = '';
  if (selectedActivityTypeKey.value && selectedActivityTypeKey.value !== CUSTOM_ACTIVITY_KEY) {
    applySelectedActivityLabel();
  }
};

const openActivityModal = (dateKey = formatDateKey(currentDate.value)) => {
  resetForm(dateKey || formatDateKey(currentDate.value));
  showActivityModal.value = true;
};

const closeActivityModal = () => {
  showActivityModal.value = false;
};

const submitActivity = async () => {
  if (isSubmittingActivity.value) {
    return;
  }

  isSubmittingActivity.value = true;
  formErrors.value = {};
  formSubmitError.value = '';
  activityFeedback.value = '';
  activityFeedbackType.value = 'success';

  try {
    // Ensure API token is available before making the request
    await ensureApiToken();

    const payload = {
      activity_type: form.value.activity_type.trim(),
      field: form.value.field.trim(),
      start_date: form.value.start_date,
      notes: form.value.notes?.trim() || null,
      status: form.value.status || 'pending'
    };

    // Use window.axios to ensure we're using the configured instance with interceptors
    const axiosInstance = window.axios || axios;
    const { data } = await axiosInstance.post('/api/activities', payload);

    if (data?.success) {
      activityFeedback.value = data.message || 'Activity saved successfully!';
      activityFeedbackType.value = data.suitable !== false ? 'success' : 'warning';
      
      // Refresh activities list
      await fetchActivities();
      
      // Close modal after a short delay
      setTimeout(() => {
        closeActivityModal();
        activityFeedback.value = '';
      }, 1500);
    } else {
      formSubmitError.value = data?.message || 'Failed to save activity';
      activityFeedbackType.value = 'error';
    }
  } catch (error) {
    console.error('Failed to save activity', error);
    
    if (error.response?.status === 422) {
      // Validation errors
      formErrors.value = error.response.data.errors || {};
      formSubmitError.value = 'Please fix the errors below';
    } else if (error.response?.status === 401) {
      // Unauthorized - token might be missing or invalid
      formSubmitError.value = 'Authentication required. Please refresh the page and try again.';
    } else {
      formSubmitError.value = error.response?.data?.message || 'Failed to save activity. Please try again.';
    }
    
    activityFeedbackType.value = 'error';
  } finally {
    isSubmittingActivity.value = false;
  }
};

const emitSelectedDay = (dateKey, dayMeta = null) => {
  if (!dateKey) {
    return;
  }

  const selectedDate = toDateOnly(dateKey);
  const today = toDateOnly(new Date());
  const isPast =
    selectedDate && today
      ? selectedDate.getTime() < today.getTime()
      : false;

  const payload = {
    date: dateKey,
    forecast: forecastByDate.value[dateKey] ?? null,
    activities: activitiesByDate.value[dateKey] ?? [],
    dayMeta,
    mode: isPast ? 'view' : 'create'
  };
  emit('open-day', payload);
};

const openCreateViewForDate = (dateKey = formatDateKey(currentDate.value)) => {
  emitSelectedDay(dateKey);
};

const handleDayClick = (day) => {
  emitSelectedDay(day?.fullDate, day);
};

onMounted(() => {
  resetForm(formatDateKey(new Date()));
  fetchActivityTypes();
  fetchActivities();
});

watch(selectedActivityTypeKey, () => {
  if (selectedActivityTypeKey.value === CUSTOM_ACTIVITY_KEY) {
    form.value.activity_type = '';
    recommendation.value = null;
    recommendationError.value = '';
    recommendationLoading.value = false;
    return;
  }
  applySelectedActivityLabel();
});

watch(
  [selectedActivityTypeKey, () => form.value.start_date],
  () => {
    fetchActivityRecommendation();
  }
);

watch(currentDate, (newDate, oldDate) => {
  if (
    !newDate ||
    (oldDate &&
      newDate.getMonth() === oldDate.getMonth() &&
      newDate.getFullYear() === oldDate.getFullYear())
  ) {
    return;
  }
  resetForm(formatDateKey(newDate));
  fetchActivities();
});

watch(
  () => props.refreshToken,
  () => {
    fetchActivities();
  }
);
</script>

<style scoped>
.calendar-view {
  padding: 40px;
  width: 100%;
  max-width: none;
  margin: 0;
  height: 100vh;
  overflow-y: auto;
  box-sizing: border-box;
  
  /* Hide scrollbar */
  scrollbar-width: none; /* Firefox */
  -ms-overflow-style: none; /* IE and Edge */
}

.calendar-view::-webkit-scrollbar {
  display: none; /* Chrome, Safari, Opera */
}

.calendar-header {
  display: grid;
  grid-template-columns: auto 1fr auto;
  align-items: center;
  margin-bottom: 30px;
  background: rgba(0, 0, 0, 0.6);
  padding: 20px 30px;
  border-radius: 16px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  gap: 20px;
}

.calendar-header h2 {
  color: white;
  font-size: 28px;
  margin: 0;
  text-align: center;
}

.calendar-nav {
  display: flex;
  align-items: center;
  gap: 12px;
}

.nav-button {
  background: rgba(59, 130, 246, 0.2);
  border: 1px solid rgba(59, 130, 246, 0.4);
  color: white;
  width: 44px;
  height: 44px;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.3s ease;
  font-size: 16px;
}

.nav-button:hover {
  background: rgba(59, 130, 246, 0.4);
  transform: scale(1.1);
}

.add-activity-button {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  background: rgba(34, 197, 94, 0.2);
  border: 1px solid rgba(34, 197, 94, 0.5);
  color: #bbf7d0;
  padding: 12px 18px;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.3s ease;
  font-weight: 600;
  font-size: 14px;
}

.add-activity-button:hover {
  background: rgba(34, 197, 94, 0.35);
  transform: translateY(-1px);
}

.add-activity-button i {
  font-size: 16px;
}

.activity-feedback {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 14px 18px;
  border-radius: 12px;
  margin-bottom: 16px;
  font-size: 14px;
  background: rgba(34, 197, 94, 0.12);
  border: 1px solid rgba(34, 197, 94, 0.35);
  color: #bbf7d0;
}

.activity-feedback.warning {
  background: rgba(250, 204, 21, 0.15);
  border-color: rgba(250, 204, 21, 0.4);
  color: #fcd34d;
}

.activity-feedback.error {
  background: rgba(239, 68, 68, 0.15);
  border-color: rgba(239, 68, 68, 0.4);
  color: #fecaca;
}

.activity-feedback i {
  font-size: 16px;
}

.activity-loading {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  color: #a5b4fc;
  font-size: 14px;
  margin-bottom: 16px;
}

.activity-loading i {
  font-size: 16px;
}

.calendar-grid {
  background: rgba(0, 0, 0, 0.6);
  border-radius: 16px;
  padding: 20px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  margin-bottom: 20px;
}

.calendar-weekdays {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 10px;
  margin-bottom: 15px;
}

.weekday {
  color: #9ca3af;
  font-weight: bold;
  font-size: 14px;
  text-align: center;
  padding: 10px;
}

.calendar-days {
  display: grid;
  grid-template-columns: repeat(7, minmax(0, 1fr));
  gap: 10px;
  width: 100%;
}

.calendar-day {
  aspect-ratio: 1;
  background: rgba(255, 255, 255, 0.05);
  border-radius: 12px;
  padding: 10px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  border: 1px solid rgba(255, 255, 255, 0.05);
  transition: all 0.3s ease;
  cursor: pointer;
  min-width: 0;
  overflow: hidden;
}

.calendar-day:hover {
  background: rgba(255, 255, 255, 0.1);
  transform: scale(1.05);
}

.calendar-day.other-month {
  opacity: 0.3;
}

.calendar-day.today {
  background: rgba(59, 130, 246, 0.3);
  border: 2px solid rgba(59, 130, 246, 0.6);
}

.calendar-day.has-weather {
  border-color: rgba(34, 197, 94, 0.4);
}

.calendar-day.has-activities {
  border-color: rgba(250, 204, 21, 0.45);
  box-shadow: 0 0 0 1px rgba(250, 204, 21, 0.2);
}

.calendar-day.no-data {
  border-color: rgba(148, 163, 184, 0.35);
  border-style: dashed;
}

.day-number {
  color: white;
  font-weight: bold;
  font-size: 16px;
}

.day-weather {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 5px;
  margin-top: 5px;
}

.day-weather i {
  font-size: 24px;
  color: #60a5fa;
}

.day-temp {
  color: white;
  font-size: 14px;
  font-weight: bold;
}
.activity-type-select {
  display: flex;
  gap: 12px;
  align-items: center;
  flex-wrap: wrap;
}

.activity-type-select select {
  flex: 1;
  min-width: 220px;
  background: rgba(15, 23, 42, 0.6);
  border: 1px solid rgba(59, 130, 246, 0.4);
  color: white;
  padding: 10px 12px;
  border-radius: 10px;
}

.activity-type-select select.invalid {
  border-color: rgba(248, 113, 113, 0.6);
}

.advisor-refresh {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: rgba(59, 130, 246, 0.2);
  border: 1px solid rgba(59, 130, 246, 0.5);
  color: #bfdbfe;
  padding: 8px 12px;
  border-radius: 10px;
  cursor: pointer;
  transition: all 0.2s ease;
  font-size: 13px;
}

.advisor-refresh.secondary {
  background: transparent;
  border-color: rgba(255, 255, 255, 0.2);
}

.advisor-refresh:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.advisor-panel {
  margin-top: 20px;
  background: rgba(15, 23, 42, 0.7);
  border: 1px solid rgba(59, 130, 246, 0.25);
  border-radius: 16px;
  padding: 18px;
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.advisor-panel-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}

.advisor-status {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 6px 12px;
  border-radius: 999px;
  font-size: 13px;
  font-weight: 600;
}

.advisor-status.success {
  background: rgba(22, 163, 74, 0.15);
  color: #bbf7d0;
  border: 1px solid rgba(22, 163, 74, 0.4);
}

.advisor-status.warning {
  background: rgba(250, 204, 21, 0.15);
  color: #fcd34d;
  border: 1px solid rgba(250, 204, 21, 0.4);
}

.advisor-status.danger {
  background: rgba(239, 68, 68, 0.15);
  color: #fecaca;
  border: 1px solid rgba(239, 68, 68, 0.4);
}

.advisor-status.neutral {
  background: rgba(148, 163, 184, 0.2);
  color: #e5e7eb;
  border: 1px solid rgba(148, 163, 184, 0.3);
}

.advisor-panel-loading,
.advisor-panel-error,
.advisor-panel-empty {
  display: flex;
  align-items: center;
  gap: 10px;
  color: #e0e7ff;
  font-size: 14px;
}

.advisor-panel-error {
  color: #fecaca;
}

.advisor-summary {
  margin: 0;
  color: #e0f2fe;
  font-size: 14px;
  line-height: 1.5;
}

.advisor-actions {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.advisor-actions li {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  color: #dbeafe;
  font-size: 13px;
}

.advisor-actions i {
  color: #fde68a;
  margin-top: 2px;
}

.advisor-metrics {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.metric-chip {
  background: rgba(59, 130, 246, 0.15);
  border: 1px solid rgba(59, 130, 246, 0.3);
  border-radius: 12px;
  padding: 10px 12px;
  min-width: 120px;
}

.metric-chip span {
  display: block;
  font-size: 12px;
  color: #bfdbfe;
  margin-bottom: 4px;
}

.metric-chip strong {
  font-size: 16px;
  color: #fff;
}

.advisor-alerts {
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  padding-top: 12px;
}

.alerts-header {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #fde68a;
  font-weight: 600;
  margin-bottom: 8px;
}

.advisor-alerts ul {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.advisor-alerts li {
  font-size: 13px;
  color: #fef3c7;
  display: flex;
  gap: 8px;
}

.advisor-alerts .alert-type {
  text-transform: capitalize;
  color: #fef3c7;
  min-width: 80px;
}

.day-activities {
  margin-top: 8px;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.day-no-data {
  margin-top: 8px;
  font-size: 12px;
  color: #94a3b8;
  text-align: center;
  font-weight: 500;
}

.activity-chip {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 6px;
  padding: 6px 8px;
  border-radius: 8px;
  font-size: 12px;
  line-height: 1.2;
  background: rgba(255, 255, 255, 0.08);
  border: 1px solid rgba(148, 163, 184, 0.25);
  color: #f8fafc;
  min-width: 0;
  flex-wrap: wrap;
}

.activity-chip.pending {
  border-color: rgba(59, 130, 246, 0.45);
  background: rgba(59, 130, 246, 0.12);
}

.activity-chip.in_progress {
  border-color: rgba(34, 197, 94, 0.45);
  background: rgba(34, 197, 94, 0.12);
}

.activity-chip.completed {
  border-color: rgba(16, 185, 129, 0.6);
  background: rgba(16, 185, 129, 0.15);
}

.activity-chip.cancelled {
  border-color: rgba(239, 68, 68, 0.5);
  background: rgba(239, 68, 68, 0.12);
  color: #fee2e2;
}

.chip-title {
  font-weight: 600;
}

.chip-field {
  font-size: 11px;
  color: #bfdbfe;
  margin-left: auto;
}

.warning-icon {
  color: #fbbf24;
  font-size: 12px;
}

.activity-more {
  font-size: 12px;
  color: #fcd34d;
  font-weight: 600;
}

.legend {
  display: flex;
  justify-content: center;
  gap: 30px;
  padding: 20px;
  background: rgba(0, 0, 0, 0.4);
  border-radius: 12px;
}

.legend-dot {
  width: 16px;
  height: 16px;
  border-radius: 4px;
}

.legend-dot.today {
  background: rgba(59, 130, 246, 0.6);
  border: 2px solid #3b82f6;
}

.legend-dot.has-forecast {
  background: rgba(34, 197, 94, 0.3);
  border: 2px solid rgba(34, 197, 94, 0.6);
}

.legend-dot.has-activity {
  background: rgba(250, 204, 21, 0.3);
  border: 2px solid rgba(250, 204, 21, 0.6);
}

.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.25s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}

.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.75);
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 40px 20px;
  z-index: 999;
}

.modal-content {
  width: min(480px, 100%);
  background: rgba(15, 23, 42, 0.95);
  border-radius: 16px;
  border: 1px solid rgba(148, 163, 184, 0.25);
  box-shadow: 0 24px 48px rgba(15, 23, 42, 0.35);
  overflow: hidden;
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 22px 24px 12px;
  color: white;
}

.modal-body {
  padding: 0 24px 24px;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.modal-close {
  background: transparent;
  border: none;
  color: #94a3b8;
  font-size: 18px;
  cursor: pointer;
  transition: color 0.3s ease;
}

.modal-close:hover {
  color: #f1f5f9;
}

.form-field {
  display: flex;
  flex-direction: column;
  gap: 8px;
  color: #cbd5f5;
  font-size: 14px;
}

.form-field input,
.form-field textarea {
  background: rgba(15, 23, 42, 0.6);
  border: 1px solid rgba(148, 163, 184, 0.3);
  border-radius: 10px;
  padding: 10px 12px;
  color: #f8fafc;
  font-size: 14px;
  transition: border 0.2s ease, box-shadow 0.2s ease;
}

.form-field textarea {
  min-height: 80px;
  resize: vertical;
}

.form-field input:focus,
.form-field textarea:focus {
  outline: none;
  border-color: rgba(59, 130, 246, 0.6);
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
}

.form-field input.invalid,
.form-field textarea.invalid {
  border-color: rgba(239, 68, 68, 0.6);
  box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.2);
}

.field-error {
  color: #fecaca;
  font-size: 12px;
}

.form-error-banner {
  display: flex;
  align-items: center;
  gap: 10px;
  background: rgba(239, 68, 68, 0.15);
  border: 1px solid rgba(239, 68, 68, 0.4);
  color: #fecaca;
  padding: 10px 12px;
  border-radius: 10px;
  font-size: 13px;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  padding-top: 10px;
}

.primary-button {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: linear-gradient(135deg, #3b82f6, #2563eb);
  border: none;
  border-radius: 10px;
  color: white;
  padding: 10px 18px;
  font-weight: 600;
  cursor: pointer;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.primary-button:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.primary-button:not(:disabled):hover {
  transform: translateY(-1px);
  box-shadow: 0 8px 16px rgba(59, 130, 246, 0.25);
}

.secondary-button {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: rgba(148, 163, 184, 0.15);
  border: 1px solid rgba(148, 163, 184, 0.35);
  color: #e2e8f0;
  padding: 10px 16px;
  border-radius: 10px;
  font-weight: 500;
  cursor: pointer;
  transition: background 0.2s ease;
}

.secondary-button:hover {
  background: rgba(148, 163, 184, 0.25);
}

/* Responsive Design - Mobile First Approach */

/* Extra Small Devices (phones, up to 480px) */
@media (max-width: 480px) {
  .calendar-view {
    padding: 0.9375rem 0.5rem;
    width: 100%;
    max-width: 100%;
    min-width: 100%;
    margin-left: 0;
    margin-right: 0;
    align-self: stretch;
  }
  
  .calendar-header {
    padding: 0.75rem 0.9375rem;
    gap: 0.625rem;
    grid-template-columns: 1fr;
    border-radius: 0.75rem;
  }
  
  .calendar-header h2 {
    font-size: 1.125rem;
  }
  
  .calendar-nav {
    justify-content: space-between;
  }

  .add-activity-button {
    font-size: 0.8125rem;
    padding: 0.625rem;
    width: 100%;
    justify-content: center;
  }
  
  .nav-button {
    width: 2.25rem;
    height: 2.25rem;
    font-size: 0.8125rem;
  }
  
  .calendar-grid {
    padding: 0.625rem;
    border-radius: 0.75rem;
  }
  
  .weekday {
    font-size: 0.625rem;
    padding: 0.375rem 0.125rem;
  }
  
  .calendar-days {
    gap: 0.25rem;
  }
  
  .calendar-day {
    padding: 0.25rem;
    border-radius: 0.5rem;
  }
  
  .day-number {
    font-size: 0.6875rem;
  }
  
  .day-weather i {
    font-size: 1rem;
  }
  
  .day-temp {
    font-size: 0.625rem;
  }

  .activity-chip {
    padding: 0.375rem 0.5rem;
    font-size: 0.6875rem;
    flex-direction: column;
    align-items: flex-start;
  }

  .chip-field {
    margin-left: 0;
    font-size: 0.625rem;
  }

  .activity-feedback {
    flex-direction: column;
    align-items: flex-start;
    padding: 0.75rem;
    font-size: 0.8125rem;
  }
  
  .legend {
    flex-direction: column;
    gap: 0.625rem;
    padding: 0.9375rem;
  }
  
  .legend-item {
    font-size: 0.8125rem;
  }

  .modal-content {
    width: 95%;
    max-width: none;
    margin: 0.5rem;
  }

  .modal-header {
    padding: 1rem;
  }

  .modal-body {
    padding: 1rem;
  }
}

/* Small Devices (landscape phones, 481px to 640px) */
@media (min-width: 481px) and (max-width: 640px) {
  .calendar-view {
    padding: 1.25rem 0.75rem;
    width: 100%;
    max-width: 100%;
    min-width: 100%;
    margin-left: 0;
    margin-right: 0;
    align-self: stretch;
  }

  .calendar-header {
    padding: 1rem 1.25rem;
  }

  .calendar-header h2 {
    font-size: 1.25rem;
  }

  .nav-button {
    width: 2.5rem;
    height: 2.5rem;
  }

  .calendar-grid {
    padding: 1rem;
  }

  .weekday {
    font-size: 0.75rem;
    padding: 0.5rem 0.25rem;
  }

  .calendar-days {
    gap: 0.375rem;
  }

  .calendar-day {
    padding: 0.5rem;
  }

  .day-number {
    font-size: 0.875rem;
  }
}

/* Medium Devices (tablets, 641px to 768px) */
@media (min-width: 641px) and (max-width: 768px) {
  .calendar-view {
    padding: 1.5rem 1rem;
    width: 100%;
    max-width: 100%;
    min-width: 100%;
    margin-left: 0;
    margin-right: 0;
    align-self: stretch;
  }

  .calendar-header {
    padding: 1.25rem 1.5rem;
  }

  .calendar-header h2 {
    font-size: 1.375rem;
  }

  .nav-button {
    width: 2.75rem;
    height: 2.75rem;
  }
}

/* Standard Mobile (up to 768px) */
@media (max-width: 768px) {
  .calendar-view {
    padding: 1.25rem 0.625rem;
    width: 100%;
    max-width: 100%;
    min-width: 100%;
    margin-left: 0;
    margin-right: 0;
    align-self: stretch;
  }
  
  .calendar-header {
    padding: 0.9375rem 1.25rem;
    grid-template-columns: 1fr;
    gap: 0.75rem;
  }
  
  .calendar-header h2 {
    font-size: 1.25rem;
  }
  
  .calendar-nav {
    justify-content: space-between;
  }

  .add-activity-button {
    width: 100%;
    justify-content: center;
  }

  .nav-button {
    width: 2.5rem;
    height: 2.5rem;
    font-size: 0.875rem;
  }
  
  .calendar-grid {
    padding: 0.9375rem;
  }
  
  .weekday {
    font-size: 0.6875rem;
    padding: 0.5rem 0.25rem;
  }
  
  .calendar-days {
    gap: 0.375rem;
  }
  
  .calendar-day {
    padding: 0.375rem;
    border-radius: 0.5rem;
  }
  
  .day-number {
    font-size: 0.75rem;
  }
  
  .day-weather i {
    font-size: 1.125rem;
  }
  
  .day-temp {
    font-size: 0.6875rem;
  }

  .activity-chip {
    flex-direction: column;
    align-items: flex-start;
  }

  .chip-field {
    margin-left: 0;
  }
  
  .legend {
    flex-direction: column;
    gap: 0.625rem;
    padding: 0.9375rem;
  }
  
  .legend-item {
    font-size: 0.8125rem;
  }
}

/* Large Devices (desktops, 1024px and up) */
@media (min-width: 1024px) {
  .calendar-view {
    width: 100%;
    max-width: none;
    margin: 0;
  }

  .calendar-header {
    grid-template-columns: auto 1fr auto;
  }
}

/* Extra Large Devices (large desktops, 1440px and up) */
@media (min-width: 1440px) {
  .calendar-view {
    padding: 2.5rem;
    width: 100%;
    max-width: none;
    margin: 0;
  }

  .calendar-header {
    padding: 1.875rem 2.5rem;
  }

  .calendar-header h2 {
    font-size: 1.75rem;
  }
}

/* Zoom Support - Ensure proper scaling */
@media (min-resolution: 192dpi) {
  .calendar-day {
    border-width: 1px;
  }
}
</style>