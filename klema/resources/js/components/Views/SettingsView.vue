<template>
  <div class="settings-view">
    <div class="settings-header">
      <h2>
        <i class="fas fa-cog"></i>
        Settings
      </h2>
    </div>

    <!-- Loading State -->
    <div v-if="isLoadingSettings" class="loading-state">
      <i class="fas fa-spinner fa-spin"></i>
      <p>Loading settings...</p>
    </div>

    <!-- Settings Content -->
    <template v-else>
      <!-- Location Settings -->
      <div class="settings-section">
        <h3>
          <i class="fas fa-map-marker-alt"></i>
          Location Settings
        </h3>
        <div class="setting-item">
          <label>Location Type</label>
          <div class="radio-group">
            <label class="radio-label">
              <input type="radio" name="locationType" value="custom" v-model="settings.locationType">
              <span>Custom Location</span>
            </label>
            <label class="radio-label">
              <input type="radio" name="locationType" value="farm" v-model="settings.locationType">
              <span>Farm Location</span>
            </label>
          </div>
        </div>
        
        <!-- Custom Location Input -->
        <div class="setting-item" v-if="settings.locationType === 'custom'">
          <label>Default Location</label>
          <input type="text" v-model="settings.defaultLocation" class="setting-input" placeholder="Enter location">
          <button class="action-button" @click="getCurrentLocation" style="margin-top: 10px;">
            <i class="fas fa-crosshairs"></i>
            Detect Location
          </button>
        </div>
        
        <!-- Farm Location Dropdown -->
        <div class="setting-item" v-if="settings.locationType === 'farm'">
          <label>Select Farm</label>
          <select v-model="settings.selectedFarmId" class="setting-select" :disabled="farmsLoading">
            <option value="">-- Select a Farm --</option>
            <option v-for="farm in farms" :key="farm.farm_id" :value="farm.farm_id">
              {{ farm.farm_name }}
            </option>
          </select>
          <small v-if="farmsLoading" style="color: #9ca3af; margin-top: 8px; display: block;">
            Loading farms...
          </small>
          <small v-else-if="farms.length === 0" style="color: #9ca3af; margin-top: 8px; display: block;">
            No farms available. Create a farm first.
          </small>
        </div>
      </div>

      <!-- Display Settings -->
      <div class="settings-section">
      <h3>
        <i class="fas fa-desktop"></i>
        Display Settings
      </h3>
      <div class="setting-item">
        <label>Temperature Unit</label>
        <div class="radio-group">
          <label class="radio-label">
            <input type="radio" name="temp" value="celsius" v-model="settings.temperatureUnit">
            <span>Celsius (°C)</span>
          </label>
          <label class="radio-label">
            <input type="radio" name="temp" value="fahrenheit" v-model="settings.temperatureUnit">
            <span>Fahrenheit (°F)</span>
          </label>
        </div>
      </div>
      <div class="setting-item">
        <label>Wind Speed Unit</label>
        <select class="setting-select" v-model="settings.windSpeedUnit">
          <option value="ms">m/s</option>
          <option value="kmh">km/h</option>
          <option value="mph">mph</option>
        </select>
      </div>
      <div class="setting-item">
        <label>Time Format</label>
        <div class="radio-group">
          <label class="radio-label">
            <input type="radio" name="time" value="24h" v-model="settings.timeFormat">
            <span>24-hour</span>
          </label>
          <label class="radio-label">
            <input type="radio" name="time" value="12h" v-model="settings.timeFormat">
            <span>12-hour</span>
          </label>
        </div>
      </div>
      </div>

      <!-- Data & Privacy -->
      <div class="settings-section">
      <h3>
        <i class="fas fa-shield-alt"></i>
        Data & Privacy
      </h3>
      <div class="toggle-item">
        <div class="toggle-info">
          <span>Save Search History</span>
          <small>Keep track of your location searches</small>
        </div>
        <label class="switch">
          <input type="checkbox" v-model="settings.saveSearchHistory">
          <span class="slider"></span>
        </label>
      </div>
      <div class="toggle-item">
        <div class="toggle-info">
          <span>Anonymous Usage Data</span>
          <small>Help improve the app by sharing usage data</small>
        </div>
        <label class="switch">
          <input type="checkbox" v-model="settings.anonymousUsageData">
          <span class="slider"></span>
        </label>
      </div>
      </div>

      <!-- About -->
      <div class="settings-section">
      <h3>
        <i class="fas fa-info-circle"></i>
        About
      </h3>
      <div class="about-info">
        <div class="info-row">
          <span class="info-label">Version:</span>
          <span class="info-value">1.1.1</span>
        </div>
        <div class="info-row">
          <span class="info-label">Weather Data:</span>
          <span class="info-value">OpenWeatherMap API; Open-Meteo API</span>
        </div>
        <div class="info-row">
          <span class="info-label">Last Updated:</span>
          <span class="info-value">October 2025</span>
        </div>
      </div>
      </div>

      <!-- Actions -->
      <div class="settings-actions">
      <button class="action-button primary" @click="saveSettings" :disabled="isSaving || isLoadingSettings">
        <i class="fas fa-save"></i>
        <span v-if="!isSaving">Save Changes</span>
        <span v-else>Saving...</span>
      </button>
      <button class="action-button danger" @click="clearAllData" :disabled="isClearing || isLoadingSettings">
        <i class="fas fa-trash"></i>
        <span v-if="!isClearing">Clear All Data</span>
        <span v-else>Clearing...</span>
      </button>
      <button class="action-button logout" @click="logout" :disabled="isLoadingSettings">
        <i class="fas fa-sign-out-alt"></i>
        Logout
      </button>
      </div>
    </template>
  </div>
