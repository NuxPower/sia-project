<template>
  <div class="day-detail-view">
    <header class="detail-header">
      <div class="header-top">
        <button class="back-button" @click="$emit('close')">
          <i class="fas fa-arrow-left"></i>
          Back
        </button>

        <div class="header-info">
          <span class="location">{{ locationLabel || 'Selected Location' }}</span>
          <h1>{{ dayLabel }}</h1>
          <p class="date">{{ formattedDate }}</p>
        </div>
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
          <span class="label">Humidity</span>
          <span class="value">{{ humiditySummary }}</span>
        </div>
        <div class="summary-card">
          <span class="label">Wind</span>
          <span class="value">{{ windSummary }}</span>
        </div>
        <div class="summary-card">
          <span class="label">Precipitation Chance</span>
          <span class="value">{{ precipitationChance }}</span>
        </div>
        <div class="summary-card">
          <span class="label">Expected Rain</span>
          <span class="value">{{ precipitationTotal }}</span>
        </div>
      </div>
    </header>

    <div class="detail-content">
      <section class="sunrise-section detail-panel">
        <h2>Sunlight Window</h2>
        <p class="panel-description">{{ sunlightExplanation }}</p>
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
        <p class="panel-description">{{ precipitationExplanation }}</p>
        <div v-if="hourlySeries.length" class="precip-chart">
          <div class="precip-hour" v-for="hour in hourlySeries" :key="hour.time">
            <div class="bar-wrapper">
              <div
                class="precip-bar"
                :style="{ height: `${hour.precipIntensity}%` }"
                :title="precipitationTooltip(hour)"
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
        <p class="panel-description">{{ temperatureExplanation }}</p>
        <div v-if="hourlySeries.length" class="temperature-table">
          <div class="table-row table-header">
            <span>Time</span>
            <span>Temp</span>
            <span>Feels Like</span>
            <span>Humidity</span>
            <span>Cloud Cover</span>
            <span>Wind</span>
          </div>
          <div class="table-row" v-for="hour in hourlySeries" :key="`${hour.time}-temp`">
            <span>{{ hour.displayTime }}</span>
            <span>{{ hour.temperature ?? '—' }}°</span>
            <span>{{ hour.feelsLike ?? '—' }}°</span>
            <span>{{ hour.humidity ?? '—' }}%</span>
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

const isFiniteNumber = (value) => typeof value === 'number' && Number.isFinite(value);

const pickNumber = (...values) => {
  for (const value of values) {
    if (isFiniteNumber(value)) {
      return value;
    }
  }
  return null;
};

// Helper function to format time in local timezone using offset
// timestamp: UTC Unix timestamp in seconds
// offset: timezone offset in seconds (positive = ahead of UTC, negative = behind UTC)
const formatLocalTime = (timestamp, offset, options = {}) => {
  if (timestamp === null || offset === null) return '';
  
  // Convert UTC timestamp to local time by adding the offset
  // Then create a Date object treating it as UTC (since we've already adjusted)
  const localTimestamp = timestamp + offset;
  const localDate = new Date(localTimestamp * 1000);
  
  // Format the date as if it were UTC (because we've already adjusted for offset)
  const formatOptions = {
    hour: 'numeric',
    hour12: true,
    timeZone: 'UTC',
    ...options
  };
  
  return localDate.toLocaleTimeString('en-US', formatOptions);
};

const resolvePrecipitationAmount = (point) => {
  const candidates = [
    point?.precipitation,
    point?.precipitation_mm,
    point?.precipitationAmount,
    point?.rain,
    point?.rain?.value,
    point?.rain?.amount,
    point?.rain?.mm,
    point?.rain?.['1h'],
    point?.rain?.['3h'],
    point?.snow,
    point?.snow?.value,
    point?.snow?.amount,
    point?.snow?.mm,
    point?.snow?.['1h'],
    point?.snow?.['3h'],
    point?.qpf,
    point?.qpf?.value,
    point?.qpf?.amount,
    point?.qpf?.mm
  ];

  for (const candidate of candidates) {
    if (isFiniteNumber(candidate)) {
      return candidate;
    }

    if (candidate && typeof candidate === 'object') {
      const nested = pickNumber(
        candidate?.value,
        candidate?.amount,
        candidate?.mm,
        candidate?.['1h'],
        candidate?.['3h']
      );
      if (nested !== null) {
        return nested;
      }
    }
  }

  return null;
};

