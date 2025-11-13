<template>
  <div class="exports-view">
    <div class="export-header">
      <h2>
        <i class="fas fa-download"></i>
        Data Exports
      </h2>
      <p class="export-description">Export your farm data, weather information, and activities in CSV or PDF format.</p>
    </div>

    <!-- Weather Data Export -->
    <div class="export-section">
      <h3>
        <i class="fas fa-cloud-sun"></i>
        Weather Data Export
      </h3>
      <form @submit.prevent="exportWeatherData" class="export-form">
        <div class="form-row">
          <div class="form-group">
            <label>Farm (Optional)</label>
            <select v-model="weatherForm.farm_id" class="form-control">
              <option value="">All Farms</option>
              <option v-for="farm in farms" :key="farm.farm_id" :value="farm.farm_id">
                {{ farm.farm_name }}
              </option>
            </select>
          </div>
          <div class="form-group">
            <label>Start Date</label>
            <input type="date" v-model="weatherForm.start_date" required class="form-control">
          </div>
          <div class="form-group">
            <label>End Date</label>
            <input type="date" v-model="weatherForm.end_date" required class="form-control">
          </div>
          <div class="form-group">
            <label>Format</label>
            <select v-model="weatherForm.format" class="form-control">
              <option value="csv">CSV</option>
              <option value="pdf">PDF</option>
            </select>
          </div>
        </div>
        <button type="submit" :disabled="weatherLoading" class="action-button primary">
          <i class="fas fa-download" v-if="!weatherLoading"></i>
          <i class="fas fa-spinner fa-spin" v-else></i>
          <span v-if="!weatherLoading">Export Weather Data</span>
          <span v-else>Exporting...</span>
        </button>
      </form>
    </div>

    <!-- Farm Data Export -->
    <div class="export-section">
      <h3>
        <i class="fas fa-tractor"></i>
        Farm Data Export
      </h3>
      <form @submit.prevent="exportFarmData" class="export-form">
        <div class="form-row">
          <div class="form-group">
            <label>Format</label>
            <select v-model="farmForm.format" class="form-control">
              <option value="csv">CSV</option>
              <option value="pdf">PDF</option>
            </select>
          </div>
        </div>
        <button type="submit" :disabled="farmLoading" class="action-button primary">
          <i class="fas fa-download" v-if="!farmLoading"></i>
          <i class="fas fa-spinner fa-spin" v-else></i>
          <span v-if="!farmLoading">Export Farm Data</span>
          <span v-else>Exporting...</span>
        </button>
      </form>
    </div>

    <!-- Activity Data Export -->
    <div class="export-section">
      <h3>
        <i class="fas fa-calendar-check"></i>
        Activity Data Export
      </h3>
      <form @submit.prevent="exportActivityData" class="export-form">
        <div class="form-row">
          <div class="form-group">
            <label>Start Date (Optional)</label>
            <input type="date" v-model="activityForm.start_date" class="form-control">
          </div>
          <div class="form-group">
            <label>End Date (Optional)</label>
            <input type="date" v-model="activityForm.end_date" class="form-control">
          </div>
          <div class="form-group">
            <label>Format</label>
            <select v-model="activityForm.format" class="form-control">
              <option value="csv">CSV</option>
              <option value="pdf">PDF</option>
            </select>
          </div>
        </div>
        <button type="submit" :disabled="activityLoading" class="action-button primary">
          <i class="fas fa-download" v-if="!activityLoading"></i>
          <i class="fas fa-spinner fa-spin" v-else></i>
          <span v-if="!activityLoading">Export Activity Data</span>
          <span v-else>Exporting...</span>
        </button>
      </form>
    </div>

    <!-- Export History -->
    <div class="export-section" v-if="exports.length > 0">
      <h3>
        <i class="fas fa-history"></i>
        Export History
      </h3>
      <div class="exports-list">
        <div v-for="exportItem in exports" :key="exportItem.export_id" class="export-item">
          <div class="export-info">
            <div class="export-name">
              <i class="fas fa-file" :class="getFileIcon(exportItem.file_name)"></i>
              {{ exportItem.file_name }}
            </div>
            <div class="export-meta">
              <span>{{ formatDate(exportItem.created_at) }}</span>
              <span v-if="exportItem.file_size">{{ exportItem.file_size }}</span>
            </div>
          </div>
          <a :href="exportItem.download_url" class="action-button small">
            <i class="fas fa-download"></i>
            Download
          </a>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';
