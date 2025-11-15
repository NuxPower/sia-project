<template>
  <div class="calendar-create-view">
    <header class="create-view-header">
      <div class="header-left">
        <button class="ghost-button" type="button" @click="$emit('close')">
          <i class="fas fa-chevron-left"></i>
          <span>Back to Calendar</span>
        </button>
        <h2>{{ formattedDate }}</h2>
        <p class="date-subtitle">
          {{ daySummary }}
        </p>
      </div>
      <div class="header-right">
        <span class="pill" :class="recommendationStatusMeta.class">
          <i :class="recommendationStatusMeta.icon"></i>
          {{ recommendationStatusMeta.label }}
        </span>
      </div>
    </header>

    <section class="weather-context" v-if="dayWeather">
      <div class="weather-icon">
        <i :class="getWeatherIcon(dayWeather)" />
      </div>
      <div class="weather-details">
        <div class="temp-row">
          <strong>{{ formatTemperature(displayTemp(dayWeather), { decimals: 0 }).replace('°C', '°') }}</strong>
          <span class="condition">{{ dayWeather.description || dayWeather.condition || 'Unknown conditions' }}</span>
        </div>
        <div class="metric-row">
          <span>High {{ formatTemperature(dayWeather.temp_max ?? dayWeather.main?.temp_max ?? null, { decimals: 0 }).replace('°C', '°') }}</span>
          <span>Low {{ formatTemperature(dayWeather.temp_min ?? dayWeather.main?.temp_min ?? null, { decimals: 0 }).replace('°C', '°') }}</span>
          <span>Rain {{ dayWeather.precipitation_sum ?? dayWeather.precip_mm ?? 0 }} mm</span>
        </div>
      </div>
    </section>

    <section v-if="existingActivities.length" class="existing-activities">
      <h3>Planned activities on this day</h3>
      <ul>
        <li v-for="activity in existingActivities" :key="activity.id ?? activity.start_date">
          <span class="activity-type">{{ activity.activity_type }}</span>
          <span class="activity-field">{{ activity.field }}</span>
          <span class="activity-status" :class="activity.status">{{ activity.status }}</span>
        </li>
      </ul>
    </section>

    <form class="create-form" @submit.prevent="submitActivity">
      <div v-if="activityFeedback" class="activity-feedback" :class="activityFeedbackType">
        <i :class="feedbackIconMap[activityFeedbackType]"></i>
        <span>{{ activityFeedback }}</span>
      </div>
      <div v-if="formSubmitError" class="form-error-banner">
        <i class="fas fa-exclamation-circle"></i>
        <span>{{ formSubmitError }}</span>
      </div>

      <label class="form-field">
        <span>Activity Type</span>
        <div class="activity-type-select">
          <select
            v-model="selectedActivityTypeKey"
            :disabled="activityTypeLoading"
            :class="{ invalid: formErrors.activity_type }"
            required
          >
            <option value="">
              {{ activityTypeLoading ? 'Loading activity types...' : 'Select an activity' }}
            </option>
            <option
              v-for="option in activityTypeOptions"
              :key="option.key"
              :value="option.key"
            >
              {{ option.label }}
            </option>
            <option :value="CUSTOM_ACTIVITY_KEY">Custom activity</option>
          </select>
          <button
            v-if="selectedActivityTypeKey && selectedActivityTypeKey !== CUSTOM_ACTIVITY_KEY"
            class="advisor-refresh"
            type="button"
            :disabled="recommendationLoading"
            @click="fetchActivityRecommendation"
          >
            <i :class="recommendationLoading ? 'fas fa-spinner fa-spin' : 'fas fa-sync-alt'"></i>
            <span>Refresh guidance</span>
          </button>
        </div>
        <input
          v-if="selectedActivityTypeKey === CUSTOM_ACTIVITY_KEY"
          v-model="form.activity_type"
          type="text"
          placeholder="Describe the activity"
          :class="{ invalid: formErrors.activity_type }"
          required
        />
        <small v-if="activityTypeError" class="field-error">
          {{ activityTypeError }}
        </small>
        <small v-if="formErrors.activity_type" class="field-error">
          {{ formErrors.activity_type[0] }}
        </small>
      </label>

      <label class="form-field">
        <span>Field</span>
        <input
          v-model="form.field"
          type="text"
          placeholder="Field identifier or location"
          :class="{ invalid: formErrors.field }"
          required
        />
        <small v-if="formErrors.field" class="field-error">
          {{ formErrors.field[0] }}
        </small>
      </label>

      <label class="form-field">
        <span>Start Date</span>
        <input
          v-model="form.start_date"
          type="date"
          :min="todayDateKey"
          :class="{ invalid: formErrors.start_date }"
          required
        />
        <small v-if="formErrors.start_date" class="field-error">
          {{ formErrors.start_date[0] }}
        </small>
      </label>

      <label class="form-field">
        <span>Notes (optional)</span>
        <textarea
          v-model="form.notes"
          placeholder="Add any additional details"
        ></textarea>
        <small v-if="formErrors.notes" class="field-error">
          {{ formErrors.notes[0] }}
        </small>
      </label>

      <div
        v-if="selectedActivityTypeKey && selectedActivityTypeKey !== CUSTOM_ACTIVITY_KEY"
        class="advisor-panel"
      >
        <div class="advisor-panel-header">
          <div :class="recommendationStatusMeta.class">
            <i :class="recommendationStatusMeta.icon"></i>
            <span>{{ recommendationStatusMeta.label }}</span>
          </div>
          <button
            type="button"
            class="advisor-refresh secondary"
            :disabled="recommendationLoading"
            @click="fetchActivityRecommendation"
          >
            <i :class="recommendationLoading ? 'fas fa-spinner fa-spin' : 'fas fa-sync-alt'"></i>
            <span>Update</span>
          </button>
        </div>

        <div v-if="recommendationLoading" class="advisor-panel-loading">
          <i class="fas fa-spinner fa-spin"></i>
          <span>Calculating guidance with latest weather & alerts...</span>
        </div>

        <div v-else-if="recommendationError" class="advisor-panel-error">
          <i class="fas fa-exclamation-circle"></i>
          <span>{{ recommendationError }}</span>
        </div>

        <template v-else-if="recommendation">
          <p class="advisor-summary">
            {{ recommendation.summary }}
          </p>

          <ul v-if="recommendation.recommendations?.length" class="advisor-actions">
            <li v-for="tip in recommendation.recommendations" :key="tip">
              <i class="fas fa-lightbulb"></i>
              <span>{{ tip }}</span>
            </li>
          </ul>

          <div v-if="recommendation.metrics" class="advisor-metrics">
            <div
              v-if="recommendation.metrics.precip_mm !== undefined && recommendation.metrics.precip_mm !== null"
              class="metric-chip"
            >
              <span>Rain (24h)</span>
              <strong>{{ recommendation.metrics.precip_mm }} mm</strong>
            </div>
            <div
              v-if="recommendation.metrics.wind_max_kmh !== undefined && recommendation.metrics.wind_max_kmh !== null"
              class="metric-chip"
            >
              <span>Wind gusts</span>
              <strong>{{ recommendation.metrics.wind_max_kmh }} km/h</strong>
            </div>
            <div
              v-if="recommendation.metrics.temp_max !== undefined && recommendation.metrics.temp_max !== null"
              class="metric-chip"
            >
              <span>High</span>
              <strong>{{ recommendation.metrics.temp_max }}°C</strong>
            </div>
            <div
              v-if="recommendation.metrics.temp_min !== undefined && recommendation.metrics.temp_min !== null"
              class="metric-chip"
            >
              <span>Low</span>
              <strong>{{ recommendation.metrics.temp_min }}°C</strong>
            </div>
            <div
              v-if="recommendation.metrics.humidity_avg !== undefined && recommendation.metrics.humidity_avg !== null"
              class="metric-chip"
            >
              <span>Humidity</span>
              <strong>{{ recommendation.metrics.humidity_avg }}%</strong>
            </div>
            <div
              v-if="recommendation.metrics.precip_probability !== undefined && recommendation.metrics.precip_probability !== null"
              class="metric-chip"
            >
              <span>Rain chance</span>
              <strong>{{ recommendation.metrics.precip_probability }}%</strong>
            </div>
          </div>

          <div v-if="recommendation.alerts?.length" class="advisor-alerts">
            <div class="alerts-header">
              <i class="fas fa-bell"></i>
              <span>Related alerts</span>
            </div>
            <ul>
              <li v-for="alert in recommendation.alerts" :key="alert.id">
                <strong class="alert-type">{{ alert.type }}</strong>
                <span>{{ alert.message }}</span>
              </li>
            </ul>
          </div>
        </template>

        <div v-else class="advisor-panel-empty">
          <span>Select a date to see activity-specific recommendations.</span>
        </div>
      </div>

      <footer class="form-footer">
        <button type="button" class="secondary-button" @click="$emit('close')">Cancel</button>
        <button type="submit" class="primary-button" :disabled="isSubmittingActivity">
          <i v-if="isSubmittingActivity" class="fas fa-spinner fa-spin"></i>
          <span>{{ isSubmittingActivity ? 'Saving...' : 'Save Activity' }}</span>
        </button>
      </footer>
    </form>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import axios from 'axios';