const degreeToCompass = (degree) => {
  if (!isFiniteNumber(degree)) return null;
  const directions = ['N', 'NNE', 'NE', 'ENE', 'E', 'ESE', 'SE', 'SSE',
    'S', 'SSW', 'SW', 'WSW', 'W', 'WNW', 'NW', 'NNW'];
  const index = Math.round(degree / 22.5) % 16;
  return directions[index];
};

const formatWindSpeed = (speed, direction) => {
  if (!isFiniteNumber(speed)) return '—';
  const speedKmh = Math.round(speed * 3.6);
  const cardinal = degreeToCompass(direction);
  return `${speedKmh} km/h${cardinal ? ` ${cardinal}` : ''}`;
};

const resolveTimezoneOffset = (entry) => {
  return pickNumber(
    entry?.timezone_offset,
    props.detail?.timezone_offset,
    props.day?.timezone_offset,
    0
  );
};

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

const timezoneOffsetSeconds = computed(() => {
  const offset = pickNumber(
    props.detail?.timezone_offset,
    props.day?.timezone_offset
  );
  return offset ?? 0;
});

const resolveSolarTimestamp = (source) => {
  const timestamp = pickNumber(
    source,
    source && Number(source)
  );
  return Number.isFinite(timestamp) ? timestamp : null;
};

const rawSunriseTimestamp = computed(() => {
  return resolveSolarTimestamp(
    props.detail?.sunrise ??
    props.detail?.sys?.sunrise ??
    props.detail?.astro?.sunrise
  );
});

const rawSunsetTimestamp = computed(() => {
  return resolveSolarTimestamp(
    props.detail?.sunset ??
    props.detail?.sys?.sunset ??
    props.detail?.astro?.sunset
  );
});

const sunriseDate = computed(() => {
  const timestamp = rawSunriseTimestamp.value;
  if (timestamp === null || timestamp === undefined) return null;
  // Create Date object from UTC timestamp (don't adjust here)
  return new Date(timestamp * 1000);
});

const sunsetDate = computed(() => {
  const timestamp = rawSunsetTimestamp.value;
  if (timestamp === null || timestamp === undefined) return null;
  // Create Date object from UTC timestamp (don't adjust here)
  return new Date(timestamp * 1000);
});

const normalizedSunsetDate = computed(() => {
  const sunrise = sunriseDate.value;
  const sunset = sunsetDate.value;
  if (!sunrise || !sunset) return sunset;
  // If sunset appears to be before sunrise, it's likely on the next day
  if (sunset <= sunrise) {
    return new Date(sunset.getTime() + 24 * 60 * 60 * 1000);
  }
  return sunset;
});

const sunriseTime = computed(() => {
  const timestamp = rawSunriseTimestamp.value;
  if (timestamp === null || timestamp === undefined) return null;
  
  // Format the UTC timestamp using the timezone offset
  const timezoneOffset = timezoneOffsetSeconds.value;
  return formatLocalTime(timestamp, timezoneOffset, {
    hour: '2-digit',
    minute: '2-digit'
  });
});

const sunsetTime = computed(() => {
  const normalized = normalizedSunsetDate.value;
  if (!normalized) return null;
  
  // Convert the normalized Date object back to UTC timestamp (seconds)
  // The Date object represents UTC time, so getTime() gives us UTC milliseconds
  const utcTimestamp = Math.floor(normalized.getTime() / 1000);
  
  // Format the UTC timestamp using the timezone offset
  const timezoneOffset = timezoneOffsetSeconds.value;
  return formatLocalTime(utcTimestamp, timezoneOffset, {
    hour: '2-digit',
    minute: '2-digit'
  });
});

const daylightDuration = computed(() => {
  const sunrise = sunriseDate.value;
  const sunset = normalizedSunsetDate.value;

  if (!sunrise || !sunset) return '';

  const diffMs = sunset.getTime() - sunrise.getTime();
  if (diffMs <= 0) return '';
  const hours = Math.floor(diffMs / (1000 * 60 * 60));
  const minutes = Math.floor((diffMs / (1000 * 60)) % 60);
  return `${hours}h ${minutes}m`;
});