import { ensureApiToken } from '../../services/auth';
import { useGlobalAlerts } from '../../composables/useGlobalAlerts';
import { useFarms } from '../../composables/useFarms';

const { showSuccess, showError } = useGlobalAlerts();
const { farms, fetchFarms } = useFarms();

const weatherForm = reactive({
  farm_id: '',
  start_date: '',
  end_date: '',
  format: 'csv'
});

const farmForm = reactive({
  format: 'csv'
});

const activityForm = reactive({
  start_date: '',
  end_date: '',
  format: 'csv'
});

const weatherLoading = ref(false);
const farmLoading = ref(false);
const activityLoading = ref(false);
const exports = ref([]);

onMounted(async () => {
  await ensureApiToken(axios);
  await fetchFarms();
  await fetchExports();
  
  // Set default dates for weather export (last 30 days)
  const endDate = new Date();
  const startDate = new Date();
  startDate.setDate(startDate.getDate() - 30);
  weatherForm.start_date = startDate.toISOString().split('T')[0];
  weatherForm.end_date = endDate.toISOString().split('T')[0];
});

const fetchExports = async () => {
  try {
    await ensureApiToken(axios);
    const response = await axios.get('/api/exports');
    if (response.data?.success && Array.isArray(response.data.exports)) {
      exports.value = response.data.exports;
    } else {
      exports.value = [];
    }
  } catch (error) {
    console.error('Failed to fetch exports:', error);
    exports.value = [];
  }
};

const exportWeatherData = async () => {
  weatherLoading.value = true;
  try {
    await ensureApiToken(axios);
    const response = await axios.post('/api/exports/weather', weatherForm, {
      headers: {
        'Accept': 'application/json'
      }
    });
    
    if (response.data?.success) {
      showSuccess('Export Complete', response.data.message || 'Weather data has been exported successfully.');
      if (response.data.export) {
        exports.value.unshift(response.data.export);
      }
      await fetchExports();
    }
  } catch (error) {
    showError('Export Failed', error.response?.data?.message || 'Failed to export weather data.');
  } finally {
    weatherLoading.value = false;
  }
};

const exportFarmData = async () => {
  farmLoading.value = true;
  try {
    await ensureApiToken(axios);
    const response = await axios.post('/api/exports/farms', farmForm, {
      headers: {
        'Accept': 'application/json'
      }
    });
    
    if (response.data?.success) {
      showSuccess('Export Complete', response.data.message || 'Farm data has been exported successfully.');
      if (response.data.export) {
        exports.value.unshift(response.data.export);
      }
      await fetchExports();
    }
  } catch (error) {
    showError('Export Failed', error.response?.data?.message || 'Failed to export farm data.');
  } finally {
    farmLoading.value = false;
  }
};

const exportActivityData = async () => {
  activityLoading.value = true;
  try {
    await ensureApiToken(axios);
    const response = await axios.post('/api/exports/activities', activityForm, {
      headers: {
        'Accept': 'application/json'
      }
    });
    
    if (response.data?.success) {
      showSuccess('Export Complete', response.data.message || 'Activity data has been exported successfully.');
      if (response.data.export) {
        exports.value.unshift(response.data.export);
      }
      await fetchExports();
    }
  } catch (error) {
    showError('Export Failed', error.response?.data?.message || 'Failed to export activity data.');
  } finally {
    activityLoading.value = false;
  }
};

const getFileIcon = (fileName) => {
  if (fileName?.endsWith('.pdf')) return 'fa-file-pdf';
  if (fileName?.endsWith('.csv')) return 'fa-file-csv';
  return 'fa-file';
};

const formatDate = (dateString) => {
  if (!dateString) return '';
  return new Date(dateString).toLocaleString();
};
</script>

<style scoped>
.exports-view {
  width: 100%;
  margin: 0;
  padding: 20px clamp(20px, 4vw, 40px);
  overflow-y: auto;
  height: 100%;
  scrollbar-width: none;
  -ms-overflow-style: none;
  box-sizing: border-box;
}

