<template>
  <div class="day-detail-view">
    <header class="detail-header">
      <button class="back-button" @click="$emit('close')">
        <i class="fas fa-arrow-left"></i>
        Back
      </button>

      <div class="header-info">
        <span class="location">{{ locationLabel || 'Selected Location' }}</span>
        <h1>{{ dayLabel }}</h1>
        <p class="date">{{ formattedDate }}</p>
      </div>

      <div class="summary-cards">
        <div class="summary-card">
          <span class="label">Condition</span>
          <span class="value">
            <i :class="weatherIcon"></i>
            {{ detail?.description || detail?.condition || 'Unknown' }}
          </span>
        </div>
        <div class="summary-card">
          <span class="label">High / Low</span>
          <span class="value">{{ detail?.temp_max ?? '—' }}° / {{ detail?.temp_min ?? '—' }}°</span>
        </div>
        <div class="summary-card">
          <span class="label">Precipitation Chance</span>
          <span class="value">{{ precipitationChance }}</span>
        </div>
      </div>
    </header>

    <div class="detail-content">
      <section class="sunrise-section detail-panel">
        <h2>Sunlight Window</h2>
        <div v-if="sunriseTime && sunsetTime" class="sun-cycle">
          <div class="sun-cycle-track">
            <span class="sunrise-time">{{ sunriseTime }}</span>
            <div class="sun-path">
              <div class="sun-indicator"></div>
            </div>
            <span class="sunset-time">{{ sunsetTime }}</span>
          </div>
          <p class="sun-duration">Daylight for approximately {{ daylightDuration }}</p>
        </div>
        <p v-else class="missing-data">
          Sunrise and sunset data were not provided for this day.
        </p>
      </section>

      <section class="precip-section detail-panel">
        <h2>Rain Window</h2>
        <div v-if="hourlySeries.length" class="precip-chart">
          <div class="precip-hour" v-for="hour in hourlySeries" :key="hour.time">
            <div class="bar-wrapper">
              <div
                class="precip-bar"
                :style="{ height: `${hour.precipIntensity}%` }"
                :title="`${hour.displayTime} • ${hour.precipitation ?? 0}mm`"
              ></div>
            </div>
            <span class="precip-label">{{ hour.timeLabel }}</span>
          </div>
        </div>
        <p v-else class="missing-data">
          No hourly precipitation data available for this day.
        </p>
      </section>

      <section class="temperature-section detail-panel">
        <h2>Temperature Trend</h2>
        <div v-if="hourlySeries.length" class="temperature-table">
          <div class="table-row table-header">
            <span>Time</span>
            <span>Temp</span>
            <span>Feels Like</span>
            <span>Cloud Cover</span>
            <span>Wind</span>
          </div>
          <div class="table-row" v-for="hour in hourlySeries" :key="`${hour.time}-temp`">
            <span>{{ hour.displayTime }}</span>
            <span>{{ hour.temperature ?? '—' }}°</span>
            <span>{{ hour.feelsLike ?? '—' }}°</span>
            <span>{{ hour.clouds ?? 0 }}%</span>
            <span>{{ hour.wind ?? '—' }}</span>
          </div>
        </div>
        <p v-else class="missing-data">
          Hourly temperature data is not available for this day.
        </p>
      </section>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useWeatherUtils } from '../../composables/useWeatherUtils';

const props = defineProps({
  day: {
    type: Object,
    required: true
  },
  detail: {
    type: Object,
    default: () => ({})
  },
  hourlyData: {
    type: Array,
    default: () => []
  },
  locationLabel: {
    type: String,
    default: ''
  }
});

defineEmits(['close']);

const { getDayLabel, getWeatherIcon } = useWeatherUtils();

const dayLabel = computed(() => getDayLabel(props.day));

const formattedDate = computed(() => {
  if (!props.day?.date) return '';
  const [year, month, day] = props.day.date.split('-').map(Number);
  return new Date(year, month - 1, day).toLocaleDateString('en-PH', {
    weekday: 'long',
    month: 'long',
    day: 'numeric',
    year: 'numeric'
  });
});

const weatherIcon = computed(() => {
  const condition =
    props.detail?.condition ??
    props.detail?.weather?.[0]?.main ??
    props.day?.condition;
  const iconCode =
    props.detail?.icon ??
    props.detail?.weather?.[0]?.icon ??
    props.day?.icon;

  return getWeatherIcon({ condition, icon: iconCode });
});

const precipitationChance = computed(() => {
  const val =
    props.detail?.precip_probability ??
    props.detail?.pop ??
    props.detail?.precipitationProbability;

  if (val === undefined || val === null) {
    return '—';
  }

  const normalized = val > 1 ? val : val * 100;
  return `${Math.round(normalized)}%`;
});

