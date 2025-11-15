<template>
  <div class="alerts-view">
    <div class="alerts-header">
      <h2>
        <i class="fas fa-bell"></i>
        Weather Alerts & Notifications
      </h2>
      <button class="add-alert-button" @click="showCreateModal = true">
        <i class="fas fa-plus"></i>
        New Alert
      </button>
    </div>

    <!-- Active Alerts -->
    <div class="alerts-section">
      <h3>Active Alerts</h3>
      
      <div v-if="alertsLoadingRef" class="loading-state">
        <i class="fas fa-spinner fa-spin"></i>
        <p>Loading alerts...</p>
      </div>
      
      <div v-else-if="alertsErrorRef" class="error-state">
        <i class="fas fa-exclamation-triangle"></i>
        <p>{{ alertsErrorRef }}</p>
        <button @click="reloadActiveAlerts" class="retry-button">
          <i class="fas fa-redo"></i>
          Retry
        </button>
      </div>
      
      <div v-else-if="activeAlerts.length === 0" class="no-alerts">
        <i class="fas fa-check-circle"></i>
        <p>No active weather alerts at this time</p>
      </div>
      
      <div v-else class="alerts-list">
        <div 
          v-for="alert in activeAlerts" 
          :key="alert.id"
          class="alert-card"
          :class="alert.severity"
        >
          <div class="alert-icon">
            <i :class="alert.icon"></i>
          </div>
          <div class="alert-content">
            <h4>{{ alert.title }}</h4>
            <p>{{ alert.description }}</p>
            <div class="alert-meta">
              <div class="alert-farm">
                <i class="fas fa-map-marker-alt"></i>
                {{ alert.farmName }}
              </div>
              <div class="alert-time">
                <i class="fas fa-clock"></i>
                {{ alert.time }}
              </div>
            </div>
          </div>
          <div class="alert-actions">
            <button class="dismiss-button" @click="dismissAlert(alert.id)" title="Mark as resolved">
              <i class="fas fa-check"></i>
            </button>
            <button class="delete-button" @click="deleteAlertHandler(alert.id)" title="Delete alert">
              <i class="fas fa-trash"></i>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Forecast Warnings -->
    <div class="warnings-section">
      <div class="warnings-header">
        <h3>Forecast Warnings <span v-if="locationLabelRef" class="warnings-location">({{ locationLabelRef }})</span></h3>
        <button @click="loadWeatherData" class="refresh-button" :disabled="alertsLoadingRef" title="Refresh forecast warnings">
          <i class="fas fa-sync-alt" :class="{ 'fa-spin': alertsLoadingRef }"></i>
          Refresh
        </button>
      </div>
      
      <div v-if="alertsLoadingRef && forecastWarningsList.length === 0" class="loading-state">
        <i class="fas fa-spinner fa-spin"></i>
        <p>Loading forecast warnings...</p>
      </div>
      
      <div v-else-if="forecastWarningsList.length === 0" class="no-alerts">
        <i class="fas fa-sun"></i>
        <p>No forecast warnings at this time</p>
      </div>
      
      <div v-else class="warnings-grid">
        <div 
          v-for="warning in forecastWarningsList" 
          :key="warning.id"
          class="warning-card"
          :class="warning.severity"
        >
          <div class="warning-header">
            <i :class="warning.icon"></i>
            <span class="warning-day">{{ warning.day }}</span>
            <span class="warning-type">{{ warning.type }}</span>
          </div>
          <p class="warning-text">{{ warning.message }}</p>
          <div class="warning-value">{{ warning.value }}</div>
          <div class="warning-severity-badge" :class="warning.severity">
            {{ warning.severity.toUpperCase() }}
          </div>
        </div>
      </div>
    </div>

    <!-- Notification Settings -->
    <div class="settings-section">
      <h3>Notification Preferences</h3>
      
      <div v-if="settingsLoadingRef" class="loading-state">
        <i class="fas fa-spinner fa-spin"></i>
        <p>Loading notification settings...</p>
      </div>
      
      <div v-else class="settings-list">
        <div 
          v-for="(value, settingName) in settingsList" 
          :key="settingName"
          class="setting-item"
        >
          <div class="setting-info">
            <i :class="getSettingInfo(settingName).icon"></i>
            <div class="setting-text">
              <span class="setting-label">{{ getSettingInfo(settingName).label }}</span>
              <p class="setting-description">{{ getSettingInfo(settingName).description }}</p>
            </div>
          </div>
          <label class="switch">
            <input 
              type="checkbox" 
              :checked="value"
              @change="handleNotificationSettingChange(settingName, $event.target.checked)"
            >
            <span class="slider"></span>
          </label>
        </div>
      </div>
      
      <div v-if="settingsErrorRef" class="error-state">
        <i class="fas fa-exclamation-triangle"></i>
        {{ settingsErrorRef }}
      </div>
      
      <div v-if="settingsSavedRef" class="success-state">
        <i class="fas fa-check-circle"></i>
        Settings saved successfully!
      </div>
    </div>

    <CreateAlertModal 
      :show="showCreateModal" 
      :farms="farmsList"
      :create-alert="createAlertRef"
      @close="showCreateModal = false"
      @alert-created="handleAlertCreated"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, computed, toRefs } from 'vue';