</template>

<script setup>
import { reactive, ref, onMounted, watch } from 'vue';
import { authorizedFetch } from '../../services/http';
import { revokeApiToken } from '../../services/auth';
import { useGlobalAlerts } from '../../composables/useGlobalAlerts';
import { useFarms } from '../../composables/useFarms';
import { ensureApiToken } from '../../services/auth';
import axios from 'axios';

const NOTIFICATION_SETTINGS_KEY = 'notificationSettings';

const defaultSettings = Object.freeze({
  locationType: 'custom', // 'custom' or 'farm'
  defaultLocation: '',
  selectedFarmId: '',
  temperatureUnit: 'celsius',
  windSpeedUnit: 'ms',
  timeFormat: '24h',
  saveSearchHistory: true,
  anonymousUsageData: false
});

const settings = reactive({ ...defaultSettings });
const isSaving = ref(false);
const isClearing = ref(false);
const isLoadingSettings = ref(false);

const { showSuccess, showError } = useGlobalAlerts();
const { farms, loading: farmsLoading, fetchFarms } = useFarms();

const loadSettings = async () => {
  try {
    isLoadingSettings.value = true;
    await ensureApiToken(axios);
    
    const response = await axios.get('/api/settings');
    
    if (response.data?.success && response.data?.settings) {
      Object.assign(settings, { ...defaultSettings, ...response.data.settings });
    }
  } catch (error) {
    console.error('Failed to load settings:', error);
    
    // Fallback to localStorage if API fails (for backward compatibility during migration)
    if (typeof window !== 'undefined') {
      try {
        const stored = window.localStorage?.getItem('appSettings');
        if (stored) {
          const parsed = JSON.parse(stored);
          Object.assign(settings, { ...defaultSettings, ...parsed });
          // Try to sync to database (defer to avoid circular dependency)
          setTimeout(async () => {
            try {
              await ensureApiToken(axios);
              await axios.put('/api/settings', settings);
            } catch (syncError) {
              console.warn('Failed to sync localStorage settings to database:', syncError);
            }
          }, 1000);
        }
      } catch (localError) {
        console.error('Failed to load from localStorage:', localError);
      }
    }
    
    // Don't show error if we successfully loaded from localStorage
    if (!error.response || error.response.status !== 404) {
      showError('Load Failed', 'Unable to load your saved settings.');
    }
  } finally {
    isLoadingSettings.value = false;
  }
};