const unixToTime = (value) => {
  if (!value) return null;
  const offset = props.detail?.timezone_offset ?? 0;
  const adjusted = new Date((value + offset) * 1000);
  return adjusted.toLocaleTimeString('en-PH', {
    hour: '2-digit',
    minute: '2-digit'
  });
};

const sunriseTime = computed(() => {
  return unixToTime(
    props.detail?.sunrise ??
    props.detail?.sys?.sunrise ??
    props.detail?.astro?.sunrise
  );
});

const sunsetTime = computed(() => {
  return unixToTime(
    props.detail?.sunset ??
    props.detail?.sys?.sunset ??
    props.detail?.astro?.sunset
  );
});

const daylightDuration = computed(() => {
  if (!sunriseTime.value || !sunsetTime.value) return '';
  const offset = props.detail?.timezone_offset ?? 0;
  const [sunriseDate, sunsetDate] = [props.detail?.sunrise, props.detail?.sunset]
    .map(ts => ts ? new Date((ts + offset) * 1000) : null);

  if (!sunriseDate || !sunsetDate) return '';

  const diffMs = sunsetDate - sunriseDate;
  const hours = Math.floor(diffMs / (1000 * 60 * 60));
  const minutes = Math.floor((diffMs / (1000 * 60)) % 60);
  return `${hours}h ${minutes}m`;
});

const parseHourlyData = (entry) => {
  if (!entry) return [];

  const source = entry.hourly || entry.hours || entry.data || [];
  const timezoneOffset = entry.timezone_offset ?? 0;
  if (!Array.isArray(source)) return [];

  return source
    .map((point) => {
      const timestamp = point.dt || point.time || point.timestamp;
      const timeDate = timestamp ? new Date(timestamp * 1000) : null;

      const adjustedTimestamp = timestamp ? timestamp + timezoneOffset : null;
      const adjustedDate = adjustedTimestamp ? new Date(adjustedTimestamp * 1000) : null;

      const displayTime = adjustedDate
        ? adjustedDate.toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit' })
        : point.label || point.time || '';

      const hourLabel = adjustedDate
        ? adjustedDate.toLocaleTimeString('en-PH', { hour: 'numeric' })
        : point.label || point.time || '';

      const precipitation = point.precipitation ?? point.rain ?? point.rain?.['1h'];
      const precipitationMm = typeof precipitation === 'number'
        ? precipitation
        : precipitation?.value ?? precipitation?.amount ?? 0;

      const precipIntensity = Math.min(100, Math.round((precipitationMm || 0) * 20));

      const windSpeed = point.wind_speed ?? point.wind?.speed;
      const windUnit = windSpeed && windSpeed > 60 ? 'km/h' : 'm/s';
      const windDisplay = windSpeed
        ? `${Math.round(windSpeed * (windUnit === 'km/h' ? 3.6 : 1))} ${windUnit}`
        : '—';

      return {
        time: adjustedTimestamp || timestamp || point.time || point.label || Math.random(),
        displayTime,
        timeLabel: hourLabel,
        precipitation: precipitationMm ? precipitationMm.toFixed(1) : null,
        precipIntensity,
        temperature: point.temp ?? point.temperature ?? null,
        feelsLike: point.feels_like ?? point.feelsLike ?? null,
        clouds: point.clouds ?? point.cloud_cover ?? point.cloudCover ?? null,
        wind: windDisplay
      };
    })
    .filter(Boolean);
};

const hourlySeries = computed(() => {
  if (props.hourlyData.length) {
    return parseHourlyData({
      hourly: props.hourlyData,
      timezone_offset: props.detail?.timezone_offset ?? 0
    });
  }

  return parseHourlyData(props.detail);
});
</script>

<style scoped>
.day-detail-view {
  position: fixed;
  inset: 0;
  background: linear-gradient(145deg, #0f172a, #111827);
  color: #f9fafb;
  display: flex;
  flex-direction: column;
  z-index: 5000;
  overflow: hidden;
  pointer-events: auto;
}

.detail-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 28px 40px;
  border-bottom: 1px solid rgba(148, 163, 184, 0.2);
  background: rgba(15, 23, 42, 0.8);
  backdrop-filter: blur(10px);
}

.back-button {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  background: rgba(59, 130, 246, 0.2);
  border: 1px solid rgba(59, 130, 246, 0.4);
  color: #e0f2fe;
  padding: 10px 18px;
  border-radius: 10px;
  cursor: pointer;
  transition: all 0.3s ease;
  font-weight: 600;
}

