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
  top: 24px;
  left: 50%;
  transform: translateX(-50%);
  z-index: 9999;
  width: min(420px, calc(100% - 48px));
  pointer-events: none;
  display: flex;
  flex-direction: column;
  align-items: center;
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
  transform: translateY(0);
  transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
  pointer-events: auto;
  position: relative;
  overflow: hidden;
  cursor: pointer;
  width: 100%;
  animation: dropDown 0.35s ease-out;
}

.alert-notification:hover {
  transform: translateY(2px) scale(1.02);
  box-shadow: 0 12px 40px rgba(0, 0, 0, 0.4);
}

.alert-notification.alert-sliding-out {
  transform: translateY(-120%);
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

/* Responsive Design - Mobile First Approach */

/* Extra Small Devices (phones, up to 480px) */
@media (max-width: 480px) {
  .global-alerts-container {
    top: 0.5rem;
    left: 0.5rem;
    right: 0.5rem;
    transform: none;
    width: calc(100% - 1rem);
    max-width: none;
  }
  
  .alert-notification {
    padding: 0.75rem;
    margin-bottom: 0.5rem;
    border-radius: 0.5rem;
  }
  
  .alert-title {
    font-size: 0.8125rem;
  }
  
  .alert-message {
    font-size: 0.75rem;
  }

  .alert-close {
    width: 1.5rem;
    height: 1.5rem;
    font-size: 0.875rem;
  }
}

/* Small Devices (landscape phones, 481px to 640px) */
@media (min-width: 481px) and (max-width: 640px) {
  .global-alerts-container {
    top: 0.625rem;
    width: calc(100% - 1.5rem);
  }

  .alert-notification {
    padding: 0.875rem;
  }
}

/* Medium Devices (tablets, 641px to 768px) */
@media (min-width: 641px) and (max-width: 768px) {
  .global-alerts-container {
    top: 0.75rem;
    width: calc(100% - 2rem);
  }
}

/* Standard Mobile (up to 768px) */
@media (max-width: 768px) {
  .global-alerts-container {
    top: 0.625rem;
    left: 50%;
    transform: translateX(-50%);
    width: calc(100% - 1.5rem);
  }
  
  .alert-notification {
    padding: 0.75rem;
    margin-bottom: 0.5rem;
  }
  
  .alert-title {
    font-size: 0.8125rem;
  }
  
  .alert-message {
    font-size: 0.75rem;
  }
}

/* Large Devices (desktops, 1024px and up) */
@media (min-width: 1024px) {
  .global-alerts-container {
    max-width: 500px;
  }
}

/* Zoom Support - Ensure proper scaling */
@media (min-resolution: 192dpi) {
  .alert-notification {
    border-width: 1px;
  }
}

@keyframes dropDown {
  from {
    transform: translateY(-40%);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}
</style>