.exports-view::-webkit-scrollbar {
  display: none;
}

.export-header {
  margin-bottom: 30px;
}

.export-header h2 {
  color: white;
  font-size: 28px;
  display: flex;
  align-items: center;
  gap: 12px;
  margin: 0 0 10px 0;
}

.export-description {
  color: #9ca3af;
  font-size: 14px;
  margin: 0;
}

.export-section {
  background: rgba(0, 0, 0, 0.6);
  border-radius: 16px;
  padding: 25px;
  margin-bottom: 20px;
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.export-section h3 {
  color: white;
  font-size: 18px;
  margin-bottom: 20px;
  display: flex;
  align-items: center;
  gap: 10px;
}

.export-section h3 i {
  color: #3b82f6;
}

.export-form {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.form-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 15px;
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

.form-control option {
  color: black;
  background: white;
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
  text-decoration: none;
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

.action-button.small {
  padding: 8px 16px;
  font-size: 13px;
}

.exports-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.export-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px;
  background: rgba(255, 255, 255, 0.05);
  border-radius: 12px;
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.export-info {
  flex: 1;
}

.export-name {
  color: white;
  font-size: 15px;
  font-weight: 500;
  margin-bottom: 6px;
  display: flex;
  align-items: center;
  gap: 10px;
}

.export-name i {
  color: #3b82f6;
}

.export-meta {
  display: flex;
  gap: 15px;
  color: #9ca3af;
  font-size: 13px;
}

/* Responsive Design - Mobile First Approach */

/* Extra Small Devices (phones, up to 480px) */
@media (max-width: 480px) {
  .exports-view {
    padding: 1rem;
  }

  .export-header h2 {
    font-size: 1.25rem;
    margin-bottom: 0.625rem;
  }

  .export-description {
    font-size: 0.8125rem;
  }

  .export-section {
    padding: 1rem;
    margin-bottom: 1rem;
    border-radius: 0.75rem;
  }

  .export-section h3 {
    font-size: 1.125rem;
    margin-bottom: 1rem;
  }

  .form-row {
    grid-template-columns: 1fr;
    gap: 0.75rem;
  }

  .form-group label {
    font-size: 0.8125rem;
  }

  .form-control {
    padding: 0.75rem;
    font-size: 0.8125rem;
  }

  .action-button {
    width: 100%;
    padding: 0.875rem 1rem;
    font-size: 0.875rem;
  }

  .export-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 1rem;
  }

  .export-name {
    font-size: 0.875rem;
  }

  .export-meta {
    font-size: 0.75rem;
    flex-direction: column;
    gap: 0.25rem;
  }
}

/* Small Devices (landscape phones, 481px to 640px) */
@media (min-width: 481px) and (max-width: 640px) {
  .exports-view {
    padding: 1.25rem;
  }

  .form-row {
    grid-template-columns: 1fr;
  }

  .export-item {
    flex-direction: column;
  }
}

/* Medium Devices (tablets, 641px to 768px) */
@media (min-width: 641px) and (max-width: 768px) {
  .exports-view {
    padding: 1.5rem;
  }

  .form-row {
    grid-template-columns: repeat(2, 1fr);
  }

  .export-item {
    flex-direction: row;
  }
}

/* Standard Mobile (up to 768px) */
@media (max-width: 768px) {
  .exports-view {
    padding: 1.25rem;
  }
  
  .form-row {
    grid-template-columns: 1fr;
  }
  
  .export-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.75rem;
  }
  
  .action-button {
    width: 100%;
  }
}

/* Large Devices (desktops, 1024px and up) */
@media (min-width: 1024px) {
  .exports-view {
    max-width: min(1200px, 95vw);
    margin: 0 auto;
    padding: 20px clamp(20px, 4vw, 40px);
  }

  .form-row {
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  }
}

/* Extra Large Devices (large desktops, 1440px and up) */
@media (min-width: 1440px) {
  .exports-view {
    padding: 2rem clamp(2rem, 5vw, 2.5rem);
  }

  .export-section {
    padding: 2rem;
  }
}

/* Zoom Support - Ensure proper scaling */
@media (min-resolution: 192dpi) {
  .export-section {
    border-width: 1px;
  }
}
</style>