onMounted(async () => {
  await ensureApiToken(axios);
  await fetchFarms();
  await loadSettings();
  
  // If location type is farm but no farm is selected, and farms are available, select first farm
  if (settings.locationType === 'farm' && !settings.selectedFarmId && farms.value.length > 0) {
    settings.selectedFarmId = farms.value[0].farm_id;
  }
});

// Watch for location type changes
watch(() => settings.locationType, (newType) => {
  // When switching to farm location, auto-select first farm if available and none selected
  if (newType === 'farm' && !settings.selectedFarmId && farms.value.length > 0) {
    settings.selectedFarmId = farms.value[0].farm_id;
  }
});

const saveSettingsToAPI = async () => {
  if (isSaving.value) return;

  try {
    isSaving.value = true;
    await ensureApiToken(axios);

    // Create a copy to avoid mutating the reactive object
    const settingsToSave = { ...settings };
    
    // Remove undefined/null values
    Object.keys(settingsToSave).forEach(key => {
      if (settingsToSave[key] === undefined || settingsToSave[key] === null || settingsToSave[key] === '') {
        delete settingsToSave[key];
      }
    });

    const response = await axios.put('/api/settings', settingsToSave);

    if (response.data?.success) {
      // Update local settings with server response (to ensure consistency)
      if (response.data?.settings) {
        Object.assign(settings, { ...defaultSettings, ...response.data.settings });
      }

      // Trigger a custom event for immediate updates in the same window (before page reload)
      window.dispatchEvent(new CustomEvent('appSettingsUpdated', { 
        detail: settings 
      }));

      // Also keep localStorage in sync (as backup/fallback)
      if (typeof window !== 'undefined') {
        window.localStorage?.setItem('appSettings', JSON.stringify(settings));
      }

      showSuccess('Settings Saved', 'Your preferences have been updated. Reloading...');
      
      // Reload the page after a short delay to ensure settings are applied
      setTimeout(() => {
        window.location.reload();
      }, 1000);
    } else {
      throw new Error(response.data?.message || 'Failed to save settings');
    }
  } catch (error) {
    console.error('Save settings error:', error);
    showError('Save Failed', error.response?.data?.message || 'Unable to save your settings. Please try again.');
    isSaving.value = false;
  }
};

const saveSettings = () => {
  saveSettingsToAPI();
};

const clearAllData = async () => {
  if (isClearing.value) return;

  const confirmed = window.confirm('This will reset your settings to defaults. Continue?');
  if (!confirmed) return;

  try {
    isClearing.value = true;
    await ensureApiToken(axios);

    // Reset settings on server
    const response = await axios.post('/api/settings/reset');

    if (response.data?.success) {
      // Update local settings with server response
      if (response.data?.settings) {
        Object.assign(settings, { ...defaultSettings, ...response.data.settings });
      } else {
        Object.assign(settings, { ...defaultSettings });
      }

      // Clear localStorage as well
      if (typeof window !== 'undefined') {
        window.localStorage?.removeItem('appSettings');
        window.localStorage?.removeItem(NOTIFICATION_SETTINGS_KEY);
      }

      // Trigger event for components
      window.dispatchEvent(new CustomEvent('appSettingsUpdated', { 
        detail: settings 
      }));

      showSuccess('Data Cleared', 'All settings have been reset to defaults.');
    } else {
      throw new Error(response.data?.message || 'Failed to reset settings');
    }
  } catch (error) {
    console.error('Clear data error:', error);
    showError('Reset Failed', error.response?.data?.message || 'Unable to clear settings. Please try again.');
  } finally {
    isClearing.value = false;
  }
};

const getCurrentLocation = () => {
  if (typeof navigator === 'undefined' || !navigator.geolocation) {
    showError('Geolocation Unsupported', 'Your browser does not support geolocation.');
    return;
  }

  navigator.geolocation.getCurrentPosition(
    (position) => {
      console.log('Location detected:', position.coords);
      const { latitude, longitude } = position.coords;
      settings.defaultLocation = `${latitude.toFixed(4)}, ${longitude.toFixed(4)}`;
      showSuccess('Location Detected', 'Default location updated. Save to keep this change.');
    },
    () => {
      showError('Location Error', 'Unable to detect location. Please check your browser permissions.');
    }
  );
};

