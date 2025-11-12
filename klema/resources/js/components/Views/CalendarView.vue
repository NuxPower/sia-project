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
      <button class="add-activity-button" type="button" @click="openActivityModal()">
        <i class="fas fa-plus"></i>
        <span>Add New Activity</span>
      </button>
    </div>

    <div v-if="activityFeedback" class="activity-feedback" :class="activityFeedbackType">
      <i :class="feedbackIconMap[activityFeedbackType]"></i>
      <span>{{ activityFeedback }}</span>
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
            <span class="day-temp">{{ day.weather.temp !== null && day.weather.temp !== undefined ? `${day.weather.temp}°` : '—' }}</span>
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

    <transition name="modal-fade">
      <div v-if="showActivityModal" class="modal-backdrop" @click.self="closeActivityModal">
        <div class="modal-content">
          <div class="modal-header">
            <h3>Schedule New Activity</h3>
            <button type="button" class="modal-close" @click="closeActivityModal" aria-label="Close activity form">
              <i class="fas fa-times"></i>
            </button>
          </div>

          <form class="modal-body" @submit.prevent="submitActivity">
            <div v-if="formSubmitError" class="form-error-banner">
              <i class="fas fa-exclamation-circle"></i>
              <span>{{ formSubmitError }}</span>
            </div>

            <label class="form-field">
              <span>Activity Type</span>
              <input
                v-model="form.activity_type"
                type="text"
                placeholder="e.g. Planting, Irrigation"
                :class="{ invalid: formErrors.activity_type }"
                required
              />
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

            <div class="modal-footer">
              <button type="button" class="secondary-button" @click="closeActivityModal">Cancel</button>
              <button type="submit" class="primary-button" :disabled="isSubmittingActivity">
                <i v-if="isSubmittingActivity" class="fas fa-spinner fa-spin"></i>
                <span>{{ isSubmittingActivity ? 'Saving...' : 'Save Activity' }}</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
  getWeatherIcon: {
    type: Function,
    required: true
  },
  timeline: {
    type: Array,
    default: () => []
  }
});

const weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
const feedbackIconMap = {
  success: 'fas fa-check-circle',
  warning: 'fas fa-exclamation-triangle',
  error: 'fas fa-times-circle'
};

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

const form = ref(defaultFormState());

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
        const temperature = rawTemp !== null && rawTemp !== undefined ? Math.round(rawTemp) : null;

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

const fetchActivities = async () => {
  isLoadingActivities.value = true;
  activityError.value = '';
  try {
    const params = {
      month: currentDate.value.getMonth() + 1,
      year: currentDate.value.getFullYear()
    };
    const { data } = await axios.get('/api/activities', { params });
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
};

const openActivityModal = (dateKey = formatDateKey(currentDate.value)) => {
  resetForm(dateKey || formatDateKey(currentDate.value));
  showActivityModal.value = true;
};

const closeActivityModal = () => {
  showActivityModal.value = false;
};

const handleDayClick = (day) => {
  openActivityModal(day.fullDate);
};

onMounted(() => {
  resetForm(formatDateKey(new Date()));
  fetchActivities();
});

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
</script>

<style scoped>
.calendar-view {
  padding: 40px 40px 40px 160px;
  max-width: 1400px;
  margin: 0;
  height: 100%;
  overflow-y: auto;
  overflow-x: hidden;
  box-sizing: border-box;
  width: 100%;
  
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
  padding: 24px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  margin-bottom: 24px;
  width: 100%;
  box-sizing: border-box;
  overflow: hidden;
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
  grid-template-columns: repeat(7, 1fr);
  gap: 12px;
  width: 100%;
  box-sizing: border-box;
}

.calendar-day {
  aspect-ratio: 1;
  background: rgba(255, 255, 255, 0.05);
  border-radius: 12px;
  padding: 12px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  border: 1px solid rgba(255, 255, 255, 0.05);
  transition: all 0.3s ease;
  cursor: pointer;
  min-height: 100px;
  width: 100%;
  box-sizing: border-box;
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

@media (max-width: 1024px) {
  .calendar-view {
    padding: 30px 30px 30px 120px;
  }
}

@media (max-width: 768px) {
  .calendar-view {
    padding: 20px 15px 20px 15px;
    overflow-x: hidden;
  }
  
  .calendar-header {
    padding: 15px 20px;
    grid-template-columns: 1fr;
    gap: 12px;
    width: 100%;
    box-sizing: border-box;
  }
  
  .calendar-header h2 {
    font-size: 20px;
  }
  
  .calendar-nav {
    justify-content: space-between;
  }

  .add-activity-button {
    width: 100%;
    justify-content: center;
  }

  .nav-button {
    width: 40px;
    height: 40px;
    font-size: 14px;
  }
  
  .calendar-grid {
    padding: 12px;
    width: 100%;
    box-sizing: border-box;
  }
  
  .calendar-weekdays {
    gap: 6px;
    width: 100%;
  }
  
  .weekday {
    font-size: 11px;
    padding: 8px 2px;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  
  .calendar-days {
    gap: 4px;
    width: 100%;
  }
  
  .calendar-day {
    padding: 6px 4px;
    border-radius: 8px;
    min-height: 70px;
    width: 100%;
    aspect-ratio: 1;
  }
  
  .day-number {
    font-size: 11px;
  }
  
  .day-weather i {
    font-size: 16px;
  }
  
  .day-temp {
    font-size: 10px;
  }

  .activity-chip {
    flex-direction: column;
    align-items: flex-start;
    font-size: 10px;
    padding: 4px 6px;
  }

  .chip-field {
    margin-left: 0;
  }
  
  .legend {
    flex-direction: column;
    gap: 10px;
    padding: 15px;
    width: 100%;
    box-sizing: border-box;
  }
  
  .legend-item {
    font-size: 13px;
  }
}

@media (max-width: 480px) {
  .calendar-view {
    padding: 15px 8px;
    overflow-x: hidden;
  }
  
  .calendar-header {
    padding: 12px 15px;
    gap: 10px;
    grid-template-columns: 1fr;
    width: 100%;
    box-sizing: border-box;
  }
  
  .calendar-header h2 {
    font-size: 18px;
  }
  
  .calendar-nav {
    justify-content: space-between;
  }

  .add-activity-button {
    font-size: 13px;
    padding: 10px;
  }
  
  .nav-button {
    width: 36px;
    height: 36px;
    font-size: 13px;
  }
  
  .calendar-grid {
    padding: 10px 8px;
    width: 100%;
    box-sizing: border-box;
  }
  
  .calendar-weekdays {
    gap: 4px;
  }
  
  .weekday {
    font-size: 9px;
    padding: 6px 1px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  
  .calendar-days {
    gap: 3px;
    width: 100%;
  }
  
  .calendar-day {
    padding: 4px 2px;
    min-height: 60px;
    width: 100%;
  }
  
  .day-number {
    font-size: 10px;
  }
  
  .day-weather i {
    font-size: 14px;
  }
  
  .day-temp {
    font-size: 9px;
  }

  .activity-chip {
    font-size: 9px;
    padding: 3px 4px;
  }

  .activity-feedback {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>