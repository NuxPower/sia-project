<template>
  <div 
    class="weather-card"
    :class="[cardClass, { 'compact': isCompact }]"
    @mouseenter="handleMouseEnter"
    @mouseleave="handleMouseLeave"
    @click="$emit('select', day)"
  >    
    <div class="day-label">{{ getDayLabel(day) }}</div>
    
    <div v-if="!isCompact" class="date">
      {{ formatDate(day.date) }}
    </div>
    
    <div class="weather-icon">
      <i :class="resolvedIcon"></i>
    </div>
    
    <div class="temperature">
      <span class="temp-high">H: {{ day.temp_max }}°</span>
      <span class="temp-low">L: {{ day.temp_min }}°</span>
    </div>
    
    <div v-if="!isCompact" class="condition">{{ day.condition || '' }}</div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  day: Object,
  getDayLabel: Function,
  getWeatherIcon: Function,
  isCompact: {
    type: Boolean,
    default: false
  }
});

const cardClass = computed(() => ({
  'history': props.day.isHistory,
  'today': props.day.isToday,
  'future': props.day.isFuture
}));

const resolvedIcon = computed(() => {
  if (!props.day) {
    return props.getWeatherIcon({});
  }

  if (props.day.isHistory) {
    return props.getWeatherIcon({
      condition: props.day.condition ?? props.day.description ?? props.day.day ?? ''
    });
  }

  return props.getWeatherIcon(props.day);
});

const formatDate = (date) => {
  if (!date) return '';
  
  // Parse date string as local date to avoid timezone issues
  // Split "2025-10-12" into year, month, day
  const parts = date.split('-');
  const year = parseInt(parts[0], 10);
  const month = parseInt(parts[1], 10);
  const day = parseInt(parts[2], 10);
  
  // Create date in local timezone (month is 0-indexed)
  const dateObj = new Date(year, month - 1, day);
  
  return dateObj.toLocaleDateString('en-PH', { 
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

defineEmits(['select']);
</script>

<style scoped>
.weather-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  color: white;
  min-width: 85px;
  padding: 12px 10px;
  border-radius: 14px;
  transition: all 0.3s ease;
  cursor: pointer;
  position: relative;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(10px);
}

.weather-card.compact {
  padding: 10px 8px;
  min-width: 70px;
  border-radius: 12px;
}

.weather-card.history {
  background: rgba(156, 163, 175, 0.15);
  border: 1px solid rgba(156, 163, 175, 0.25);
  opacity: 0.75;
}

.weather-card.today {
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.35), rgba(37, 99, 235, 0.25));
  border: 2px solid rgba(59, 130, 246, 0.7);
  transform: scale(1.08);
  box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4), 0 0 0 2px rgba(59, 130, 246, 0.2);
}

.weather-card.future {
  background: rgba(34, 197, 94, 0.15);
  border: 1px solid rgba(34, 197, 94, 0.3);
}

.weather-card:hover:not(.today) {
  background: rgba(255, 255, 255, 0.1);
  border-color: rgba(59, 130, 246, 0.4);
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
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
  font-size: 13px;
  margin-bottom: 8px;
  margin-top: 4px;
  font-weight: 600;
  text-align: center;
  color: #e2e8f0;
  letter-spacing: 0.3px;
}

.compact .day-label {
  font-size: 10px;
  margin-bottom: 4px;
  margin-top: 4px;
}

.date {
  font-size: 10px;
  margin-bottom: 10px;
  color: #94a3b8;
  text-align: center;
  font-weight: 500;
}

.weather-icon {
  font-size: 24px;
  margin-bottom: 10px;
  filter: drop-shadow(0 2px 6px rgba(0, 0, 0, 0.4));
  transition: transform 0.3s ease;
}

.weather-card:hover .weather-icon {
  transform: scale(1.1);
}

.compact .weather-icon {
  font-size: 16px;
  margin-bottom: 6px;
}

.temperature {
  display: flex;
  flex-direction: column;
  align-items: center;
  font-size: 12px;
  gap: 2px;
}

.compact .temperature {
  font-size: 10px;
}

.temp-high {
  font-weight: 700;
  color: #ffffff;
  font-size: 13px;
}

.temp-low {
  color: #94a3b8;
  font-size: 11px;
  font-weight: 500;
}

.compact .temp-low {
  font-size: 9px;
}

.condition {
  font-size: 9px;
  margin-top: 6px;
  color: #94a3b8;
  text-align: center;
  max-width: 70px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  font-weight: 500;
  text-transform: capitalize;
}

@media (max-width: 768px) {
  .weather-card {
    min-width: 70px;
    padding: 10px 8px;
    flex-shrink: 0;
  }
  
  .day-label {
    font-size: 11px;
  }
  
  .date {
    font-size: 9px;
    margin-bottom: 8px;
  }
  
  .weather-icon {
    font-size: 20px;
    margin-bottom: 8px;
  }
  
  .temperature {
    font-size: 11px;
  }
  
  .temp-high {
    font-size: 12px;
  }
  
  .temp-low {
    font-size: 10px;
  }
  
  .condition {
    font-size: 8px;
    max-width: 65px;
  }
}

@media (max-width: 480px) {
  .weather-card {
    min-width: 65px;
    padding: 8px 6px;
    flex-shrink: 0;
  }
  
  .weather-card.compact {
    min-width: 60px;
    padding: 8px 6px;
  }
  
  .day-label {
    font-size: 10px;
    margin-bottom: 6px;
    margin-top: 2px;
  }
  
  .compact .day-label {
    font-size: 9px;
  }
  
  .date {
    font-size: 8px;
    margin-bottom: 6px;
  }
  
  .weather-icon {
    font-size: 18px;
    margin-bottom: 6px;
  }
  
  .compact .weather-icon {
    font-size: 16px;
    margin-bottom: 4px;
  }
  
  .temperature {
    font-size: 10px;
  }
  
  .compact .temperature {
    font-size: 9px;
  }
  
  .temp-high {
    font-size: 11px;
  }
  
  .temp-low {
    font-size: 9px;
  }
  
  .compact .temp-low {
    font-size: 8px;
  }
  
  .condition {
    font-size: 8px;
    max-width: 60px;
  }
}
</style>