import { ensureApiToken } from '../../services/auth';
import { useDisplaySettings } from '../../composables/useDisplaySettings';

const feedbackIconMap = {
  success: 'fas fa-check-circle',
  warning: 'fas fa-exclamation-triangle',
  error: 'fas fa-times-circle'
};

const props = defineProps({
  detail: {
    type: Object,
    required: true
  }
});

const emit = defineEmits(['close', 'saved']);

const { formatTemperature } = useDisplaySettings();

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

const activityFeedback = ref('');
const activityFeedbackType = ref('success');
const isSubmittingActivity = ref(false);
const formErrors = ref({});
const formSubmitError = ref('');

const todayDateKey = formatDateKey(new Date());
const form = ref({
  activity_type: fallbackActivityOptions[0]?.label ?? '',
  field: '',
  start_date: props.detail?.date ?? todayDateKey,
  notes: '',
  status: 'pending'
});

const selectedActivityOption = computed(() =>
  activityTypeOptions.value.find((option) => option.key === selectedActivityTypeKey.value)
);

const dayWeather = computed(() => props.detail?.forecast ?? null);
const existingActivities = computed(() => props.detail?.activities ?? []);

const formattedDate = computed(() => {
  const date = toDateOnly(form.value.start_date);
  if (!date) {
    return 'Select a date';
  }
  return date.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
});