import CreateAlertModal from '../CreateAlertModal.vue';

const props = defineProps({
  alerts: {
    type: Array,
    default: () => []
  },
  alertsLoading: {
    type: Boolean,
    default: false
  },
  alertsError: {
    type: [String, null],
    default: null
  },
  forecastWarnings: {
    type: Array,
    default: () => []
  },
  notificationSettings: {
    type: Object,
    default: () => ({})
  },
  settingsLoading: {
    type: Boolean,
    default: false
  },
  settingsError: {
    type: [String, null],
    default: null
  },
  settingsSaved: {
    type: Boolean,
    default: false
  },
  getAlertTypeInfo: {
    type: Function,
    required: true
  },
  formatAlertTime: {
    type: Function,
    required: true
  },
  getSettingInfo: {
    type: Function,
    required: true
  },
  resolveAlert: {
    type: Function,
    required: true
  },
  deleteAlert: {
    type: Function,
    required: true
  },
  updateNotificationSetting: {
    type: Function,
    required: true
  },
  fetchActiveAlerts: {
    type: Function,
    required: true
  },
  refreshForecastWarnings: {
    type: Function,
    required: true
  },
  loadNotificationSettings: {
    type: Function,
    required: true
  },
  createAlert: {
    type: Function,
    required: true
  },
  farms: {
    type: Array,
    default: () => []
  },
  locationLabel: {
    type: String,
    default: ''
  }
});

const showCreateModal = ref(false);
const {
  alerts: alertsRef,
  alertsLoading: alertsLoadingRef,
  alertsError: alertsErrorRef,
  forecastWarnings: forecastWarningsRef,
  notificationSettings: notificationSettingsRef,
  settingsLoading: settingsLoadingRef,
  settingsError: settingsErrorRef,
  settingsSaved: settingsSavedRef,
  createAlert: createAlertRef,
  locationLabel: locationLabelRef
} = toRefs(props);

const loadWeatherData = async () => {
  try {
    await props.refreshForecastWarnings();
  } catch (error) {
    console.error('Failed to load weather data:', error);
  }
};

const initializeData = async () => {
  try {
    await Promise.all([
      props.fetchActiveAlerts(),
      loadWeatherData(),
      props.loadNotificationSettings()
    ]);
  } catch (error) {
    console.error('Failed to initialize alerts view:', error);
  }
};

const activeAlerts = computed(() => {
  return (alertsRef.value || []).map(alert => {
    const typeInfo = props.getAlertTypeInfo(alert.alert_type);
    return {
      id: alert.alert_id,
      title: typeInfo.label,
      description: alert.message,
      time: props.formatAlertTime(alert.issued_at),
      icon: typeInfo.icon,
      severity: typeInfo.color,
      farmName: alert.farm?.farm_name || 'Unknown Farm',
      alertType: alert.alert_type
    };
  });
});