const logout = async () => {
  try {
    const response = await authorizedFetch('/api/auth/logout', {
      method: 'POST'
    });

    if (!response.ok) {
      throw new Error('Logout failed');
    }

    revokeApiToken();
    // For mobile, reload the app which will show login if needed
    window.location.reload();
  } catch (error) {
    console.error('Logout error:', error);
    showError('Logout Failed', 'Failed to logout. Please try again.');
  }
};
</script>

<style scoped>
.settings-view {
  width: 100%;
  margin: 0;
  padding: 0;
  overflow-y: auto;
  height: 100%;
  
  /* Hide scrollbar */
  scrollbar-width: none; /* Firefox */
  -ms-overflow-style: none; /* IE and Edge */
}

.settings-view::-webkit-scrollbar {
  display: none; /* Chrome, Safari, Opera */
}

.settings-header {
  margin-bottom: 30px;
}

.settings-header h2 {
  color: white;
  font-size: 28px;
  display: flex;
  align-items: center;
  gap: 12px;
  margin: 0;
}

.settings-section {
  background: rgba(0, 0, 0, 0.6);
  border-radius: 16px;
  padding: 25px;
  margin-bottom: 20px;
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.settings-section h3 {
  color: white;
  font-size: 18px;
  margin-bottom: 20px;
  display: flex;
  align-items: center;
  gap: 10px;
}

.settings-section h3 i {
  color: #3b82f6;
}

.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
  color: #9ca3af;
  text-align: center;
}

.loading-state i {
  font-size: 48px;
  margin-bottom: 20px;
  color: #3b82f6;
}

.loading-state p {
  font-size: 16px;
  margin: 0;
}

.setting-item {
  margin-bottom: 20px;
}

.setting-item:last-child {
  margin-bottom: 0;
}

.setting-item label {
  display: block;
  color: #d1d5db;
  font-size: 14px;
  margin-bottom: 8px;
  font-weight: 500;
}

.setting-input {
  width: 100%;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  padding: 12px 16px;
  color: white;
  font-size: 14px;
  transition: all 0.3s ease;
}

.setting-input:focus {
  outline: none;
  border-color: #3b82f6;
  background: rgba(255, 255, 255, 0.08);
}

.setting-select {
  width: 100%;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  padding: 12px 16px;
  color: white;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.3s ease;
}

.setting-select:focus {
  outline: none;
  border-color: #3b82f6;
  background: rgba(255, 255, 255, 0.08);
}

.setting-select:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.setting-select option {
  color: black;
  background: white;
}

.radio-group {
  display: flex;
  gap: 20px;
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

.toggle-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px;
  background: rgba(255, 255, 255, 0.05);
  border-radius: 12px;
  margin-bottom: 12px;
}

.toggle-item:last-child {
  margin-bottom: 0;
}