const daySummary = computed(() => {
  if (!dayWeather.value) {
    return 'Weather data unavailable';
  }
  const condition = dayWeather.value.description || dayWeather.value.condition || 'Unknown';
  return `${condition} • High ${dayWeather.value.temp_max ?? '-'}° / Low ${dayWeather.value.temp_min ?? '-'}°`;
});

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

const applySelectedActivityLabel = () => {
  if (selectedActivityTypeKey.value === CUSTOM_ACTIVITY_KEY) {
    form.value.activity_type = '';
    return;
  }
  if (!selectedActivityTypeKey.value) {
    form.value.activity_type = '';
    return;
  }
  const match = selectedActivityOption.value;
  form.value.activity_type = match?.label ?? '';
};

const clampToToday = (dateKey) => {
  const date = toDateOnly(dateKey);
  const today = toDateOnly(new Date());
  if (!date || !today) {
    return todayDateKey;
  }

  return date.getTime() < today.getTime() ? todayDateKey : formatDateKey(date);
};

const resetForm = (dateKey) => {
  form.value = {
    activity_type: (form.value.activity_type || fallbackActivityOptions[0]?.label) ?? '',
    field: '',
    start_date: clampToToday(dateKey || todayDateKey),
    notes: '',
    status: 'pending'
  };
  formErrors.value = {};
  formSubmitError.value = '';
  applySelectedActivityLabel();
};

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
    await ensureApiToken();

    const payload = {
      activity_type: form.value.activity_type.trim(),
      field: form.value.field.trim(),
      start_date: form.value.start_date,
      notes: form.value.notes?.trim() || null,
      status: form.value.status || 'pending'
    };

    const axiosInstance = window.axios || axios;
    const { data } = await axiosInstance.post('/api/activities', payload);

    if (data?.success) {
      activityFeedback.value = data.message || 'Activity saved successfully!';
      activityFeedbackType.value = data.suitable !== false ? 'success' : 'warning';
      emit('saved');
    } else {
      formSubmitError.value = data?.message || 'Failed to save activity';
      activityFeedbackType.value = 'error';
    }
  } catch (error) {
    console.error('Failed to save activity', error);

    if (error.response?.status === 422) {
      formErrors.value = error.response.data.errors || {};
      formSubmitError.value = 'Please fix the errors below';
    } else if (error.response?.status === 401) {
      formSubmitError.value = 'Authentication required. Please refresh the page and try again.';
    } else {
      formSubmitError.value = error.response?.data?.message || 'Failed to save activity. Please try again.';
    }

    activityFeedbackType.value = 'error';
  } finally {
    isSubmittingActivity.value = false;
  }
};

