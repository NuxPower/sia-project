<template>
  <div class="alerts-view">
    <div class="alerts-header">
      <h2>
        <i class="fas fa-bell"></i>
        Weather Alerts & Notifications
      </h2>
      <button class="add-alert-button" @click="showAddAlert = !showAddAlert">
        <i class="fas fa-plus"></i>
        New Alert
      </button>
    </div>

    <!-- Active Alerts -->
    <div class="alerts-section">
      <h3>Active Alerts</h3>
      <div v-if="activeAlerts.length === 0" class="no-alerts">
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
            <div class="alert-time">
              <i class="fas fa-clock"></i>
              {{ alert.time }}
            </div>
          </div>
          <button class="dismiss-button" @click="dismissAlert(alert.id)">
            <i class="fas fa-times"></i>
          </button>
        </div>
      </div>
    </div>

    <!-- Forecast Warnings -->
    <div class="warnings-section">
      <h3>Forecast Warnings</h3>
      <div class="warnings-grid">
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
      <div class="settings-list">
        <div class="setting-item">
          <div class="setting-info">
            <i class="fas fa-cloud-rain"></i>
            <span>Heavy Rain Alerts</span>
          </div>
          <label class="switch">
            <input type="checkbox" checked>
            <span class="slider"></span>
          </label>
        </div>
        <div class="setting-item">
          <div class="setting-info">
            <i class="fas fa-wind"></i>
            <span>Strong Wind Warnings</span>
          </div>
          <label class="switch">
            <input type="checkbox" checked>
            <span class="slider"></span>
          </label>
        </div>
        <div class="setting-item">
          <div class="setting-info">
            <i class="fas fa-temperature-high"></i>
            <span>Temperature Extremes</span>
          </div>
          <label class="switch">
            <input type="checkbox">
            <span class="slider"></span>
          </label>
        </div>
        <div class="setting-item">
          <div class="setting-info">
            <i class="fas fa-bolt"></i>
            <span>Storm Alerts</span>
          </div>
          <label class="switch">
            <input type="checkbox" checked>
            <span class="slider"></span>
          </label>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';

const showAddAlert = ref(false);

const activeAlerts = ref([
  {
    id: 1,
    title: 'Heavy Rain Warning',
    description: 'Heavy rainfall expected in the next 3-6 hours. Secure outdoor equipment and livestock.',
    time: '2 hours ago',
    icon: 'fas fa-cloud-showers-heavy',
    severity: 'warning'
  }
]);

const forecastWarnings = ref([
  {
    id: 1,
    day: 'Tomorrow',
    message: 'High wind speeds expected',
    value: '45 km/h',
    icon: 'fas fa-wind'
  },
  {
    id: 2,
    day: 'Wednesday',
    message: 'Heavy precipitation',
    value: '85mm',
    icon: 'fas fa-cloud-rain'
  },
  {
    id: 3,
    day: 'Friday',
    message: 'Temperature drop',
    value: '18°C',
    icon: 'fas fa-temperature-low'
  }
]);

const dismissAlert = (id) => {
  activeAlerts.value = activeAlerts.value.filter(alert => alert.id !== id);
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

.no-alerts {
  text-align: center;
  padding: 40px;
  color: #9ca3af;
}

.no-alerts i {
  font-size: 48px;
  color: #22c55e;
  margin-bottom: 15px;
  display: block;
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

.alert-time {
  color: #9ca3af;
  font-size: 13px;
  display: flex;
  align-items: center;
  gap: 5px;
}

.dismiss-button {
  background: rgba(239, 68, 68, 0.2);
  border: 1px solid rgba(239, 68, 68, 0.3);
  color: white;
  width: 36px;
  height: 36px;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
  flex-shrink: 0;
}

.dismiss-button:hover {
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
  align-items: center;
  gap: 12px;
  color: white;
  font-size: 15px;
}

.setting-info i {
  font-size: 20px;
  color: #60a5fa;
  width: 24px;
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