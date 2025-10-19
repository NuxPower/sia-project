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
      
      <!-- Loading State -->
      <div v-if="loading" class="loading-state">
        <i class="fas fa-spinner fa-spin"></i>
        <p>Loading alerts...</p>
      </div>
      
      <!-- Error State -->
      <div v-else-if="error" class="error-state">
        <i class="fas fa-exclamation-triangle"></i>
        <p>{{ error }}</p>
        <button @click="fetchActiveAlerts()" class="retry-button">
          <i class="fas fa-redo"></i>
          Retry
        </button>
      </div>
      
      <!-- No Alerts State -->
      <div v-else-if="activeAlerts.length === 0" class="no-alerts">
        <i class="fas fa-check-circle"></i>
        <p>No active weather alerts at this time</p>
      </div>
      
      <!-- Alerts List -->
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
      <h3>Forecast Warnings</h3>
      
      <!-- Loading State -->
      <div v-if="loading && forecastWarnings.length === 0" class="loading-state">
        <i class="fas fa-spinner fa-spin"></i>
        <p>Loading forecast warnings...</p>
      </div>
      
      <!-- No Warnings State -->
      <div v-else-if="forecastWarnings.length === 0" class="no-alerts">
        <i class="fas fa-sun"></i>
        <p>No forecast warnings at this time</p>
      </div>
      
      <!-- Warnings Grid -->
      <div v-else class="warnings-grid">
        <div 
          v-for="warning in forecastWarnings" 
          :key="warning.id"
          class="warning-card"
        >
          <div class="warning-header">
            <i :class="warning.icon"></i>
            <span>{{ warning.day }}</span>
          </div>
          <p class="warning-text">{{ warning.message }}</p>
          <div class="warning-value">{{ warning.value }}</div>
        </div>
      </div>
    </div>

    <!-- Notification Settings -->
    <div class="settings-section">
      <h3>Notification Preferences</h3>
      
      <!-- Loading State -->
      <div v-if="settingsLoading" class="loading-state">
        <i class="fas fa-spinner fa-spin"></i>
        <p>Loading notification settings...</p>
      </div>
      
      <!-- Settings List -->
      <div v-else class="settings-list">
        <div 
          v-for="(value, settingName) in notificationSettings" 
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
              @change="updateNotificationSetting(settingName, $event.target.checked)"
            >
            <span class="slider"></span>
          </label>
        </div>
      </div>
      
      <!-- Save Status -->
      <div v-if="settingsError" class="error-state">
        <i class="fas fa-exclamation-triangle"></i>
        {{ settingsError }}
      </div>
      
      <div v-if="settingsSaved" class="success-state">
        <i class="fas fa-check-circle"></i>
        Settings saved successfully!
      </div>
    </div>

    <!-- Create Alert Modal -->
    <CreateAlertModal 
      :show="showCreateModal" 
      @close="showCreateModal = false"
      @alert-created="handleAlertCreated"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useAlerts } from '../../composables/useAlerts';
import { useNotificationSettings } from '../../composables/useNotificationSettings';
import CreateAlertModal from '../CreateAlertModal.vue';

const { 
  alerts, 
  forecastWarnings, 
  loading, 
  error, 
  fetchActiveAlerts, 
  fetchForecastWarnings, 
  resolveAlert, 
  deleteAlert,
  getAlertTypeInfo,
  formatAlertTime
} = useAlerts();

const { 
  settings: notificationSettings,
  loading: settingsLoading,
  error: settingsError,
  loadSettings,
  updateSetting,
  getSettingInfo
} = useNotificationSettings();

const showCreateModal = ref(false);
const settingsSaved = ref(false);

// Computed property to get active alerts with formatted data
const activeAlerts = computed(() => {
  return alerts.value.map(alert => {
    const typeInfo = getAlertTypeInfo(alert.alert_type);
    return {
      id: alert.alert_id,
      title: typeInfo.label,
      description: alert.message,
      time: formatAlertTime(alert.issued_at),
      icon: typeInfo.icon,
      severity: typeInfo.color,
      farmName: alert.farm?.farm_name || 'Unknown Farm',
      alertType: alert.alert_type
    };
  });
});

// Load data when component mounts
onMounted(async () => {
  await Promise.all([
    fetchActiveAlerts(),
    fetchForecastWarnings(),
    loadSettings()
  ]);
});

const dismissAlert = async (alertId) => {
  try {
    await resolveAlert(alertId);
  } catch (err) {
    console.error('Failed to dismiss alert:', err);
  }
};

const deleteAlertHandler = async (alertId) => {
  if (confirm('Are you sure you want to delete this alert?')) {
    try {
      await deleteAlert(alertId);
    } catch (err) {
      console.error('Failed to delete alert:', err);
    }
  }
};

const handleAlertCreated = (newAlert) => {
  // The alert will be automatically added to the list by the composable
  // You could also show a success message here
  console.log('Alert created:', newAlert);
};

const updateNotificationSetting = async (settingName, value) => {
  try {
    await updateSetting(settingName, value);
    settingsSaved.value = true;
    
    // Hide success message after 3 seconds
    setTimeout(() => {
      settingsSaved.value = false;
    }, 3000);
  } catch (err) {
    console.error('Failed to update notification setting:', err);
  }
};
</script>

<style scoped>
.alerts-view {
  padding: 40px;
  max-width: 1400px;
  margin: 0 auto;
  overflow-y: auto;
  height: 100vh;
  
  /* Hide scrollbar */
  scrollbar-width: none; /* Firefox */
  -ms-overflow-style: none; /* IE and Edge */
}

.alerts-view::-webkit-scrollbar {
  display: none; /* Chrome, Safari, Opera */
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

.alerts-section h3, .warnings-section h3, .settings-section h3 {
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

.loading-state i {
  color: #3b82f6;
}

.error-state i {
  color: #ef4444;
}

.no-alerts i {
  color: #22c55e;
}

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
}

.warning-card:hover {
  transform: translateY(-5px);
  border-color: rgba(245, 158, 11, 0.5);
}

.warning-header {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 12px;
}

.warning-header i {
  font-size: 24px;
  color: #f59e0b;
}

.warning-header span {
  color: white;
  font-weight: bold;
  font-size: 16px;
}

.warning-text {
  color: #d1d5db;
  font-size: 14px;
  margin-bottom: 10px;
}

.warning-value {
  color: #f59e0b;
  font-size: 20px;
  font-weight: bold;
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

@media (max-width: 768px) {
  .alerts-view {
    padding: 20px;
  }
  
  .alerts-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 15px;
  }
  
  .warnings-grid {
    grid-template-columns: 1fr;
  }
}
</style>