.toggle-info {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.toggle-info span {
  color: white;
  font-size: 15px;
  font-weight: 500;
}

.toggle-info small {
  color: #9ca3af;
  font-size: 13px;
}

.about-info {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.info-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px;
  background: rgba(255, 255, 255, 0.05);
  border-radius: 8px;
}

.info-label {
  color: #9ca3af;
  font-size: 14px;
}

.info-value {
  color: white;
  font-size: 14px;
  font-weight: 500;
}

.settings-actions {
  display: flex;
  gap: 15px;
  margin-top: 30px;
}

.action-button {
  flex: 1;
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

.action-button:hover {
  transform: translateY(-2px);
}

.action-button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

.action-button:disabled:hover {
  background: inherit;
  transform: none;
}

.action-button.primary {
  background: rgba(34, 197, 94, 0.2);
  border-color: rgba(34, 197, 94, 0.4);
}

.action-button.primary:hover {
  background: rgba(34, 197, 94, 0.4);
}

.action-button.danger {
  background: rgba(239, 68, 68, 0.2);
  border-color: rgba(239, 68, 68, 0.4);
}

.action-button.danger:hover {
  background: rgba(239, 68, 68, 0.4);
}

.action-button.logout {
  background: rgba(148, 163, 184, 0.2);
  border-color: rgba(148, 163, 184, 0.4);
}

.action-button.logout:hover {
  background: rgba(148, 163, 184, 0.35);
}

.switch {
  position: relative;
  display: inline-block;
  width: 52px;
  height: 28px;
}

.switch input {
  opacity: 0;
  width: 0;
  height: 0;
}

.slider {
  position: absolute;
  cursor: pointer;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(156, 163, 175, 0.3);
  transition: 0.3s;
  border-radius: 28px;
}

.slider:before {
  position: absolute;
  content: "";
  height: 20px;
  width: 20px;
  left: 4px;
  bottom: 4px;
  background-color: white;
  transition: 0.3s;
  border-radius: 50%;
}

input:checked + .slider {
  background-color: #3b82f6;
}

input:checked + .slider:before {
  transform: translateX(24px);
}

/* Responsive Design - Mobile First Approach */

/* Extra Small Devices (phones, up to 480px) */
@media (max-width: 480px) {
  .settings-view {
    padding: 1rem;
  }

  .settings-header h2 {
    font-size: 1.25rem;
    margin-bottom: 1.5rem;
  }

  .settings-section {
    padding: 1rem;
    margin-bottom: 1rem;
    border-radius: 0.75rem;
  }

  .settings-section h3 {
    font-size: 1.125rem;
    margin-bottom: 1rem;
  }

  .setting-item {
    margin-bottom: 1rem;
  }

  .setting-item label {
    font-size: 0.8125rem;
  }

  .setting-input, .setting-select {
    padding: 0.75rem;
    font-size: 0.8125rem;
  }

  .radio-group {
    flex-direction: column;
    gap: 0.625rem;
  }

  .radio-label {
    font-size: 0.8125rem;
  }

  .toggle-item {
    padding: 0.75rem;
    flex-direction: column;
    align-items: flex-start;
    gap: 0.75rem;
  }

  .toggle-info span {
    font-size: 0.875rem;
  }

  .toggle-info small {
    font-size: 0.75rem;
  }

  .settings-actions {
    flex-direction: column;
    gap: 0.75rem;
    margin-top: 1.5rem;
  }

  .action-button {
    width: 100%;
    padding: 0.875rem 1rem;
    font-size: 0.875rem;
  }
}

/* Small Devices (landscape phones, 481px to 640px) */
@media (min-width: 481px) and (max-width: 640px) {
  .settings-view {
    padding: 1.25rem;
  }

  .settings-actions {
    flex-direction: column;
  }

  .radio-group {
    flex-direction: column;
    gap: 0.75rem;
  }
}

/* Medium Devices (tablets, 641px to 768px) */
@media (min-width: 641px) and (max-width: 768px) {
  .settings-view {
    padding: 1.5rem;
  }

  .settings-actions {
    flex-direction: row;
    flex-wrap: wrap;
  }

  .action-button {
    flex: 1 1 calc(50% - 0.5rem);
  }
}

/* Standard Mobile (up to 768px) */
@media (max-width: 768px) {
  .settings-view {
    padding: 1.25rem;
  }
  
  .settings-actions {
    flex-direction: column;
  }
  
  .radio-group {
    flex-direction: column;
    gap: 0.625rem;
  }
}

/* Large Devices (desktops, 1024px and up) */
@media (min-width: 1024px) {
  .settings-view {
    max-width: 1200px;
    margin: 0 auto;
  }

  .settings-actions {
    flex-direction: row;
  }
}

/* Extra Large Devices (large desktops, 1440px and up) */
@media (min-width: 1440px) {
  .settings-view {
    padding: 2rem;
  }

  .settings-section {
    padding: 2rem;
  }
}

/* Zoom Support - Ensure proper scaling */
@media (min-resolution: 192dpi) {
  .settings-section {
    border-width: 1px;
  }
}
</style>