const parseHourlyData = (entry, selectedDate = null) => {
  if (!entry) return [];

  const source = entry.hourly || entry.hours || entry.data || [];
  const timezoneOffset = resolveTimezoneOffset(entry);
  if (!Array.isArray(source)) return [];

  // Note: We'll filter by comparing local calendar days instead of UTC timestamp ranges
  // This is more reliable and handles timezone boundaries correctly

  return source
    .map((point, index) => {
      if (!point) return null;

      const timestamp = pickNumber(point.dt, point.timestamp, point.time);
      if (timestamp === null) return null;

      // Create Date object from UTC timestamp (Unix timestamp in seconds)
      // Weather API timestamps are in UTC
      const utcDate = new Date(timestamp * 1000);
      
      // Filter to selected day if provided
      // Convert UTC timestamp to local time components to determine which calendar day it belongs to
      if (selectedDate) {
        const dateStr = typeof selectedDate === 'string' ? selectedDate : selectedDate.date;
        if (dateStr) {
          const [selectedYear, selectedMonth, selectedDay] = dateStr.split('-').map(Number);
          
          // Convert UTC timestamp to local time by adding the offset
          // Then create a Date object - since we've adjusted for offset, we can use UTC methods
          // to get the local calendar day components
          const localTimestampSeconds = timestamp + timezoneOffset;
          const localDate = new Date(localTimestampSeconds * 1000);
          
          // Extract calendar day components from the local time
          // Using UTC methods because the Date object is already adjusted for local time
          const localYear = localDate.getUTCFullYear();
          const localMonth = localDate.getUTCMonth() + 1; // getUTCMonth() returns 0-11
          const localDay = localDate.getUTCDate();
          
          // Check if this hour belongs to the selected calendar day in local timezone
          if (localYear !== selectedYear || localMonth !== selectedMonth || localDay !== selectedDay) {
            return null; // Skip this hour if it's not in the selected day
          }
        }
      }

      // Format time in local timezone using the timezone offset
      // The timestamp is in UTC, and we have the timezone offset
      const displayTime = formatLocalTime(timestamp, timezoneOffset, {
        hour: '2-digit',
        minute: '2-digit'
      });

      // Format hour label with AM/PM (just the hour)
      const hourLabel = formatLocalTime(timestamp, timezoneOffset, {
        hour: 'numeric'
      });

      const temperature = pickNumber(point.temp, point.temperature, point.main?.temp, point.details?.temperature);
      const feelsLike = pickNumber(point.feels_like, point.main?.feels_like);
      const clouds = pickNumber(point.clouds?.all, point.clouds, point.cloud_cover, point.cloudCover);
      const humidity = pickNumber(point.humidity, point.main?.humidity);
      const pressure = pickNumber(point.pressure, point.main?.pressure);

      const windSpeed = pickNumber(point.wind_speed, point.wind?.speed);
      const windGust = pickNumber(point.wind_gust, point.wind?.gust);
      const windDeg = pickNumber(point.wind_deg, point.wind?.deg);
      const windDisplay = formatWindSpeed(windSpeed, windDeg);

      const precipitationAmount = resolvePrecipitationAmount(point);
      const precipitationMm = precipitationAmount !== null ? Number(precipitationAmount) : null;
      const precipitationDisplay = precipitationAmount !== null
        ? Number(precipitationAmount).toFixed(1)
        : null;

      const rawPop = point.pop ?? point.precip_probability ?? point.precipitationProbability;
      const precipitationProbability = isFiniteNumber(rawPop)
        ? Math.round((rawPop <= 1 ? rawPop * 100 : rawPop))
        : null;

      const precipIntensity = precipitationAmount !== null
        ? Math.min(100, Math.max(0, Math.round(precipitationAmount * 20)))
        : (precipitationProbability ?? 0);

      return {
        time: timestamp,
        timestamp,
        utcDate,
        displayTime,
        timeLabel: hourLabel,
        precipitationDisplay,
        precipitationMm,
        precipitationProbability,
        precipIntensity,
        temperature: isFiniteNumber(temperature) ? Math.round(temperature) : null,
        feelsLike: isFiniteNumber(feelsLike) ? Math.round(feelsLike) : null,
        clouds: isFiniteNumber(clouds) ? Math.round(clouds) : null,
        humidity: isFiniteNumber(humidity) ? Math.round(humidity) : null,
        pressure: isFiniteNumber(pressure) ? Math.round(pressure) : null,
        windSpeed,
        windGust,
        windDeg,
        windCardinal: degreeToCompass(windDeg),
        wind: windDisplay
      };
    })
    .filter(Boolean)
    .sort((a, b) => {
      // Sort by timestamp to ensure correct chronological order
      if (a.timestamp && b.timestamp) {
        return a.timestamp - b.timestamp;
      }
      return 0;
    });
};