const displayTemp = (entry) => {
  if (!entry) return null;
  return entry.temp ?? entry.main?.temp ?? entry.temp_max ?? entry.temp_min ?? null;
};

const getWeatherIcon = (entry) => {
  const icon = entry?.icon || entry?.weather?.[0]?.icon || 'fas fa-cloud';
  const condition = entry?.condition || entry?.weather?.[0]?.main || '';

  const conditionMap = {
    Rain: 'fas fa-cloud-showers-heavy',
    Drizzle: 'fas fa-cloud-rain',
    Clouds: 'fas fa-cloud',
    Clear: 'fas fa-sun',
    Thunderstorm: 'fas fa-bolt',
    Snow: 'fas fa-snowflake',
    Mist: 'fas fa-smog',
    Fog: 'fas fa-smog'
  };

  return conditionMap[condition] || `owf owf-${icon}`;
};

watch(
  () => props.detail?.date,
  (date) => {
    resetForm(date || formatDateKey(new Date()));
    fetchActivityRecommendation();
  },
  { immediate: true }
);

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

onMounted(() => {
  fetchActivityTypes();
  fetchActivityRecommendation();
});
</script>

<style scoped>
.calendar-create-view {
  position: fixed;
  inset: 0;
  background: #0a0e1a;
  color: #f9fafb;
  display: flex;
  flex-direction: column;
  height: 100%;
  overflow-y: auto;
  z-index: 5000;
  animation: fadeIn 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

.calendar-create-view::before {
  content: '';
  position: fixed;
  inset: 0;
  background: 
    radial-gradient(circle at 20% 30%, rgba(59, 130, 246, 0.08) 0%, transparent 50%),
    radial-gradient(circle at 80% 70%, rgba(139, 92, 246, 0.06) 0%, transparent 50%);
  pointer-events: none;
  z-index: -1;
}

.create-view-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 24px;
  padding: 28px 32px 24px;
  background: rgba(15, 23, 42, 0.6);
  backdrop-filter: blur(20px);
  border-bottom: 1px solid rgba(59, 130, 246, 0.1);
  position: sticky;
  top: 0;
  z-index: 10;
}

.header-left {
  flex: 1;
}

.ghost-button {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: transparent;
  border: none;
  color: #94a3b8;
  padding: 8px 0;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  transition: all 0.2s ease;
}

.ghost-button:hover {
  color: #60a5fa;
  transform: translateX(-2px);
}

.ghost-button i {
  font-size: 12px;
}

.create-view-header h2 {
  margin: 12px 0 0;
  font-size: 28px;
  font-weight: 700;
  background: linear-gradient(135deg, #ffffff 0%, #cbd5e1 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.date-subtitle {
  margin: 6px 0 0;
  color: #64748b;
  font-size: 14px;
  font-weight: 500;
}

.pill {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 16px;
  border-radius: 999px;
  text-transform: uppercase;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.5px;
}

.weather-context {
  display: flex;
  gap: 20px;
  padding: 24px;
  margin: 24px 32px;
  background: linear-gradient(135deg, rgba(15, 23, 42, 0.8) 0%, rgba(30, 41, 59, 0.6) 100%);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(59, 130, 246, 0.2);
  border-radius: 20px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
}

.weather-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 80px;
  height: 80px;
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.15) 0%, rgba(99, 102, 241, 0.1) 100%);
  border-radius: 20px;
  border: 1px solid rgba(59, 130, 246, 0.3);
}

