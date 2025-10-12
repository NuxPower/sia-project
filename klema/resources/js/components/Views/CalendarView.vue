<template>
  <div class="calendar-view">
    <div class="calendar-header">
      <button @click="previousMonth" class="nav-button">
        <i class="fas fa-chevron-left"></i>
      </button>
      <h2>{{ currentMonthYear }}</h2>
      <button @click="nextMonth" class="nav-button">
        <i class="fas fa-chevron-right"></i>
      </button>
    </div>

    <div class="calendar-grid">
      <div class="calendar-weekdays">
        <div v-for="day in weekdays" :key="day" class="weekday">{{ day }}</div>
      </div>
      
      <div class="calendar-days">
        <div
          v-for="(day, index) in calendarDays"
          :key="index"
          class="calendar-day"
          :class="{
            'other-month': !day.currentMonth,
            'today': day.isToday,
            'has-weather': day.weather
          }"
        >
          <div class="day-number">{{ day.date }}</div>
          <div v-if="day.weather" class="day-weather">
            <i :class="getWeatherIcon(day.weather.condition)"></i>
            <span class="day-temp">{{ day.weather.temp }}°</span>
          </div>
        </div>
      </div>
    </div>

    <div class="legend">
      <div class="legend-item">
        <div class="legend-dot today"></div>
        <span>Today</span>
      </div>
      <div class="legend-item">
        <div class="legend-dot has-forecast"></div>
        <span>Has Forecast</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';

const props = defineProps({
  forecast: Array,
  getWeatherIcon: Function
});

const currentDate = ref(new Date());
const weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

const currentMonthYear = computed(() => {
  return currentDate.value.toLocaleDateString('en-US', { 
    month: 'long', 
    year: 'numeric' 
  });
});

const calendarDays = computed(() => {
  const year = currentDate.value.getFullYear();
  const month = currentDate.value.getMonth();
  
  const firstDay = new Date(year, month, 1);
  const lastDay = new Date(year, month + 1, 0);
  const startDay = firstDay.getDay();
  const daysInMonth = lastDay.getDate();
  
  const days = [];
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  
  // Previous month days
  const prevMonthLastDay = new Date(year, month, 0).getDate();
  for (let i = startDay - 1; i >= 0; i--) {
    days.push({
      date: prevMonthLastDay - i,
      currentMonth: false,
      isToday: false,
      weather: null
    });
  }
  
  // Current month days
  for (let i = 1; i <= daysInMonth; i++) {
    const dayDate = new Date(year, month, i);
    dayDate.setHours(0, 0, 0, 0);
    
    const dateStr = dayDate.toISOString().split('T')[0];
    const weatherData = props.forecast?.find(f => f.date === dateStr);
    
    days.push({
      date: i,
      currentMonth: true,
      isToday: dayDate.getTime() === today.getTime(),
      weather: weatherData ? {
        condition: weatherData.condition,
        temp: weatherData.temp_max
      } : null
    });
  }
  
  // Next month days
  const remainingDays = 42 - days.length; // 6 rows * 7 days
  for (let i = 1; i <= remainingDays; i++) {
    days.push({
      date: i,
      currentMonth: false,
      isToday: false,
      weather: null
    });
  }
  
  return days;
});

const previousMonth = () => {
  currentDate.value = new Date(
    currentDate.value.getFullYear(),
    currentDate.value.getMonth() - 1,
    1
  );
};

const nextMonth = () => {
  currentDate.value = new Date(
    currentDate.value.getFullYear(),
    currentDate.value.getMonth() + 1,
    1
  );
};
</script>

<style scoped>
.calendar-view {
  padding: 40px;
  max-width: 1200px;
  margin: 0 auto;
  height: 100vh;
  overflow-y: auto;
  
  /* Hide scrollbar */
  scrollbar-width: none; /* Firefox */
  -ms-overflow-style: none; /* IE and Edge */
}

.calendar-view::-webkit-scrollbar {
  display: none; /* Chrome, Safari, Opera */
}

.calendar-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 30px;
  background: rgba(0, 0, 0, 0.6);
  padding: 20px 30px;
  border-radius: 16px;
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.calendar-header h2 {
  color: white;
  font-size: 28px;
  margin: 0;
}

.nav-button {
  background: rgba(59, 130, 246, 0.2);
  border: 1px solid rgba(59, 130, 246, 0.4);
  color: white;
  width: 44px;
  height: 44px;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.3s ease;
  font-size: 16px;
}

.nav-button:hover {
  background: rgba(59, 130, 246, 0.4);
  transform: scale(1.1);
}

.calendar-grid {
  background: rgba(0, 0, 0, 0.6);
  border-radius: 16px;
  padding: 20px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  margin-bottom: 20px;
}

.calendar-weekdays {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 10px;
  margin-bottom: 15px;
}

.weekday {
  color: #9ca3af;
  font-weight: bold;
  font-size: 14px;
  text-align: center;
  padding: 10px;
}

.calendar-days {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 10px;
}

.calendar-day {
  aspect-ratio: 1;
  background: rgba(255, 255, 255, 0.05);
  border-radius: 12px;
  padding: 10px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  border: 1px solid rgba(255, 255, 255, 0.05);
  transition: all 0.3s ease;
  cursor: pointer;
}

.calendar-day:hover {
  background: rgba(255, 255, 255, 0.1);
  transform: scale(1.05);
}

.calendar-day.other-month {
  opacity: 0.3;
}

.calendar-day.today {
  background: rgba(59, 130, 246, 0.3);
  border: 2px solid rgba(59, 130, 246, 0.6);
}

.calendar-day.has-weather {
  border-color: rgba(34, 197, 94, 0.4);
}

.day-number {
  color: white;
  font-weight: bold;
  font-size: 16px;
}

.day-weather {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 5px;
  margin-top: 5px;
}

.day-weather i {
  font-size: 24px;
  color: #60a5fa;
}

.day-temp {
  color: white;
  font-size: 14px;
  font-weight: bold;
}

.legend {
  display: flex;
  justify-content: center;
  gap: 30px;
  padding: 20px;
  background: rgba(0, 0, 0, 0.4);
  border-radius: 12px;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 8px;
  color: white;
  font-size: 14px;
}

.legend-dot {
  width: 16px;
  height: 16px;
  border-radius: 4px;
}

.legend-dot.today {
  background: rgba(59, 130, 246, 0.6);
  border: 2px solid #3b82f6;
}

.legend-dot.has-forecast {
  background: rgba(34, 197, 94, 0.3);
  border: 2px solid rgba(34, 197, 94, 0.6);
}

@media (max-width: 768px) {
  .calendar-view {
    padding: 20px;
  }
  
  .calendar-header h2 {
    font-size: 20px;
  }
  
  .day-number {
    font-size: 12px;
  }
  
  .day-weather i {
    font-size: 18px;
  }
  
  .day-temp {
    font-size: 11px;
  }
}
</style>