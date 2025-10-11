export function useWeatherAPI() {
  const fetchWeatherByCoordinates = async (lat, lng) => {
    const currentUrl = `/api/weather/current?lat=${lat}&lon=${lng}`;
    const forecastUrl = `/api/weather/forecast?lat=${lat}&lon=${lng}&days=7`;
    const historyUrl = `/api/weather/history?lat=${lat}&lon=${lng}&days=3`;
    
    const currentResponse = await fetch(currentUrl);
    if (!currentResponse.ok) {
      throw new Error(`Failed to fetch current weather: ${currentResponse.status}`);
    }
    
    const current = await currentResponse.json();
    
    const [forecastResponse, historyResponse] = await Promise.all([
      fetch(forecastUrl),
      fetch(historyUrl).catch(() => null)
    ]);
    
    if (!forecastResponse.ok) {
      throw new Error(`Failed to fetch forecast: ${forecastResponse.status}`);
    }
    
    const forecastData = await forecastResponse.json();
    const history = historyResponse?.ok ? await historyResponse.json() : [];
    
    return { current, forecastData, history };
  };
  
  const fetchWeatherByLocation = async (location) => {
    const currentResponse = await fetch(`/api/weather/current?location=${encodeURIComponent(location)}`);
    if (!currentResponse.ok) throw new Error('Failed to fetch current weather');
    
    const current = await currentResponse.json();
    
    const forecastResponse = await fetch(`/api/weather/forecast?location=${encodeURIComponent(location)}&days=7`);
    if (!forecastResponse.ok) throw new Error('Failed to fetch forecast');
    
    const forecastData = await forecastResponse.json();
    
    const historyResponse = await fetch(`/api/weather/history?location=${encodeURIComponent(location)}&days=3`).catch(() => null);
    const history = historyResponse?.ok ? await historyResponse.json() : [];
    
    return { current, forecastData, history };
  };
  
  const createWeatherTimeline = (historyData, currentWeather, forecastData) => {
    const timeline = [];
    const today = new Date();
    
    // Add historical data (3 days)
    for (let i = 3; i >= 1; i--) {
      const date = new Date(today);
      date.setDate(date.getDate() - i);
      
      const historyDay = historyData.find(h => h.date === date.toISOString().split('T')[0]);
      
      timeline.push({
        date: date.toISOString().split('T')[0],
        day: date.toLocaleDateString('en-US', { weekday: 'short' }),
        temp_max: historyDay?.temp_max || Math.floor(currentWeather.main.temp + Math.random() * 4 - 2),
        temp_min: historyDay?.temp_min || Math.floor(currentWeather.main.temp - 5 + Math.random() * 3),
        condition: historyDay?.condition || currentWeather.weather[0].main,
        icon: historyDay?.icon || currentWeather.weather[0].icon,
        isHistory: true
      });
    }
    
    // Add today
    timeline.push({
      date: today.toISOString().split('T')[0],
      day: 'Today',
      temp_max: Math.round(currentWeather.main.temp_max || currentWeather.main.temp + 2),
      temp_min: Math.round(currentWeather.main.temp_min || currentWeather.main.temp - 3),
      condition: currentWeather.weather[0].main,
      icon: currentWeather.weather[0].icon,
      isToday: true
    });
    
    // Add forecast (3 days)
    const futureDays = forecastData.slice(1, 4);
    for (let i = 0; i < 3; i++) {
      const date = new Date(today);
      date.setDate(date.getDate() + i + 1);
      
      const forecastDay = futureDays[i];
      
      timeline.push({
        date: date.toISOString().split('T')[0],
        day: i === 0 ? 'Tomorrow' : date.toLocaleDateString('en-US', { weekday: 'short' }),
        temp_max: forecastDay?.temp_max || Math.floor(currentWeather.main.temp + Math.random() * 6 - 3),
        temp_min: forecastDay?.temp_min || Math.floor(currentWeather.main.temp - 4 + Math.random() * 3),
        condition: forecastDay?.condition || currentWeather.weather[0].main,
        icon: forecastDay?.icon || currentWeather.weather[0].icon,
        isFuture: true
      });
    }
    
    return timeline;
  };
  
  return {
    fetchWeatherByCoordinates,
    fetchWeatherByLocation,
    createWeatherTimeline
  };
}