.back-button:hover {
  background: rgba(59, 130, 246, 0.35);
  transform: translateX(-2px);
}

.header-info {
  text-align: center;
  max-width: 420px;
}

.header-info h1 {
  margin: 4px 0;
  font-size: 28px;
  font-weight: 700;
}

.header-info .date {
  color: #94a3b8;
  font-size: 14px;
}

.header-info .location {
  letter-spacing: 0.2em;
  font-size: 11px;
  color: #60a5fa;
}

.summary-cards {
  display: flex;
  gap: 16px;
}

.summary-card {
  background: rgba(15, 118, 110, 0.15);
  border: 1px solid rgba(45, 212, 191, 0.3);
  padding: 16px 20px;
  border-radius: 14px;
  display: flex;
  flex-direction: column;
  min-width: 160px;
  backdrop-filter: blur(6px);
}

.summary-card .label {
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: 0.15em;
  color: #67e8f9;
  margin-bottom: 6px;
}

.summary-card .value {
  font-size: 18px;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 10px;
}

.detail-content {
  flex: 1;
  overflow-y: auto;
  padding: 32px 40px 40px;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
  gap: 24px;
}

.detail-panel {
  background: rgba(15, 23, 42, 0.7);
  border: 1px solid rgba(148, 163, 184, 0.25);
  border-radius: 20px;
  padding: 24px;
  box-shadow: 0 25px 35px -20px rgba(15, 23, 42, 0.8);
  backdrop-filter: blur(12px);
}

.detail-panel h2 {
  margin: 0 0 18px;
  font-size: 18px;
  font-weight: 700;
  color: #bae6fd;
}

.sun-cycle {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.sun-cycle-track {
  display: flex;
  align-items: center;
  gap: 16px;
}

.sun-path {
  flex: 1;
  height: 12px;
  border-radius: 999px;
  background: linear-gradient(90deg, rgba(59, 130, 246, 0.3), rgba(234, 179, 8, 0.5));
  position: relative;
}

.sun-indicator {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 18px;
  height: 18px;
  border-radius: 50%;
  background: radial-gradient(circle, #fbbf24 0%, #f97316 70%);
  box-shadow: 0 0 18px rgba(251, 191, 36, 0.75);
}

.sunrise-time,
.sunset-time {
  font-weight: 600;
  font-size: 14px;
  color: #fef08a;
}

.sun-duration {
  margin: 0;
  font-size: 13px;
  color: #94a3b8;
}

.precip-chart {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(40px, 1fr));
  align-items: end;
  gap: 12px;
  padding: 12px 8px 0;
  min-height: 160px;
}

.precip-hour {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
}

.bar-wrapper {
  width: 16px;
  height: 120px;
  background: rgba(59, 130, 246, 0.1);
  border-radius: 10px;
  display: flex;
  align-items: flex-end;
  overflow: hidden;
  border: 1px solid rgba(96, 165, 250, 0.2);
}

.precip-bar {
  width: 100%;
  background: linear-gradient(180deg, rgba(59, 130, 246, 0.9), rgba(96, 165, 250, 0.4));
  border-radius: 10px;
  transition: height 0.3s ease;
}

.precip-label {
  font-size: 11px;
  color: #d1d5db;
}

.temperature-table {
  display: flex;
  flex-direction: column;
  border: 1px solid rgba(59, 130, 246, 0.25);
  border-radius: 14px;
  overflow: hidden;
  background: rgba(30, 41, 59, 0.6);
}

.table-row {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  padding: 12px 16px;
  font-size: 13px;
  color: #e2e8f0;
}

.table-header {
  background: rgba(59, 130, 246, 0.15);
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  font-size: 11px;
}

.table-row:nth-child(even) {
  background: rgba(15, 23, 42, 0.4);
}

.missing-data {
  margin: 0;
  font-size: 13px;
  color: #94a3b8;
  font-style: italic;
}

@media (max-width: 1024px) {
  .detail-header {
    flex-direction: column;
    gap: 20px;
    padding: 24px;
  }

  .summary-cards {
    width: 100%;
    justify-content: center;
    flex-wrap: wrap;
  }

  .detail-content {
    padding: 24px;
  }

  .temperature-table .table-row {
    grid-template-columns: repeat(3, minmax(100px, 1fr));
    row-gap: 8px;
  }

  .temperature-table .table-row span:nth-child(4),
  .temperature-table .table-row span:nth-child(5) {
    display: none;
  }
}

@media (max-width: 640px) {
  .detail-content {
    display: flex;
    flex-direction: column;
  }

  .summary-card {
    min-width: 140px;
    padding: 12px 16px;
  }
}
</style>

