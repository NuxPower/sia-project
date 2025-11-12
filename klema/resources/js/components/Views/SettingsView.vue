<template>
  <div class="settings-view">
    <div class="settings-header">
      <h2>
        <i class="fas fa-cog"></i>
        Settings
      </h2>
    </div>

    <!-- Location Settings -->
    <div class="settings-section">
      <h3>
        <i class="fas fa-map-marker-alt"></i>
        Location Settings
      </h3>
      <div class="setting-item">
        <label>Default Location</label>
        <input type="text" v-model="settings.defaultLocation" class="setting-input">
      </div>
      <div class="setting-item">
        <label>Use Current Location</label>
        <button class="action-button" @click="getCurrentLocation">
          <i class="fas fa-crosshairs"></i>
          Detect Location
        </button>
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
          <span class="info-value">1.0.0</span>
        </div>
        <div class="info-row">
          <span class="info-label">Weather Data:</span>
          <span class="info-value">OpenWeatherMap API</span>
        </div>
        <div class="info-row">
          <span class="info-label">Last Updated:</span>
          <span class="info-value">October 2025</span>
        </div>
      </div>
    </div>

    <!-- Actions -->
    <div class="settings-actions">
      <button class="action-button primary" @click="saveSettings" :disabled="isSaving">
        <i class="fas fa-save"></i>
        <span v-if="!isSaving">Save Changes</span>
        <span v-else>Saving...</span>
      </button>
      <button class="action-button danger" @click="clearAllData" :disabled="isClearing">
        <i class="fas fa-trash"></i>
        <span v-if="!isClearing">Clear All Data</span>
        <span v-else>Clearing...</span>
      </button>
      <button class="action-button logout" @click="logout">
        <i class="fas fa-sign-out-alt"></i>
        Logout
      </button>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref, onMounted } from 'vue';
import { authorizedFetch } from '../../services/http';
import { revokeApiToken } from '../../services/auth';
import { useGlobalAlerts } from '../../composables/useGlobalAlerts';

const STORAGE_KEY = 'appSettings';
const NOTIFICATION_SETTINGS_KEY = 'notificationSettings';

const defaultSettings = Object.freeze({
  defaultLocation: 'Northern Mindanao',
  temperatureUnit: 'celsius',
  windSpeedUnit: 'ms',
  timeFormat: '24h',
  saveSearchHistory: true,
  anonymousUsageData: false
});

const settings = reactive({ ...defaultSettings });
const isSaving = ref(false);
const isClearing = ref(false);

const { showSuccess, showError } = useGlobalAlerts();

const loadSettings = () => {
  if (typeof window === 'undefined') {
    return;
  }

  try {
    const stored = window.localStorage?.getItem(STORAGE_KEY);
    if (stored) {
      const parsed = JSON.parse(stored);
      Object.assign(settings, { ...defaultSettings, ...parsed });
    }
  } catch (error) {
    console.error('Failed to load settings:', error);
    showError('Load Failed', 'Unable to load your saved settings.');
  }
};

onMounted(() => {
  loadSettings();
});

const saveSettings = async () => {
  if (isSaving.value) return;

  try {
    isSaving.value = true;

    if (typeof window !== 'undefined') {
      window.localStorage?.setItem(STORAGE_KEY, JSON.stringify(settings));
    }

    showSuccess('Settings Saved', 'Your preferences have been updated.');
  } catch (error) {
    console.error('Save settings error:', error);
    showError('Save Failed', 'Unable to save your settings. Please try again.');
  } finally {
    isSaving.value = false;
  }
};

const clearAllData = async () => {
  if (isClearing.value) return;
  if (typeof window === 'undefined') return;

  const confirmed = window.confirm('This will reset your local settings. Continue?');
  if (!confirmed) return;

  try {
    isClearing.value = true;

    window.localStorage?.removeItem(STORAGE_KEY);
    window.localStorage?.removeItem(NOTIFICATION_SETTINGS_KEY);

    Object.assign(settings, { ...defaultSettings });

    showSuccess('Data Cleared', 'All settings have been reset to defaults.');
  } catch (error) {
    console.error('Clear data error:', error);
    showError('Reset Failed', 'Unable to clear settings. Please try again.');
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
    window.location.href = '/login';
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

@media (max-width: 768px) {
  .settings-view {
    padding: 20px;
  }
  
  .settings-actions {
    flex-direction: column;
  }
  
  .radio-group {
    flex-direction: column;
    gap: 10px;
  }
}
</style>