const hourlySeries = computed(() => {
  // Pass the selected day's date to filter hourly data to that day only
  const selectedDate = props.day?.date || props.detail?.date;
  
  if (props.hourlyData.length) {
    return parseHourlyData({
      hourly: props.hourlyData,
      timezone_offset: props.detail?.timezone_offset ?? 0
    }, selectedDate ? { date: selectedDate } : null);
  }

  return parseHourlyData(props.detail, selectedDate ? { date: selectedDate } : null);
});

const detailStatistics = computed(() => {
  const hours = hourlySeries.value;
  if (!hours.length) {
    return {
      averageHumidity: null,
      peakWind: null,
      dominantWind: null,
      totalPrecipitation: null,
      peakPrecipProbability: null,
      averagePrecipProbability: null
    };
  }

  const humidityValues = hours.map((entry) => entry.humidity).filter(isFiniteNumber);
  const averageHumidity = humidityValues.length
    ? humidityValues.reduce((sum, value) => sum + value, 0) / humidityValues.length
    : null;

  const windValues = hours.map((entry) => entry.windSpeed).filter(isFiniteNumber);
  const peakWind = windValues.length ? Math.max(...windValues) : null;

  const windDirections = hours
    .map((entry) => entry.windCardinal)
    .filter((value) => typeof value === 'string' && value.length);

  const dominantWind = windDirections.length
    ? [...windDirections.reduce((map, dir) => map.set(dir, (map.get(dir) ?? 0) + 1), new Map()).entries()]
        .sort((a, b) => b[1] - a[1])[0][0]
    : null;

  const precipitationValues = hours.map((entry) => entry.precipitationMm).filter(isFiniteNumber);
  const totalPrecipitation = precipitationValues.length
    ? Number(precipitationValues.reduce((sum, value) => sum + value, 0).toFixed(1))
    : null;

  const probabilityValues = hours
    .map((entry) => entry.precipitationProbability)
    .filter(isFiniteNumber);

  const peakPrecipProbability = probabilityValues.length ? Math.max(...probabilityValues) : null;
  const averagePrecipProbability = probabilityValues.length
    ? Math.round(probabilityValues.reduce((sum, value) => sum + value, 0) / probabilityValues.length)
    : null;

  return {
    averageHumidity,
    peakWind,
    dominantWind,
    totalPrecipitation,
    peakPrecipProbability,
    averagePrecipProbability
  };
});

const precipitationChance = computed(() => {
  const rawValue = [
    props.detail?.precip_probability,
    props.detail?.pop,
    props.detail?.precipitationProbability
  ].find((value) => value !== undefined && value !== null);

  const fallbackProbability =
    detailStatistics.value.peakPrecipProbability ?? detailStatistics.value.averagePrecipProbability;

  const value = rawValue ?? fallbackProbability;
  if (value === undefined || value === null) {
    return '—';
  }

  const normalized = value <= 1 ? value * 100 : value;
  return `${Math.round(normalized)}%`;
});

const humiditySummary = computed(() => {
  const humidity = pickNumber(
    props.detail?.humidity,
    props.detail?.main?.humidity,
    detailStatistics.value.averageHumidity
  );

  if (!isFiniteNumber(humidity)) {
    return '—';
  }

  return `${Math.round(humidity)}%`;
});

const windSummary = computed(() => {
  const windSpeed = pickNumber(
    props.detail?.wind_speed,
    props.detail?.wind?.speed,
    detailStatistics.value.peakWind
  );

  if (!isFiniteNumber(windSpeed)) {
    return '—';
  }

  const windDirection =
    degreeToCompass(pickNumber(props.detail?.wind_deg, props.detail?.wind?.deg)) ??
    detailStatistics.value.dominantWind;

  const speedKmh = Math.round(windSpeed * 3.6);
  return `${speedKmh} km/h${windDirection ? ` ${windDirection}` : ''}`;
});

const precipitationTotal = computed(() => {
  const total = detailStatistics.value.totalPrecipitation;
  if (!isFiniteNumber(total)) {
    return '—';
  }

  return `${total.toFixed(1)} mm`;
});