.weather-icon i {
  font-size: 40px;
  color: #60a5fa;
}

.weather-details {
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: 8px;
}

.temp-row {
  display: flex;
  align-items: baseline;
  gap: 12px;
}

.temp-row strong {
  font-size: 36px;
  font-weight: 700;
  color: #ffffff;
}

.temp-row .condition {
  font-size: 16px;
  color: #94a3b8;
  font-weight: 500;
}

.metric-row {
  display: flex;
  gap: 20px;
  font-size: 13px;
  color: #64748b;
  font-weight: 500;
}

.metric-row span {
  padding: 4px 12px;
  background: rgba(15, 23, 42, 0.6);
  border-radius: 8px;
  border: 1px solid rgba(59, 130, 246, 0.15);
}

.existing-activities {
  background: linear-gradient(135deg, rgba(15, 23, 42, 0.6) 0%, rgba(30, 41, 59, 0.4) 100%);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(148, 163, 184, 0.15);
  border-radius: 20px;
  padding: 20px 24px;
  margin: 0 32px 24px;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
}

.existing-activities h3 {
  margin: 0 0 16px;
  font-size: 16px;
  font-weight: 600;
  color: #e2e8f0;
}

.existing-activities ul {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.existing-activities li {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 12px 16px;
  background: rgba(15, 23, 42, 0.5);
  border: 1px solid rgba(59, 130, 246, 0.15);
  border-radius: 12px;
  font-size: 14px;
  transition: all 0.2s ease;
}

.existing-activities li:hover {
  background: rgba(15, 23, 42, 0.7);
  border-color: rgba(59, 130, 246, 0.3);
}

.activity-type {
  font-weight: 600;
  color: #bfdbfe;
}

.activity-field {
  color: #94a3b8;
}

.activity-status {
  text-transform: capitalize;
  padding: 4px 12px;
  background: rgba(59, 130, 246, 0.15);
  border-radius: 6px;
  font-size: 12px;
  font-weight: 600;
  color: #bfdbfe;
}

.create-form {
  display: flex;
  flex-direction: column;
  gap: 24px;
  padding: 32px;
  margin: 0 32px 32px;
  background: linear-gradient(135deg, rgba(15, 23, 42, 0.6) 0%, rgba(30, 41, 59, 0.4) 100%);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(148, 163, 184, 0.15);
  border-radius: 20px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
}

.form-field {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.form-field > span {
  font-size: 13px;
  font-weight: 600;
  color: #cbd5e1;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.form-field input,
.form-field textarea,
.form-field select {
  background: rgba(15, 23, 42, 0.8);
  border: 1px solid rgba(59, 130, 246, 0.3);
  color: white;
  padding: 12px 16px;
  border-radius: 12px;
  font-size: 15px;
  transition: all 0.2s ease;
  font-family: inherit;
}

.form-field input:focus,
.form-field textarea:focus,
.form-field select:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
  background: rgba(15, 23, 42, 0.9);
}

.form-field textarea {
  min-height: 100px;
  resize: vertical;
}

.form-field input.invalid,
.form-field select.invalid {
  border-color: #ef4444;
}

.form-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  padding-top: 12px;
  border-top: 1px solid rgba(148, 163, 184, 0.1);
}

.activity-feedback {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px 20px;
  border-radius: 14px;
  font-size: 14px;
  font-weight: 500;
  background: rgba(34, 197, 94, 0.12);
  border: 1px solid rgba(34, 197, 94, 0.35);
  color: #bbf7d0;
  box-shadow: 0 4px 12px rgba(34, 197, 94, 0.1);
}

.activity-feedback i {
  font-size: 18px;
}

.activity-feedback.warning {
  background: rgba(250, 204, 21, 0.15);
  border-color: rgba(250, 204, 21, 0.4);
  color: #fcd34d;
  box-shadow: 0 4px 12px rgba(250, 204, 21, 0.1);
}

.activity-feedback.error {
  background: rgba(239, 68, 68, 0.15);
  border-color: rgba(239, 68, 68, 0.4);
  color: #fecaca;
  box-shadow: 0 4px 12px rgba(239, 68, 68, 0.1);
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
}

.advisor-refresh {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: rgba(59, 130, 246, 0.15);
  border: 1px solid rgba(59, 130, 246, 0.4);
  color: #bfdbfe;
  padding: 10px 16px;
  border-radius: 10px;
  cursor: pointer;
  transition: all 0.2s ease;
  font-size: 13px;
  font-weight: 600;
}

.advisor-refresh:hover:not(:disabled) {
  background: rgba(59, 130, 246, 0.25);
  border-color: rgba(59, 130, 246, 0.6);
  transform: translateY(-1px);
}

.advisor-refresh:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.advisor-refresh.secondary {
  background: rgba(15, 23, 42, 0.6);
  border-color: rgba(148, 163, 184, 0.3);
}

.advisor-panel {
  margin-top: 8px;
  background: linear-gradient(135deg, rgba(15, 23, 42, 0.9) 0%, rgba(30, 41, 59, 0.7) 100%);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(59, 130, 246, 0.25);
  border-radius: 18px;
  padding: 24px;
  display: flex;
  flex-direction: column;
  gap: 18px;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
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
  gap: 10px;
  padding: 8px 16px;
  border-radius: 999px;
  font-size: 13px;
  font-weight: 700;
  letter-spacing: 0.3px;
  text-transform: uppercase;
}

.advisor-status.success {
  background: linear-gradient(135deg, rgba(22, 163, 74, 0.2) 0%, rgba(34, 197, 94, 0.15) 100%);
  color: #bbf7d0;
  border: 1px solid rgba(22, 163, 74, 0.5);
  box-shadow: 0 4px 12px rgba(22, 163, 74, 0.2);
}

.advisor-status.warning {
  background: linear-gradient(135deg, rgba(250, 204, 21, 0.2) 0%, rgba(234, 179, 8, 0.15) 100%);
  color: #fcd34d;
  border: 1px solid rgba(250, 204, 21, 0.5);
  box-shadow: 0 4px 12px rgba(250, 204, 21, 0.2);
}

.advisor-status.danger {
  background: linear-gradient(135deg, rgba(239, 68, 68, 0.2) 0%, rgba(220, 38, 38, 0.15) 100%);
  color: #fecaca;
  border: 1px solid rgba(239, 68, 68, 0.5);
  box-shadow: 0 4px 12px rgba(239, 68, 68, 0.2);
}

.advisor-status.neutral {
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.15) 0%, rgba(99, 102, 241, 0.1) 100%);
  color: #bfdbfe;
  border: 1px solid rgba(59, 130, 246, 0.4);
}

