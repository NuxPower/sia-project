<template>
  <div class="admin-weather-update">
    <div class="update-header">
      <h3>
        <i class="fas fa-sync-alt"></i>
        Manual Weather Update
      </h3>
      <p class="update-description">Manually trigger weather data update from external API</p>
    </div>

    <form @submit.prevent="updateWeather" class="update-form">
      <div class="form-group">
        <label>Update Method</label>
        <div class="radio-group">
          <label class="radio-label">
            <input type="radio" v-model="updateMethod" value="location" />
            <span>By Location</span>
          </label>
          <label class="radio-label">
            <input type="radio" v-model="updateMethod" value="coordinates" />
            <span>By Coordinates</span>
          </label>
          <label class="radio-label">
            <input type="radio" v-model="updateMethod" value="farm" />
            <span>By Farm</span>
          </label>
        </div>
      </div>

      <!-- Location Input -->
      <div v-if="updateMethod === 'location'" class="form-group">
        <label>Location Name</label>
        <input 
          type="text" 
          v-model="form.location" 
          placeholder="e.g., Butuan, Caraga, PH"
          class="form-control"
          required
        />
      </div>

      <!-- Coordinates Input -->
      <div v-if="updateMethod === 'coordinates'" class="form-row">
        <div class="form-group">
          <label>Latitude</label>
          <input 
            type="number" 
            v-model.number="form.lat" 
            step="any"
            min="-90"
            max="90"
            placeholder="e.g., 8.9475"
            class="form-control"
            required
          />
        </div>
        <div class="form-group">
          <label>Longitude</label>
          <input 
            type="number" 
            v-model.number="form.lon" 
            step="any"
            min="-180"
            max="180"
            placeholder="e.g., 125.5406"
            class="form-control"
            required
          />
        </div>
      </div>

      <!-- Farm Selection -->
      <div v-if="updateMethod === 'farm'" class="form-group">
        <label>Select Farm</label>
        <select v-model="form.farm_id" class="form-control" required>
          <option value="">Choose a farm...</option>
          <option v-for="farm in farms" :key="farm.farm_id" :value="farm.farm_id">
            {{ farm.farm_name }}
          </option>
        </select>
      </div>

      <button type="submit" :disabled="loading" class="action-button primary">
        <i class="fas fa-sync-alt" :class="{ 'fa-spin': loading }" v-if="!loading"></i>
        <i class="fas fa-spinner fa-spin" v-else></i>
        <span v-if="!loading">Update Weather</span>
        <span v-else>Updating...</span>
      </button>
    </form>

    <div v-if="lastUpdate" class="update-result">
      <div class="result-header">
        <i class="fas fa-check-circle"></i>
        <span>Last Update: {{ formatDate(lastUpdate.timestamp) }}</span>
      </div>
      <div v-if="lastUpdate.weather" class="weather-preview">
        <div class="weather-item">
          <span class="label">Temperature:</span>
          <span class="value">{{ formatTemperature(lastUpdate.weather.main?.temp || 0, { decimals: 0 }) }}</span>
        </div>
        <div class="weather-item">
          <span class="label">Condition:</span>
          <span class="value">{{ lastUpdate.weather.weather?.[0]?.description || 'N/A' }}</span>
        </div>
        <div class="weather-item">
          <span class="label">Humidity:</span>
          <span class="value">{{ lastUpdate.weather.main?.humidity || 0 }}%</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import axios from 'axios';
import { ensureApiToken } from '../services/auth';
import { useGlobalAlerts } from '../composables/useGlobalAlerts';
import { useFarms } from '../composables/useFarms';
import { useDisplaySettings } from '../composables/useDisplaySettings';

const props = defineProps({
  farms: {
    type: Array,
    default: () => []
  }
});

const { showSuccess, showError } = useGlobalAlerts();
const { farms: allFarms, fetchFarms } = useFarms();
const { formatTemperature } = useDisplaySettings();

const updateMethod = ref('location');
const loading = ref(false);
const lastUpdate = ref(null);

const form = reactive({
  location: '',
  lat: null,
  lon: null,
  farm_id: ''
});

const farms = computed(() => props.farms.length > 0 ? props.farms : allFarms.value);

