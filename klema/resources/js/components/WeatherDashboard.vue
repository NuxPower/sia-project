<template>
  <div class="weather-dashboard">
    <!-- Debug info -->
    <div style="position: fixed; top: 10px; left: 10px; background: red; color: white; padding: 10px; z-index: 9999;">
      Vue Component Loaded!
    </div>
    <!-- Sidebar Navigation -->
    <div class="sidebar">
      <div class="sidebar-icons">
        <div class="icon-item" @click="setActiveView('dashboard')">
          <i class="fas fa-th"></i>
        </div>
        <div class="icon-item" @click="setActiveView('map')">
          <i class="fas fa-map"></i>
        </div>
        <div class="icon-item" @click="setActiveView('calendar')">
          <i class="fas fa-calendar"></i>
        </div>
        <div class="icon-item" @click="setActiveView('alerts')">
          <i class="fas fa-bell"></i>
        </div>
        <div class="icon-item" @click="setActiveView('settings')">
          <i class="fas fa-cog"></i>
        </div>
      </div>
    </div>

    <!-- Search Bar -->
    <div class="search-bar">
      <div class="search-input">
        <i class="fas fa-search"></i>
        <input 
          type="text" 
          v-model="searchLocation" 
          @keyup.enter="searchWeather"
          placeholder="Search location..."
        />
      </div>
    </div>

    <!-- Main Map Area -->
    <div class="map-container">
      <div id="map" ref="mapContainer"></div>
      
      <!-- Weather Overlay -->
      <div class="weather-overlay" v-if="currentWeather">
        <div class="weather-info">
          <div class="location">{{ currentWeather.name }}, {{ currentWeather.sys.country }}</div>
          <div class="temperature">{{ Math.round(currentWeather.main.temp) }}°C</div>
          <div class="condition">{{ currentWeather.weather[0].description }}</div>
          <div class="details">
            <span>Humidity: {{ currentWeather.main.humidity }}%</span>
            <span>Wind: {{ currentWeather.wind.speed }} m/s</span>
            <span>Pressure: {{ currentWeather.main.pressure }} mb</span>
          </div>
        </div>
      </div>
    </div>

    <!-- 7-Day Forecast -->
    <div class="forecast-bar">
      <div class="forecast-item" v-for="(day, index) in forecast" :key="index">
        <div class="day">{{ day.day }}</div>
        <div class="weather-icon">
          <i :class="getWeatherIcon(day.condition)"></i>
        </div>
        <div class="temps">
          <span class="high">H: {{ day.temp_max }}°</span>
          <span class="low">L: {{ day.temp_min }}°</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, nextTick } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

// Fix Leaflet default icons
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
  iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon-2x.png',
  iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon.png',
  shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png',
});

export default {
  name: 'WeatherDashboard',
  setup() {
    const mapContainer = ref(null);
    const map = ref(null);
    const currentWeather = ref(null);
    const forecast = ref([]);
    const searchLocation = ref('Maramag, Northern Mindanao');
    const activeView = ref('dashboard');

    // Initialize map
    const initMap = async () => {
      await nextTick();
      
      if (mapContainer.value) {
        // Initialize map centered on Mindanao, Philippines
        map.value = L.map(mapContainer.value).setView([7.5, 124.5], 7);
        
        // Add OpenStreetMap tiles
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
          attribution: '© OpenStreetMap contributors'
        }).addTo(map.value);

        // Add satellite overlay for weather visualization
        L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
          attribution: '© Esri',
          opacity: 0.3
        }).addTo(map.value);
      }
    };

    // Fetch weather data from Laravel API
    const fetchWeatherData = async (location = 'Maramag,PH') => {
      try {
        // Fetch current weather from Laravel API
        const currentResponse = await fetch(`/api/weather/current?location=${encodeURIComponent(location)}`);
        currentWeather.value = await currentResponse.json();
        
        // Fetch forecast from Laravel API
        const forecastResponse = await fetch(`/api/weather/forecast?location=${encodeURIComponent(location)}&days=7`);
        forecast.value = await forecastResponse.json();
        
        // Update map center if location changed
        if (map.value && currentWeather.value.coord) {
          map.value.setView([currentWeather.value.coord.lat, currentWeather.value.coord.lon], 10);
        }
        
      } catch (error) {
        console.error('Error fetching weather data:', error);
      }
    };

    // Search for weather by location
    const searchWeather = () => {
      if (searchLocation.value.trim()) {
        fetchWeatherData(searchLocation.value);
      }
    };

    // Get weather icon class
    const getWeatherIcon = (condition) => {
      const iconMap = {
        'Clear': 'fas fa-sun',
        'Clouds': 'fas fa-cloud',
        'Rain': 'fas fa-cloud-rain',
        'Drizzle': 'fas fa-cloud-drizzle',
        'Thunderstorm': 'fas fa-bolt',
        'Snow': 'fas fa-snowflake',
        'Mist': 'fas fa-smog',
        'Fog': 'fas fa-smog',
        'Haze': 'fas fa-smog',
        'Dust': 'fas fa-smog',
        'Sand': 'fas fa-smog',
        'Ash': 'fas fa-smog',
        'Squall': 'fas fa-wind',
        'Tornado': 'fas fa-tornado'
      };
      return iconMap[condition] || 'fas fa-cloud';
    };

    // Set active view
    const setActiveView = (view) => {
      activeView.value = view;
    };

    onMounted(async () => {
      console.log('WeatherDashboard mounted');
      try {
        await initMap();
        console.log('Map initialized');
        await fetchWeatherData();
        console.log('Weather data fetched');
      } catch (error) {
        console.error('Error in onMounted:', error);
      }
    });

    return {
      mapContainer,
      currentWeather,
      forecast,
      searchLocation,
      activeView,
      initMap,
      fetchWeatherData,
      searchWeather,
      getWeatherIcon,
      setActiveView
    };
  }
};
</script>

