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
          <span class="temp-value">{{ Math.round(currentWeather.main.temp) }}°C</span>
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
            <span>{{ currentWeather.wind.speed }} m/s</span>
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
          v-for="(day, index) in forecast" 
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
            <span class="temp-max">{{ day.temp_max }}°</span>
            <span class="temp-min">{{ day.temp_min }}°</span>
          </div>
          <div class="forecast-condition">{{ day.condition }}</div>
        </div>
      </div>
    </div>

    <FarmManagementPanel
      class="dashboard-farm-panel"
      :farms="farms"
      :loading="farmsLoading"
      :is-drawing="isDrawing"
      :is-placing-point="isPlacingPoint"
      :soil-types="soilTypes"
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
  }
});

const emit = defineEmits([
  'refresh-farms',
  'create-farm',
  'save-farm',
  'clear-boundary',
  'start-boundary',
  'finish-boundary',
  'cancel-boundary',
  'start-point',
  'cancel-point'
]);

const farms = computed(() => props.farms ?? []);

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

const initVanta = () => {
  if (typeof window === 'undefined' || !weatherCard.value) {
    return;
  }

  destroyVanta();

  const condition = props.currentWeather?.weather?.[0]?.main ?? '';
  const effectType = selectVantaEffect(condition);

  try {
    vantaEffect = effectType({
      el: weatherCard.value,
      THREE,
      mouseControls: false,
      touchControls: false,
      gyroControls: false,
      minHeight: 200.0,
      minWidth: 200.0,
      scale: 1.0,
      scaleMobile: 1.0,
      color: 0x0077ff,
      backgroundAlpha: 0.0
    });

    isVantaActive.value = true;
  } catch {
    destroyVanta();
  }
};

const scheduleVantaInit = () => {
  if (!weatherCard.value) {
    return;
  }

  nextTick(() => {
    initVanta();
  });
};

onMounted(() => {
  scheduleVantaInit();
});

onBeforeUnmount(() => {
  destroyVanta();
});

watch(
  () => props.currentWeather?.weather?.[0]?.main,
  () => {
    scheduleVantaInit();
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
  padding: 30px;
  margin-bottom: 30px;
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
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 40px;
}

.temperature-display {
  flex: 1;
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
  grid-template-columns: repeat(2, 1fr);
  gap: 20px;
}

.detail-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 15px;
  background: rgba(255, 255, 255, 0.05);
  border-radius: 12px;
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.detail-item i {
  font-size: 24px;
  color: #3b82f6;
  margin-bottom: 8px;
  -webkit-text-stroke: 0.5px rgba(15, 23, 42, 0.6);
  text-shadow:
    0 0 6px rgba(15, 23, 42, 0.45),
    0 0 12px rgba(15, 23, 42, 0.35);
}

.detail-item span {
  font-size: 20px;
  font-weight: bold;
  color: #1f2937;
  margin-bottom: 4px;
}

.detail-item small {
  font-size: 12px;
  color: #374151;
  letter-spacing: 0.4px;
  text-transform: uppercase;
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

@media (max-width: 768px) {
  .dashboard-view {
    padding: 20px;
  }
  
  .current-weather {
    flex-direction: column;
  }
  
  .weather-details {
    grid-template-columns: 1fr;
  }
  
  .forecast-cards {
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
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