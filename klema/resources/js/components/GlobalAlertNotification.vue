<template>
  <div v-if="isVisible && alerts.length > 0" class="global-alerts-container">
    <div 
      v-for="alert in alerts" 
      :key="alert.id"
      class="alert-notification"
      :class="[`alert-${alert.type}`, { 'alert-sliding-out': alert.slidingOut }]"
      @click="handleAlertClick(alert)"
    >
      <div class="alert-icon">
        <i :class="getAlertIcon(alert.type)"></i>
      </div>
      
      <div class="alert-content">
        <h4 class="alert-title">{{ alert.title }}</h4>
        <p class="alert-message">{{ alert.message }}</p>
      </div>
      
      <div class="alert-actions">
        <button 
          v-if="alert.action"
          class="alert-action-button"
          @click.stop="handleActionClick(alert)"
        >
          {{ alert.action.label }}
        </button>
        
        <button 
          class="alert-close-button"
          @click.stop="removeAlert(alert.id)"
          title="Dismiss"
        >
          <i class="fas fa-times"></i>
        </button>
      </div>
      
      <!-- Progress bar for auto-dismiss -->
      <div v-if="!alert.persistent" class="alert-progress">
        <div 
          class="alert-progress-bar"
          :style="{ animationDuration: `${alert.duration}ms` }"
        ></div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useGlobalAlerts } from '../composables/useGlobalAlerts'

const { alerts, isVisible, removeAlert } = useGlobalAlerts()

const getAlertIcon = (type) => {
  const icons = {
    success: 'fas fa-check-circle',
    error: 'fas fa-exclamation-circle',
    warning: 'fas fa-exclamation-triangle',
    info: 'fas fa-info-circle'
  }
  return icons[type] || icons.info
}

const handleAlertClick = (alert) => {
  // Optional: Handle alert click (e.g., navigate to relevant page)
  console.log('Alert clicked:', alert)
}

const handleActionClick = (alert) => {
  if (alert.action && alert.action.handler) {
    alert.action.handler()
  }
}
</script>

<style scoped>
.global-alerts-container {
  position: fixed;
  top: 20px;
  right: 20px;
  z-index: 9999;
  max-width: 400px;
  pointer-events: none;
}

.alert-notification {
  background: rgba(0, 0, 0, 0.9);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 12px;
  padding: 16px;
  margin-bottom: 12px;
  display: flex;
  align-items: flex-start;
  gap: 12px;
  backdrop-filter: blur(15px);
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
  transform: translateX(0);
  transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
  pointer-events: auto;
  position: relative;
  overflow: hidden;
  cursor: pointer;
}

.alert-notification:hover {
  transform: translateX(-5px) scale(1.02);
  box-shadow: 0 12px 40px rgba(0, 0, 0, 0.4);
}

.alert-notification.alert-sliding-out {
  transform: translateX(100%);
  opacity: 0;
}

/* Alert type styles */
.alert-success {
  border-left: 4px solid #10b981;
}

.alert-success .alert-icon {
  color: #10b981;
}

.alert-error {
  border-left: 4px solid #ef4444;
}

.alert-error .alert-icon {
  color: #ef4444;
}

.alert-warning {
  border-left: 4px solid #f59e0b;
}

.alert-warning .alert-icon {
  color: #f59e0b;
}

.alert-info {
  border-left: 4px solid #3b82f6;
}

.alert-info .alert-icon {
  color: #3b82f6;
}

.alert-icon {
  font-size: 20px;
  margin-top: 2px;
  flex-shrink: 0;
}

.alert-content {
  flex: 1;
  min-width: 0;
}

.alert-title {
  color: white;
  font-size: 14px;
  font-weight: 600;
  margin: 0 0 4px 0;
  line-height: 1.3;
}

.alert-message {
  color: rgba(255, 255, 255, 0.8);
  font-size: 13px;
  margin: 0;
  line-height: 1.4;
}

.alert-actions {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
}

.alert-action-button {
  background: rgba(59, 130, 246, 0.2);
  border: 1px solid rgba(59, 130, 246, 0.3);
  color: #3b82f6;
  padding: 4px 8px;
  border-radius: 6px;
  font-size: 11px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.alert-action-button:hover {
  background: rgba(59, 130, 246, 0.3);
  border-color: rgba(59, 130, 246, 0.5);
}

.alert-close-button {
  background: transparent;
  border: none;
  color: rgba(255, 255, 255, 0.5);
  padding: 4px;
  border-radius: 4px;
  cursor: pointer;
  transition: all 0.2s ease;
  font-size: 12px;
}

.alert-close-button:hover {
  color: white;
  background: rgba(255, 255, 255, 0.1);
}

.alert-progress {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  height: 2px;
  background: rgba(255, 255, 255, 0.1);
}

.alert-progress-bar {
  height: 100%;
  background: linear-gradient(90deg, #3b82f6, #10b981);
  width: 100%;
  transform-origin: left;
  animation: progress-bar linear forwards;
}

@keyframes progress-bar {
  from {
    transform: scaleX(1);
  }
  to {
    transform: scaleX(0);
  }
}

/* Mobile responsive */
@media (max-width: 768px) {
  .global-alerts-container {
    top: 10px;
    right: 10px;
    left: 10px;
    max-width: none;
  }
  
  .alert-notification {
    padding: 12px;
    margin-bottom: 8px;
  }
  
  .alert-title {
    font-size: 13px;
  }
  
  .alert-message {
    font-size: 12px;
  }
}

/* Animation for new alerts */
.alert-notification {
  animation: slideInRight 0.3s ease-out;
}

@keyframes slideInRight {
  from {
    transform: translateX(100%);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}
</style>