<style scoped>
.weather-dashboard {
  position: relative;
  width: 100vw;
  height: 100vh;
  background: #1a1a1a;
  overflow: hidden;
}

.sidebar {
  position: fixed;
  left: 20px;
  top: 50%;
  transform: translateY(-50%);
  z-index: 1000;
}

.sidebar-icons {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.icon-item {
  width: 50px;
  height: 50px;
  background: rgba(0, 0, 0, 0.7);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.3s ease;
  color: white;
  font-size: 18px;
}

.icon-item:hover {
  background: rgba(0, 0, 0, 0.9);
  transform: scale(1.1);
}

.search-bar {
  position: fixed;
  top: 20px;
  right: 20px;
  z-index: 1000;
}

.search-input {
  display: flex;
  align-items: center;
  background: rgba(0, 0, 0, 0.7);
  border-radius: 12px;
  padding: 12px 16px;
  color: white;
  min-width: 300px;
}

.search-input i {
  margin-right: 10px;
  color: #ccc;
}

.search-input input {
  background: none;
  border: none;
  color: white;
  outline: none;
  flex: 1;
  font-size: 14px;
}

.search-input input::placeholder {
  color: #ccc;
}

.map-container {
  position: relative;
  width: 100%;
  height: 100%;
}

#map {
  width: 100%;
  height: 100%;
}

.weather-overlay {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  background: rgba(0, 0, 0, 0.8);
  border-radius: 16px;
  padding: 20px;
  color: white;
  text-align: center;
  z-index: 1000;
  backdrop-filter: blur(10px);
}

.location {
  font-size: 18px;
  font-weight: bold;
  margin-bottom: 10px;
}

.temperature {
  font-size: 48px;
  font-weight: bold;
  margin-bottom: 10px;
}

.condition {
  font-size: 16px;
  margin-bottom: 15px;
  text-transform: capitalize;
}

.details {
  display: flex;
  flex-direction: column;
  gap: 5px;
  font-size: 14px;
  color: #ccc;
}

.forecast-bar {
  position: fixed;
  bottom: 20px;
  left: 50%;
  transform: translateX(-50%);
  display: flex;
  background: rgba(0, 0, 0, 0.8);
  border-radius: 16px;
  padding: 16px;
  gap: 20px;
  z-index: 1000;
  backdrop-filter: blur(10px);
}

.forecast-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  color: white;
  min-width: 80px;
}

.day {
  font-size: 12px;
  margin-bottom: 8px;
  font-weight: bold;
}

.weather-icon {
  font-size: 20px;
  margin-bottom: 8px;
}

.temps {
  display: flex;
  flex-direction: column;
  align-items: center;
  font-size: 11px;
}

.high {
  font-weight: bold;
}

.low {
  color: #ccc;
}

/* Responsive design */
@media (max-width: 768px) {
  .sidebar {
    left: 10px;
  }
  
  .icon-item {
    width: 40px;
    height: 40px;
    font-size: 16px;
  }
  
  .search-bar {
    top: 10px;
    right: 10px;
  }
  
  .search-input {
    min-width: 250px;
    padding: 10px 12px;
  }
  
  .forecast-bar {
    bottom: 10px;
    padding: 12px;
    gap: 15px;
  }
  
  .forecast-item {
    min-width: 60px;
  }
  
  .weather-overlay {
    padding: 15px;
  }
  
  .temperature {
    font-size: 36px;
  }
}
</style>
