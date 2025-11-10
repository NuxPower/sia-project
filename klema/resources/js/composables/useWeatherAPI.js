import { authorizedFetch } from '../services/http';

export function useWeatherAPI() {
  const fetchWeatherByCoordinates = async (lat, lng, options = {}) => {
    const days = Math.max(1, Math.min(options.days ?? 7, 16));
    const requestedHistoryDays = options.historyDays ?? 30;
    const historyDays = Math.max(1, Math.min(requestedHistoryDays, 30));
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
    const requestedHistoryDays = options.historyDays ?? 30;
    const historyDays = Math.max(1, Math.min(requestedHistoryDays, 30));
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
  
  const fetchWeatherHistory = async ({ lat, lon, location, days = 30 } = {}) => {
    const normalizeCoordinate = (value) => {
      if (value === null || value === undefined) {
        return null;
      }

      const numeric = Number(value);
      return Number.isFinite(numeric) ? numeric : null;
    };

    const historyDays = Math.max(1, Math.min(days ?? 30, 30));
    const params = new URLSearchParams();
    params.set('days', historyDays.toString());

    const normalizedLat = normalizeCoordinate(lat);
    const normalizedLon = normalizeCoordinate(lon);

    if (Number.isFinite(normalizedLat) && Number.isFinite(normalizedLon)) {
      params.set('lat', normalizedLat.toString());
      params.set('lon', normalizedLon.toString());
    } else if (typeof location === 'string' && location.trim()) {
      params.set('location', location.trim());
    }

    const url = `/api/weather/history?${params.toString()}`;
    const response = await authorizedFetch(url);
    if (!response.ok) {
      throw new Error(`Failed to fetch extended weather history: ${response.status}`);
    }

    return await response.json();
  };

  const fetchWeatherForecast = async ({ lat, lon, location, days = 16 } = {}) => {
    const normalizeCoordinate = (value) => {
      if (value === null || value === undefined) {
        return null;
      }

      const numeric = Number(value);
      return Number.isFinite(numeric) ? numeric : null;
    };

    const forecastDays = Math.max(1, Math.min(days ?? 16, 16));
    const params = new URLSearchParams();
    params.set('days', forecastDays.toString());

    const normalizedLat = normalizeCoordinate(lat);
    const normalizedLon = normalizeCoordinate(lon);

    if (Number.isFinite(normalizedLat) && Number.isFinite(normalizedLon)) {
      params.set('lat', normalizedLat.toString());
      params.set('lon', normalizedLon.toString());
    } else if (typeof location === 'string' && location.trim()) {
      params.set('location', location.trim());
    }

    const url = `/api/weather/forecast?${params.toString()}`;
    const response = await authorizedFetch(url);
    if (!response.ok) {
      throw new Error(`Failed to fetch extended weather forecast: ${response.status}`);
    }

    return await response.json();
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

    const {
      reuseHistory = [],
      historyWindow = null,
      includeNoDataPlaceholders = true
    } = options;

    const historyList = Array.isArray(historyData) ? historyData : [];
    const forecastList = Array.isArray(forecastData) ? forecastData : [];

    const historyEntriesMap = new Map();

    if (Array.isArray(reuseHistory) && reuseHistory.length) {
      reuseHistory
        .filter(entry => entry?.isHistory && entry?.date)
        .forEach(entry => {
          historyEntriesMap.set(entry.date, { ...entry });
        });
    }

    const buildHistoryEntry = (entry) => {
      if (!entry) return null;
      const normalized = normalizeWeather(entry);
      if (!normalized) return null;

      const dateKey = extractEntryDate(entry);
      if (!dateKey) return null;

      const dateObj = new Date(dateKey);

      const tempMax = resolveTemp(
        entry.temp_max ?? entry.main?.temp_max ?? entry.temp?.max ?? entry.main?.temp
      );
      const tempMin = resolveTemp(
        entry.temp_min ?? entry.main?.temp_min ?? entry.temp?.min ?? entry.main?.temp
      );

      return {
        date: dateKey,
        day: dateObj.toLocaleDateString('en-US', { weekday: 'short' }),
        temp_max: tempMax,
        temp_min: tempMin,
        condition: normalized.condition,
        description: normalized.description,
        icon: normalized.icon,
        isHistory: true
      };
    };

    if (historyList.length) {
      historyList.forEach((entry) => {
        const normalizedEntry = buildHistoryEntry(entry);
        if (normalizedEntry) {
          historyEntriesMap.set(normalizedEntry.date, normalizedEntry);
        }
      });
    }

    const resolvedHistoryWindow = historyWindow
      ? Math.max(1, Math.min(historyWindow, 30))
      : null;

    if (resolvedHistoryWindow) {
      for (let i = resolvedHistoryWindow; i >= 1; i--) {
        const dateObj = new Date(today);
        dateObj.setDate(dateObj.getDate() - i);
        const dateKey = formatLocalDate(dateObj);
        const existingEntry = historyEntriesMap.get(dateKey);

        if (existingEntry) {
          timeline.push(existingEntry);
          continue;
        }

        if (includeNoDataPlaceholders) {
          const placeholder = {
            date: dateKey,
            day: dateObj.toLocaleDateString('en-US', { weekday: 'short' }),
            temp_max: null,
            temp_min: null,
            condition: 'No data',
            description: 'No data available',
            icon: null,
            isHistory: true,
            noData: true,
            isPlaceholder: true
          };
          timeline.push(placeholder);
          historyEntriesMap.set(dateKey, placeholder);
        }
      }
    } else if (historyEntriesMap.size) {
      Array.from(historyEntriesMap.values())
        .sort((a, b) => new Date(a.date) - new Date(b.date))
        .slice(-30)
        .forEach(entry => timeline.push(entry));
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
    fetchWeatherHistory,
    fetchWeatherForecast,
    createWeatherTimeline
  };
}