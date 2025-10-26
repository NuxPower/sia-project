export function useWeatherUtils() {
  const getDayLabel = (day) => {
    if (!day.date) return day.day || 'Unknown';
    
    const today = new Date();
    const tomorrow = new Date(today);
    tomorrow.setDate(tomorrow.getDate() + 1);
    
    // Helper function to format date in local timezone (avoiding UTC conversion issues)
    const formatLocalDate = (date) => {
      const year = date.getFullYear();
      const month = String(date.getMonth() + 1).padStart(2, '0');
      const day = String(date.getDate()).padStart(2, '0');
      return `${year}-${month}-${day}`;
    };
    
    const dayDate = new Date(day.date);
    const todayStr = formatLocalDate(today);
    const tomorrowStr = formatLocalDate(tomorrow);
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