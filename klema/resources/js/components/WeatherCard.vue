<template>
  <div 
    class="weather-card"
    :class="cardClass"
    @mouseenter="handleMouseEnter"
    @mouseleave="handleMouseLeave"
  >    
    <div class="day-label">{{ getDayLabel(day) }}</div>
    
    <div class="date">
      {{ formatDate(day.date) }}
    </div>
    
    <div class="weather-icon">
      <i :class="getWeatherIcon(day.condition)"></i>
    </div>
    
    <div class="temperature">
      <span class="temp-high">H: {{ day.temp_max }}°</span>
      <span class="temp-low">L: {{ day.temp_min }}°</span>
    </div>
    
    <div class="condition">{{ day.condition || '' }}</div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  day: Object,
  getDayLabel: Function,
  getWeatherIcon: Function
});

const cardClass = computed(() => ({
  'history': props.day.isHistory,
  'today': props.day.isToday,
  'future': props.day.isFuture
}));

const formatDate = (date) => {
  if (!date) return '';
  return new Date(date).toLocaleDateString('en-PH', { 
    month: 'short', 
    day: 'numeric' 
  });
};

const handleMouseEnter = (e) => {
  if (!props.day.isToday) {
    e.currentTarget.style.transform = 'scale(1.02)';
  }
};

const handleMouseLeave = (e) => {
  if (!props.day.isToday) {
    e.currentTarget.style.transform = 'scale(1)';
  }
};
</script>

<style scoped>
.weather-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  color: white;
  min-width: 75px;
  padding: 10px 8px;
  border-radius: 10px;
  transition: all 0.3s ease;
  cursor: pointer;
  position: relative;
}

.weather-card.history {
  background: rgba(156, 163, 175, 0.2);
  border: 1px solid rgba(156, 163, 175, 0.3);
  opacity: 0.7;
}

.weather-card.today {
  background: rgba(59, 130, 246, 0.3);
  border: 2px solid rgba(59, 130, 246, 0.6);
  transform: scale(1.05);
  box-shadow: 0 4px 16px rgba(59, 130, 246, 0.3);
}

.weather-card.future {
  background: rgba(34, 197, 94, 0.2);
  border: 1px solid rgba(34, 197, 94, 0.3);
}

.timeline-indicator {
  position: absolute;
  top: -8px;
  left: 50%;
  transform: translateX(-50%);
  font-size: 10px;
  background: rgba(0, 0, 0, 0.8);
  padding: 2px 6px;
  border-radius: 4px;
}

.day-label {
  font-size: 12px;
  margin-bottom: 6px;
  margin-top: 8px;
  font-weight: bold;
  text-align: center;
}

.date {
  font-size: 9px;
  margin-bottom: 8px;
  color: #6b7280;
  text-align: center;
}

.weather-icon {
  font-size: 20px;
  margin-bottom: 8px;
  filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.5));
}

.temperature {
  display: flex;
  flex-direction: column;
  align-items: center;
  font-size: 11px;
}

.temp-high {
  font-weight: bold;
  color: #ffffff;
  margin-bottom: 2px;
}

.temp-low {
  color: #9ca3af;
  font-size: 10px;
}

.condition {
  font-size: 8px;
  margin-top: 4px;
  color: #6b7280;
  text-align: center;
  max-width: 60px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
</style>