const farmsList = computed(() => props.farms ?? []);
const forecastWarningsList = computed(() => forecastWarningsRef.value ?? []);
const settingsList = computed(() => notificationSettingsRef.value ?? {});

onMounted(async () => {
  await initializeData();
});

const dismissAlert = async (alertId) => {
  try {
    await props.resolveAlert(alertId);
  } catch (err) {
    console.error('Failed to dismiss alert:', err);
  }
};

const deleteAlertHandler = async (alertId) => {
  if (confirm('Are you sure you want to delete this alert?')) {
    try {
      await props.deleteAlert(alertId);
    } catch (err) {
      console.error('Failed to delete alert:', err);
    }
  }
};

const handleAlertCreated = async () => {
  try {
    await props.fetchActiveAlerts();
  } catch (err) {
    console.error('Failed to refresh alerts after creation:', err);
  }
};

const handleNotificationSettingChange = async (settingName, value) => {
  try {
    await props.updateNotificationSetting(settingName, value);
  } catch (err) {
    console.error('Failed to update setting:', err);
  }
};

const reloadActiveAlerts = async () => {
  try {
    await props.fetchActiveAlerts();
  } catch (error) {
    console.error('Failed to reload alerts:', error);
  }
};
</script>

<style scoped>
.alerts-view {
  width: 100%;
  margin: 0;
  padding: 0;
  overflow-y: auto;
  height: 100%;
  scrollbar-width: none;
  -ms-overflow-style: none;
}

.alerts-view::-webkit-scrollbar {
  display: none;
}

.alerts-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 30px;
}

.alerts-header h2 {
  color: white;
  font-size: 28px;
  display: flex;
  align-items: center;
  gap: 12px;
  margin: 0;
}

.alerts-header h2 i {
  color: #f59e0b;
}

.add-alert-button {
  background: rgba(59, 130, 246, 0.2);
  border: 1px solid rgba(59, 130, 246, 0.4);
  color: white;
  padding: 12px 24px;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  font-weight: 600;
}

.add-alert-button:hover {
  background: rgba(59, 130, 246, 0.4);
  transform: translateY(-2px);
}

.alerts-section, .warnings-section, .settings-section {
  background: rgba(0, 0, 0, 0.6);
  border-radius: 16px;
  padding: 25px;
  margin-bottom: 25px;
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.warnings-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.warnings-header h3 {
  color: white;
  font-size: 20px;
  margin: 0;
}
.warnings-header .warnings-location {
  font-size: 14px;
  color: #9ca3af;
  margin-left: 8px;
}

.refresh-button {
  background: rgba(59, 130, 246, 0.2);
  border: 1px solid rgba(59, 130, 246, 0.4);
  color: white;
  padding: 8px 16px;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  font-weight: 600;
}

.refresh-button:hover:not(:disabled) {
  background: rgba(59, 130, 246, 0.4);
  transform: translateY(-2px);
}

.refresh-button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  transform: none;
}

.alerts-section h3, .settings-section h3 {
  color: white;
  font-size: 20px;
  margin-bottom: 20px;
}

.loading-state, .error-state, .no-alerts {
  text-align: center;
  padding: 40px;
  color: #9ca3af;
}

.loading-state i, .error-state i, .no-alerts i {
  font-size: 48px;
  margin-bottom: 15px;
  display: block;
}