onMounted(async () => {
  await ensureApiToken(axios);
  if (props.farms.length === 0) {
    await fetchFarms();
  }
});

const updateWeather = async () => {
  loading.value = true;
  
  try {
    await ensureApiToken(axios);
    
    const payload = {};
    
    if (updateMethod.value === 'location') {
      payload.location = form.location;
    } else if (updateMethod.value === 'coordinates') {
      payload.lat = form.lat;
      payload.lon = form.lon;
    } else if (updateMethod.value === 'farm') {
      payload.farm_id = form.farm_id;
    }
    
    const response = await axios.post('/api/admin/weather/update', payload, {
      headers: {
        'Accept': 'application/json',
        'Authorization': `Bearer ${await ensureApiToken(axios)}`
      }
    });
    
    if (response.data.success) {
      lastUpdate.value = {
        timestamp: new Date(),
        weather: response.data.weather
      };
      showSuccess('Weather Updated', 'Weather data has been successfully updated from the external API.');
      
      // Reset form
      form.location = '';
      form.lat = null;
      form.lon = null;
      form.farm_id = '';
    }
  } catch (error) {
    showError('Update Failed', error.response?.data?.message || 'Failed to update weather data.');
  } finally {
    loading.value = false;
  }
};

const formatDate = (date) => {
  if (!date) return '';
  return new Date(date).toLocaleString();
};
</script>

<style scoped>
.admin-weather-update {
  background: rgba(0, 0, 0, 0.6);
  border-radius: 16px;
  padding: 25px;
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.update-header {
  margin-bottom: 20px;
}

.update-header h3 {
  color: white;
  font-size: 18px;
  margin: 0 0 8px 0;
  display: flex;
  align-items: center;
  gap: 10px;
}

.update-header h3 i {
  color: #3b82f6;
}

.update-description {
  color: #9ca3af;
  font-size: 14px;
  margin: 0;
}

.update-form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.form-group label {
  color: #d1d5db;
  font-size: 14px;
  font-weight: 500;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 15px;
}

.radio-group {
  display: flex;
  gap: 20px;
  flex-wrap: wrap;
}

.radio-label {
  display: flex;
  align-items: center;
  gap: 8px;
  color: white;
  cursor: pointer;
  font-size: 14px;
}

.radio-label input[type="radio"] {
  width: 18px;
  height: 18px;
  cursor: pointer;
  accent-color: #3b82f6;
}

.form-control {
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  padding: 12px 16px;
  color: white;
  font-size: 14px;
  transition: all 0.3s ease;
}

.form-control:focus {
  outline: none;
  border-color: #3b82f6;
  background: rgba(255, 255, 255, 0.08);
}

.action-button {
  background: rgba(59, 130, 246, 0.2);
  border: 1px solid rgba(59, 130, 246, 0.4);
  color: white;
  padding: 14px 24px;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  font-size: 15px;
  font-weight: 600;
}

.action-button:hover:not(:disabled) {
  transform: translateY(-2px);
  background: rgba(59, 130, 246, 0.4);
}

.action-button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.action-button.primary {
  background: rgba(34, 197, 94, 0.2);
  border-color: rgba(34, 197, 94, 0.4);
}

.action-button.primary:hover:not(:disabled) {
  background: rgba(34, 197, 94, 0.4);
}

.update-result {
  margin-top: 20px;
  padding: 16px;
  background: rgba(34, 197, 94, 0.1);
  border-radius: 12px;
  border: 1px solid rgba(34, 197, 94, 0.3);
}

.result-header {
  display: flex;
  align-items: center;
  gap: 10px;
  color: #22c55e;
  font-size: 14px;
  font-weight: 500;
  margin-bottom: 12px;
}

.weather-preview {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.weather-item {
  display: flex;
  justify-content: space-between;
  color: white;
  font-size: 14px;
}

.weather-item .label {
  color: #9ca3af;
}

.weather-item .value {
  font-weight: 500;
}

@media (max-width: 768px) {
  .form-row {
    grid-template-columns: 1fr;
  }
  
  .radio-group {
    flex-direction: column;
    gap: 10px;
  }
}
</style>

