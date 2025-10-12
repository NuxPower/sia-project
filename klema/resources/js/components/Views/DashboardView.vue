<template>
  <div class="dashboard-view">
    <!-- Top Weather Summary Card -->
    <div class="weather-summary-card">
      <div class="location-info">
        <i class="fas fa-map-marker-alt"></i>
        <h2>{{ currentWeather?.name || 'Loading...' }}</h2>
      </div>
      
      <div class="current-weather" v-if="currentWeather">
        <div class="temperature-display">
          <span class="temp-value">{{ Math.round(currentWeather.main.temp) }}°C</span>
          <div class="weather-description">
            <i :class="getWeatherIcon(currentWeather.weather[0].main)"></i>
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

    <!-- Weather Forecast Grid -->
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
            <i :class="getWeatherIcon(day.condition)"></i>
          </div>
          <div class="forecast-temp">
            <span class="temp-max">{{ day.temp_max }}°</span>
            <span class="temp-min">{{ day.temp_min }}°</span>
          </div>
          <div class="forecast-condition">{{ day.condition }}</div>
        </div>
      </div>
    </div>

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
import { computed } from 'vue';

const props = defineProps({
  currentWeather: Object,
  forecast: Array,
  getDayLabel: Function,
  getWeatherIcon: Function
});

const formatDate = (date) => {
  if (!date) return '';
  const parts = date.split('-');
  const dateObj = new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2]));
  return dateObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
};
</script>

<style scoped>
.dashboard-view {
  padding: 40px;
  max-width: 1400px;
  margin: 0 auto;
  overflow-y: auto;
  height: 100vh;
  
  /* Hide scrollbar */
  scrollbar-width: none; /* Firefox */
  -ms-overflow-style: none; /* IE and Edge */
}

.dashboard-view::-webkit-scrollbar {
  display: none; /* Chrome, Safari, Opera */
}

.weather-summary-card {
  background: rgba(0, 0, 0, 0.6);
  border-radius: 20px;
  padding: 30px;
  margin-bottom: 30px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(10px);
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
}

.detail-item span {
  font-size: 20px;
  font-weight: bold;
  color: white;
  margin-bottom: 4px;
}

.detail-item small {
  font-size: 12px;
  color: #9ca3af;
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
</style>