.advisor-panel-loading,
.advisor-panel-error,
.advisor-panel-empty {
  display: flex;
  align-items: center;
  gap: 12px;
  color: #cbd5e1;
  font-size: 14px;
  padding: 16px;
  background: rgba(15, 23, 42, 0.5);
  border-radius: 12px;
}

.advisor-panel-error {
  color: #fecaca;
  background: rgba(239, 68, 68, 0.1);
}

.advisor-summary {
  margin: 0;
  color: #e0f2fe;
  font-size: 15px;
  line-height: 1.6;
  padding: 16px;
  background: rgba(15, 23, 42, 0.5);
  border-radius: 12px;
  border-left: 3px solid #3b82f6;
}

.advisor-actions {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.advisor-actions li {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  color: #dbeafe;
  font-size: 14px;
  padding: 12px 16px;
  background: rgba(15, 23, 42, 0.4);
  border-radius: 10px;
  border-left: 2px solid #fbbf24;
}

.advisor-actions i {
  color: #fbbf24;
  margin-top: 2px;
  font-size: 16px;
}

.advisor-metrics {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
  gap: 12px;
}

.metric-chip {
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.15) 0%, rgba(99, 102, 241, 0.1) 100%);
  border: 1px solid rgba(59, 130, 246, 0.3);
  border-radius: 14px;
  padding: 14px 16px;
  display: flex;
  flex-direction: column;
  gap: 6px;
  transition: all 0.2s ease;
}

.metric-chip:hover {
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.2) 0%, rgba(99, 102, 241, 0.15) 100%);
  border-color: rgba(59, 130, 246, 0.4);
  transform: translateY(-2px);
}