const sunlightExplanation = computed(() => {
  if (sunriseTime.value && sunsetTime.value) {
    return `Sunrise is around ${sunriseTime.value} and sunset about ${sunsetTime.value}, giving roughly ${daylightDuration.value || '—'} of daylight.`;
  }
  return 'Sunrise and sunset times will appear here when provided by the forecast data.';
});

const precipitationExplanation = computed(() => {
  if (!hourlySeries.value.length) {
    return 'Hourly precipitation data is currently unavailable for this day.';
  }

  const chance = precipitationChance.value;
  const total = precipitationTotal.value;
  const pieces = [];

  if (chance !== '—') {
    pieces.push(`Chance of precipitation is about ${chance}.`);
  }

  if (total !== '—') {
    pieces.push(`Total expected accumulation is roughly ${total}.`);
  }

  pieces.push('Each bar represents one hour; hover for the exact amount and probability.');

  return pieces.join(' ');
});

const temperatureExplanation = computed(() => {
  if (!hourlySeries.value.length) {
    return 'Hourly temperature data is not available for this day.';
  }

  return 'Rows show hourly readings: air temperature, feels-like value, humidity, cloud cover, and wind speed/direction.';
});

const precipitationTooltip = (hour) => {
  if (!hour) return '';
  const hasAmount = hour.precipitationDisplay !== null && hour.precipitationDisplay !== undefined;
  const amountText = hasAmount ? `${hour.precipitationDisplay}mm` : '—';
  const hasProbability = isFiniteNumber(hour.precipitationProbability);
  const probabilityText = hasProbability ? ` • ${hour.precipitationProbability}% chance` : '';
  return `${hour.displayTime} • ${amountText}${probabilityText}`;
};
</script>

<style scoped>
.day-detail-view {
  position: fixed;
  inset: 0;
  background: linear-gradient(145deg, #0a0f1e, #0f172a, #111827);
  color: #f9fafb;
  display: flex;
  flex-direction: column;
  z-index: 5000;
  overflow: hidden;
  pointer-events: auto;
  animation: fadeIn 0.4s ease-out;
}
.detail-header {
  display: flex;
  flex-direction: column;
  gap: 20px;
  padding: 24px 36px 20px;
  border-bottom: 1px solid rgba(59, 130, 246, 0.15);
  background: linear-gradient(135deg, rgba(15, 23, 42, 0.92), rgba(30, 41, 59, 0.82));
  backdrop-filter: blur(16px);
  box-shadow: 0 3px 18px rgba(0, 0, 0, 0.35);
  animation: slideDown 0.5s ease-out;
  width: 100%;
}

.header-top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 16px 24px;
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
  align-self: flex-start;
  width: auto;
}

.back-button:hover {
  background: rgba(59, 130, 246, 0.35);
  transform: translateX(-2px);
}

.header-info {
  text-align: left;
  max-width: 420px;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.header-info h1 {
  margin: 0;
  font-size: 26px;
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
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 16px;
  width: 100%;
}

.summary-card {
  background: rgba(15, 118, 110, 0.15);
  border: 1px solid rgba(45, 212, 191, 0.3);
  padding: 14px 16px;
  border-radius: 14px;
  display: flex;
  flex-direction: column;
  min-width: 0;
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
  display: flex;
  flex-direction: column;
  gap: 24px;
  width: 100%;
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

.panel-description {
  margin: -10px 0 20px;
  font-size: 13px;
  color: #cbd5f5;
  line-height: 1.4;
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
  grid-template-columns: repeat(6, 1fr);
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
@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

@keyframes slideDown {
  from {
    transform: translateY(-20px);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

@media (max-width: 1024px) {
  .detail-header {
    flex-direction: column;
    padding: 24px;
  }

  .header-top {
    flex-direction: column;
    align-items: flex-start;
    gap: 16px;
  }

  .summary-cards {
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
  }

  .detail-content {
    padding: 24px;
  }

  .temperature-table .table-row {
    grid-template-columns: repeat(3, minmax(100px, 1fr));
    row-gap: 8px;
  }

  .temperature-table .table-row span:nth-child(n + 4) {
    display: none;
  }
}

@media (max-width: 640px) {
  .detail-content {
    display: flex;
    flex-direction: column;
  }

  .summary-card {
    padding: 12px 14px;
  }
}
</style>