.loading-state i { color: #3b82f6; }
.error-state i { color: #ef4444; }
.no-alerts i { color: #22c55e; }

.retry-button {
  background: rgba(239, 68, 68, 0.2);
  border: 1px solid rgba(239, 68, 68, 0.4);
  color: white;
  padding: 8px 16px;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  margin-top: 15px;
}

.retry-button:hover {
  background: rgba(239, 68, 68, 0.4);
}

.alerts-list {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.alert-card {
  display: flex;
  align-items: flex-start;
  gap: 20px;
  padding: 20px;
  background: rgba(255, 255, 255, 0.05);
  border-radius: 12px;
  border-left: 4px solid;
  transition: all 0.3s ease;
}

.alert-card.warning {
  border-left-color: #f59e0b;
  background: rgba(245, 158, 11, 0.1);
}

.alert-card.danger {
  border-left-color: #ef4444;
  background: rgba(239, 68, 68, 0.1);
}

.alert-card:hover {
  transform: translateX(5px);
}

.alert-icon {
  font-size: 32px;
  color: #f59e0b;
  flex-shrink: 0;
}

.alert-content {
  flex: 1;
}

.alert-content h4 {
  color: white;
  margin: 0 0 10px 0;
  font-size: 18px;
}

.alert-content p {
  color: #d1d5db;
  margin: 0 0 10px 0;
  line-height: 1.5;
}

.alert-meta {
  display: flex;
  flex-direction: column;
  gap: 5px;
  margin-top: 10px;
}

.alert-farm, .alert-time {
  color: #9ca3af;
  font-size: 13px;
  display: flex;
  align-items: center;
  gap: 5px;
}

.alert-actions {
  display: flex;
  flex-direction: column;
  gap: 8px;
  flex-shrink: 0;
}

.dismiss-button, .delete-button {
  background: rgba(239, 68, 68, 0.2);
  border: 1px solid rgba(239, 68, 68, 0.3);
  color: white;
  width: 36px;
  height: 36px;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.dismiss-button {
  background: rgba(34, 197, 94, 0.2);
  border-color: rgba(34, 197, 94, 0.3);
}

.dismiss-button:hover {
  background: rgba(34, 197, 94, 0.4);
  transform: scale(1.1);
}

.delete-button:hover {
  background: rgba(239, 68, 68, 0.4);
  transform: scale(1.1);
}

.warnings-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 15px;
}

.warning-card {
  background: rgba(255, 255, 255, 0.05);
  border-radius: 12px;
  padding: 20px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.warning-card:hover {
  transform: translateY(-5px);
  border-color: rgba(245, 158, 11, 0.5);
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
}

.warning-card.warning {
  border-left: 4px solid #f59e0b;
  background: rgba(245, 158, 11, 0.1);
}

.warning-card.error {
  border-left: 4px solid #ef4444;
  background: rgba(239, 68, 68, 0.1);
}

.warning-card.info {
  border-left: 4px solid #3b82f6;
  background: rgba(59, 130, 246, 0.1);
}

.warning-header {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 12px;
  flex-wrap: wrap;
}

.warning-header i {
  font-size: 24px;
  color: #f59e0b;
  flex-shrink: 0;
}

.warning-day {
  color: white;
  font-weight: bold;
  font-size: 16px;
  flex: 1;
}

.warning-type {
  color: #9ca3af;
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  background: rgba(255, 255, 255, 0.1);
  padding: 2px 8px;
  border-radius: 4px;
}

.warning-text {
  color: #d1d5db;
  font-size: 14px;
  margin-bottom: 10px;
  line-height: 1.5;
}

.warning-value {
  color: #f59e0b;
  font-size: 20px;
  font-weight: bold;
  margin-bottom: 10px;
}

.warning-severity-badge {
  position: absolute;
  top: 10px;
  right: 10px;
  padding: 4px 8px;
  border-radius: 6px;
  font-size: 10px;
  font-weight: bold;
  letter-spacing: 0.5px;
}

.warning-severity-badge.warning {
  background: rgba(245, 158, 11, 0.2);
  color: #f59e0b;
  border: 1px solid rgba(245, 158, 11, 0.3);
}

.warning-severity-badge.error {
  background: rgba(239, 68, 68, 0.2);
  color: #ef4444;
  border: 1px solid rgba(239, 68, 68, 0.3);
}

.warning-severity-badge.info {
  background: rgba(59, 130, 246, 0.2);
  color: #3b82f6;
  border: 1px solid rgba(59, 130, 246, 0.3);
}

.settings-list {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.setting-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px;
  background: rgba(255, 255, 255, 0.05);
  border-radius: 12px;
  transition: all 0.3s ease;
}

.setting-item:hover {
  background: rgba(255, 255, 255, 0.08);
}

.setting-info {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  color: white;
  flex: 1;
}

.setting-info i {
  font-size: 20px;
  color: #60a5fa;
  width: 24px;
  flex-shrink: 0;
  margin-top: 2px;
}

.setting-text {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.setting-label {
  font-size: 15px;
  font-weight: 600;
  color: white;
}

.setting-description {
  font-size: 13px;
  color: #9ca3af;
  margin: 0;
  line-height: 1.4;
}

.success-state {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 16px;
  background: rgba(34, 197, 94, 0.2);
  border: 1px solid rgba(34, 197, 94, 0.3);
  color: #86efac;
  border-radius: 8px;
  margin-top: 15px;
  font-size: 14px;
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
  .alerts-view {
    padding: 0.75rem;
  }
  
  .alerts-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.9375rem;
    margin-bottom: 1.5rem;
  }

  .alerts-header h2 {
    font-size: 1.25rem;
  }

  .add-alert-button {
    width: 100%;
    justify-content: center;
    padding: 0.75rem 1rem;
    font-size: 0.8125rem;
  }
  
  .alerts-section, .warnings-section, .settings-section {
    padding: 1rem;
    margin-bottom: 1rem;
  }

  .alerts-section h3, .settings-section h3 {
    font-size: 1.125rem;
    margin-bottom: 1rem;
  }

  .warnings-header h3 {
    font-size: 1.125rem;
  }

  .warnings-grid {
    grid-template-columns: 1fr;
    gap: 0.75rem;
  }

  .warning-card {
    padding: 1rem;
  }

  .alert-card {
    padding: 1rem;
    gap: 0.75rem;
    flex-direction: column;
  }

  .alert-icon {
    font-size: 1.5rem;
  }

  .alert-content h4 {
    font-size: 1rem;
  }

  .alert-actions {
    flex-direction: row;
    width: 100%;
    justify-content: flex-end;
  }

  .settings-list {
    gap: 0.75rem;
  }

  .setting-item {
    padding: 0.75rem;
    flex-direction: row;
    align-items: center;
    gap: 0.75rem;
  }

  .setting-info {
    width: auto;
    flex: 1;
  }
}

/* Small Devices (landscape phones, 481px to 640px) */
@media (min-width: 481px) and (max-width: 640px) {
  .alerts-view {
    padding: 0.9rem;
  }

  .warnings-grid {
    grid-template-columns: 1fr;
  }

  .alert-card {
    flex-direction: row;
  }
}

/* Medium Devices (tablets, 641px to 768px) */
@media (min-width: 641px) and (max-width: 768px) {
  .alerts-view {
    padding: 1rem;
  }

  .warnings-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

/* Standard Mobile (up to 768px) */
@media (max-width: 768px) {
  .alerts-view {
    padding: 0.9rem;
  }
  
  .alerts-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.9375rem;
  }
  
  .warnings-grid {
    grid-template-columns: 1fr;
  }
  
  .setting-item {
    flex-direction: row;
    align-items: center;
  }
  
  .setting-info {
    flex: 1;
  }
}

/* Large Devices (desktops, 1024px and up) */
@media (min-width: 1024px) {
  .warnings-grid {
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  }
}

/* Extra Large Devices (large desktops, 1440px and up) */
@media (min-width: 1440px) {
  .alerts-view {
    padding: 2rem;
  }

  .alerts-section, .warnings-section, .settings-section {
    padding: 1.875rem;
  }
}

/* Zoom Support - Ensure proper scaling */
@media (min-resolution: 192dpi) {
  .alert-card {
    border-left-width: 3px;
  }

  .warning-card {
    border-left-width: 3px;
  }
}
</style>