.metric-chip span {
  font-size: 11px;
  color: #94a3b8;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.metric-chip strong {
  font-size: 20px;
  color: #ffffff;
  font-weight: 700;
}

.advisor-alerts {
  border-top: 1px solid rgba(59, 130, 246, 0.15);
  padding-top: 16px;
  margin-top: 4px;
}

.alerts-header {
  display: flex;
  align-items: center;
  gap: 10px;
  color: #fbbf24;
  font-weight: 700;
  margin-bottom: 12px;
  font-size: 14px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.advisor-alerts ul {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.advisor-alerts li {
  padding: 12px 16px;
  background: rgba(250, 204, 21, 0.1);
  border: 1px solid rgba(250, 204, 21, 0.3);
  border-radius: 10px;
  font-size: 13px;
  color: #fef3c7;
}

.alert-type {
  display: block;
  color: #fbbf24;
  font-weight: 700;
  margin-bottom: 4px;
  text-transform: uppercase;
  font-size: 11px;
  letter-spacing: 0.5px;
}

.field-error {
  color: #fca5a5;
  font-size: 12px;
  font-weight: 500;
  margin-top: 4px;
}

.form-error-banner {
  display: flex;
  align-items: center;
  gap: 12px;
  background: linear-gradient(135deg, rgba(239, 68, 68, 0.2) 0%, rgba(220, 38, 38, 0.15) 100%);
  border: 1px solid rgba(239, 68, 68, 0.4);
  color: #fecaca;
  padding: 14px 18px;
  border-radius: 12px;
  font-size: 14px;
  font-weight: 500;
  box-shadow: 0 4px 12px rgba(239, 68, 68, 0.15);
}

.form-error-banner i {
  font-size: 18px;
}

.primary-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
  border: none;
  border-radius: 12px;
  color: white;
  padding: 12px 24px;
  font-weight: 700;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 4px 16px rgba(59, 130, 246, 0.3);
  letter-spacing: 0.3px;
}

.primary-button:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(59, 130, 246, 0.4);
  background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
}

.primary-button:active:not(:disabled) {
  transform: translateY(0);
}

.primary-button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

.secondary-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  background: rgba(15, 23, 42, 0.6);
  border: 1px solid rgba(148, 163, 184, 0.3);
  color: #cbd5e1;
  padding: 12px 24px;
  border-radius: 12px;
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s ease;
  backdrop-filter: blur(10px);
}

.secondary-button:hover {
  background: rgba(15, 23, 42, 0.8);
  border-color: rgba(148, 163, 184, 0.5);
  color: #f1f5f9;
  transform: translateY(-1px);
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Scrollbar styling */
.calendar-create-view::-webkit-scrollbar {
  width: 8px;
}

.calendar-create-view::-webkit-scrollbar-track {
  background: rgba(15, 23, 42, 0.3);
}

.calendar-create-view::-webkit-scrollbar-thumb {
  background: rgba(59, 130, 246, 0.3);
  border-radius: 4px;
}

.calendar-create-view::-webkit-scrollbar-thumb:hover {
  background: rgba(59, 130, 246, 0.5);
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .calendar-create-view {
    padding: 0;
  }

  .create-view-header {
    padding: 20px 16px;
    flex-direction: column;
    align-items: flex-start;
  }

  .create-view-header h2 {
    font-size: 24px;
  }

  .weather-context,
  .existing-activities,
  .create-form {
    margin: 0 16px 16px;
  }

  .weather-context {
    flex-direction: column;
    text-align: center;
  }

  .weather-icon {
    margin: 0 auto;
  }

  .temp-row {
    justify-content: center;
  }

  .metric-row {
    flex-wrap: wrap;
    justify-content: center;
  }

  .existing-activities {
    padding: 16px;
  }

  .existing-activities ul {
    gap: 10px;
  }

  .existing-activities li {
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
  }

  .create-form {
    padding: 20px;
  }

  .advisor-metrics {
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
  }

  .advisor-metrics {
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
  }

  .form-footer {
    flex-direction: column-reverse;
  }

  .form-footer button {
    width: 100%;
  }
}
</style>


