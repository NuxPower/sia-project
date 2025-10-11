export function useWeatherUtils() {
  const getDayLabel = (day) => {
    if (!day.date) return day.day || 'Unknown';
    
    const today = new Date();
    const tomorrow = new Date(today);
    tomorrow.setDate(tomorrow.getDate() + 1);
    
    const dayDate = new Date(day.date);
    const todayStr = today.toISOString().split('T')[0];
    const tomorrowStr = tomorrow.toISOString().split('T')[0];
    const dayStr = day.date;
    
    if (dayStr === todayStr) return 'Today';
    if (dayStr === tomorrowStr) return 'Tomorrow';
    
    const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    return dayNames[dayDate.getDay()];
  };
  
  const getWeatherIcon = (condition) => {
    const iconMap = {
      'Clear': 'fas fa-sun',
      'Clouds': 'fas fa-cloud',
      'Rain': 'fas fa-cloud-rain',
      'Drizzle': 'fas fa-cloud-drizzle',
      'Thunderstorm': 'fas fa-bolt',
      'Snow': 'fas fa-snowflake',
      'Mist': 'fas fa-smog',
      'Fog': 'fas fa-smog',
      'Haze': 'fas fa-smog'
    };
    return iconMap[condition] || 'fas fa-cloud';
  };
  
  return {
    getDayLabel,
    getWeatherIcon
  };
}