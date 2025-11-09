import { authorizedFetch } from '../services/http';

export function useWeatherAPI() {
  const fetchWeatherByCoordinates = async (lat, lng, options = {}) => {
    const days = Math.max(1, Math.min(options.days ?? 7, 16));
    const historyDays = Math.max(1, Math.min(options.historyDays ?? 3, 10));
    const includeHistory = options.includeHistory ?? true;

    const currentUrl = `/api/weather/current?lat=${lat}&lon=${lng}`;
    const forecastUrl = `/api/weather/forecast?lat=${lat}&lon=${lng}&days=${days}`;
    const historyUrl = includeHistory ? `/api/weather/history?lat=${lat}&lon=${lng}&days=${historyDays}` : null;
    
    const currentResponse = await authorizedFetch(currentUrl);
    if (!currentResponse.ok) {
      throw new Error(`Failed to fetch current weather: ${currentResponse.status}`);
    }
    
    const current = await currentResponse.json();
    
    const forecastPromise = authorizedFetch(forecastUrl);
    const historyPromise = includeHistory ? authorizedFetch(historyUrl).catch(() => null) : Promise.resolve(null);
    
    const [forecastResponse, historyResponse] = await Promise.all([forecastPromise, historyPromise]);
    
    if (!forecastResponse.ok) {
      throw new Error(`Failed to fetch forecast: ${forecastResponse.status}`);
    }
    
    const forecastData = await forecastResponse.json();
    const history = includeHistory && historyResponse?.ok ? await historyResponse.json() : [];
    
    return { current, forecastData, history };
  };
  
  const fetchWeatherByLocation = async (location, options = {}) => {
    const days = Math.max(1, Math.min(options.days ?? 7, 16));
    const historyDays = Math.max(1, Math.min(options.historyDays ?? 3, 10));
    const includeHistory = options.includeHistory ?? true;

    const currentResponse = await authorizedFetch(`/api/weather/current?location=${encodeURIComponent(location)}`);
    if (!currentResponse.ok) throw new Error('Failed to fetch current weather');
    
    const current = await currentResponse.json();
    
    const forecastResponse = await authorizedFetch(`/api/weather/forecast?location=${encodeURIComponent(location)}&days=${days}`);
    if (!forecastResponse.ok) throw new Error('Failed to fetch forecast');
    
    const forecastData = await forecastResponse.json();
    
    let history = [];
    if (includeHistory) {
      const historyResponse = await authorizedFetch(`/api/weather/history?location=${encodeURIComponent(location)}&days=${historyDays}`).catch(() => null);
      history = historyResponse?.ok ? await historyResponse.json() : [];
    }
    
    return { current, forecastData, history };
  };
  
  const createWeatherTimeline = (historyData, currentWeather, forecastData, options = {}) => {
    const timeline = [];
    const today = new Date();
 
    const formatLocalDate = (date) => {
      const year = date.getFullYear();
      const month = String(date.getMonth() + 1).padStart(2, '0');
      const day = String(date.getDate()).padStart(2, '0');
      return `${year}-${month}-${day}`;
    };

    const extractEntryDate = (entry) => {
      if (!entry) return null;
      if (entry.date) return entry.date;
      if (entry.dt) {
        const date = new Date(entry.dt * 1000);
        return formatLocalDate(date);
      }
      if (entry.timestamp) {
        const date = new Date(entry.timestamp * 1000);
        return formatLocalDate(date);
      }
      return null;
    };
 
    const normalizeWeather = (entry) => {
      if (!entry) return null;
 
      const sources = [
        entry.condition,
        entry.weather?.[0]?.main,
        entry.weather?.[0]?.description
      ].filter(Boolean);
 
      if (sources.length === 0) return null;
 
      const resolved = sources[0].toString();
      const lowerResolved = resolved.toLowerCase();
 
      const conditionMap = [
        { match: ['thunder', 'storm'], value: 'Thunderstorm' },
        { match: ['drizzle'], value: 'Drizzle' },
        { match: ['rain', 'shower'], value: 'Rain' },
        { match: ['snow'], value: 'Snow' },
        { match: ['clear', 'sun'], value: 'Clear' },
        { match: ['mist', 'fog', 'haze', 'smog'], value: 'Mist' },
        { match: ['cloud'], value: 'Clouds' }
      ];
 
      const normalizedCondition =
        conditionMap.find(({ match }) => match.some(token => lowerResolved.includes(token)))?.value
        || resolved;
 
      const description =
        entry.weather?.[0]?.description
        || entry.description
        || resolved;
 
      return {
        condition: normalizedCondition,
        description,
        icon: entry.icon ?? entry.weather?.[0]?.icon ?? null
      };
    };

    const resolveTemp = (value) => {
      if (typeof value === 'number' && !Number.isNaN(value)) {
        return Math.round(value);
      }
      return null;
    };

    const { reuseHistory = [] } = options;

    const historyList = Array.isArray(historyData) ? historyData : [];
    const forecastList = Array.isArray(forecastData) ? forecastData : [];

    const pushHistoryEntry = (entry) => {
      if (!entry) return;
      const normalized = normalizeWeather(entry);
      if (!normalized) return;

      const dateKey = extractEntryDate(entry);
      if (!dateKey) return;

      const dateObj = new Date(dateKey);

      const tempMax = resolveTemp(
        entry.temp_max ?? entry.main?.temp_max ?? entry.temp?.max ?? entry.main?.temp
      );
      const tempMin = resolveTemp(
        entry.temp_min ?? entry.main?.temp_min ?? entry.temp?.min ?? entry.main?.temp
      );

      timeline.push({
        date: dateKey,
        day: dateObj.toLocaleDateString('en-US', { weekday: 'short' }),
        temp_max: tempMax,
        temp_min: tempMin,
        condition: normalized.condition,
        description: normalized.description,
        icon: normalized.icon,
        isHistory: true
      });
    };

    if (historyList.length) {
      for (let i = 3; i >= 1; i--) {
        const date = new Date(today);
        date.setDate(date.getDate() - i);

        const dateStr = formatLocalDate(date);
        const historyDay = historyList.find((h) => extractEntryDate(h) === dateStr) || null;
        if (!historyDay) continue;
        pushHistoryEntry(historyDay);
      }
    } else if (Array.isArray(reuseHistory) && reuseHistory.length) {
      const previousHistory = reuseHistory.filter(entry => entry?.isHistory).slice(-3);
      previousHistory.forEach(entry => {
        timeline.push({ ...entry });
      });
    }

    const weatherEntry = currentWeather?.weather?.[0];
    if (currentWeather && (weatherEntry || currentWeather.condition || currentWeather.description)) {
      const todayStr = formatLocalDate(today);
      const todayNormalized = normalizeWeather(weatherEntry) ?? {
        condition: weatherEntry?.main
          ?? currentWeather.condition
          ?? currentWeather.description
          ?? 'Unknown',
        description: weatherEntry?.description
          ?? currentWeather.description
          ?? weatherEntry?.main
          ?? currentWeather.condition
          ?? 'Unknown',
        icon: weatherEntry?.icon ?? null
      };

      timeline.push({
        date: todayStr,
        day: 'Today',
        temp_max: resolveTemp(currentWeather.main?.temp_max),
        temp_min: resolveTemp(currentWeather.main?.temp_min),
        condition: todayNormalized.condition,
        description: todayNormalized.description,
        icon: todayNormalized.icon,
        isToday: true
      });
    }

    forecastList
      .slice(1)
      .filter(Boolean)
      .forEach((forecastDay, index) => {
        const normalized = normalizeWeather(forecastDay);
        if (!normalized) return;

        const date = new Date(today);
        date.setDate(date.getDate() + index + 1);
        const dateStr = formatLocalDate(date);

        const tempMax = resolveTemp(
          forecastDay.temp_max ?? forecastDay.main?.temp_max ?? forecastDay.temp?.max
        );
        const tempMin = resolveTemp(
          forecastDay.temp_min ?? forecastDay.main?.temp_min ?? forecastDay.temp?.min
        );

        timeline.push({
          date: dateStr,
          day: index === 0 ? 'Tomorrow' : date.toLocaleDateString('en-US', { weekday: 'short' }),
          temp_max: tempMax,
          temp_min: tempMin,
          condition: normalized.condition,
          description: normalized.description,
          icon: normalized.icon,
          isFuture: true
        });
      });

    const { windowSize = null, maxEntries = null } = options;

    if (windowSize && timeline.length) {
      const todayIndex = timeline.findIndex(entry => entry.isToday);
      if (todayIndex !== -1) {
        const halfWindow = Math.floor(windowSize / 2);
        const start = Math.max(0, todayIndex - halfWindow);
        const end = Math.min(timeline.length, start + windowSize);
        return timeline.slice(start, end);
      }
      return timeline.slice(0, windowSize);
    }

    if (maxEntries) {
      return timeline.slice(0, maxEntries);
    }

    return timeline;
  };
  
  return {
    fetchWeatherByCoordinates,
    fetchWeatherByLocation,
    createWeatherTimeline
  };
}