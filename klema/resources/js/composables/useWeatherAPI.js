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
  
  const fetchWeatherHistory = async ({ lat, lon, location, farmId, days = 30 } = {}) => {
    const normalizeCoordinate = (value) => {
      if (value === null || value === undefined) {
        return null;
      }

      const numeric = Number(value);
      return Number.isFinite(numeric) ? numeric : null;
    };

    const historyDays = Math.max(1, Math.min(days ?? 30, 90)); // Increased from 30 to 90 days
    const params = new URLSearchParams();
    params.set('days', historyDays.toString());

    const normalizedLat = normalizeCoordinate(lat);
    const normalizedLon = normalizeCoordinate(lon);

    // Prefer farm_id if available, then coordinates, then location
    if (farmId !== null && farmId !== undefined) {
      params.set('farm_id', farmId.toString());
    } else if (Number.isFinite(normalizedLat) && Number.isFinite(normalizedLon)) {
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
    // Use local date consistently - backend returns dates in location timezone, frontend should use local timezone for "today"
    const today = new Date();
    today.setHours(0, 0, 0, 0); // Normalize to start of day in local timezone
 
    const formatLocalDate = (date) => {
      // Ensure we're working with a Date object normalized to local timezone
      const normalizedDate = date instanceof Date ? date : new Date(date);
      const year = normalizedDate.getFullYear();
      const month = String(normalizedDate.getMonth() + 1).padStart(2, '0');
      const day = String(normalizedDate.getDate()).padStart(2, '0');
      return `${year}-${month}-${day}`;
    };

    const formatUTCDate = (date) => {
      const year = date.getUTCFullYear();
      const month = String(date.getUTCMonth() + 1).padStart(2, '0');
      const day = String(date.getUTCDate()).padStart(2, '0');
      return `${year}-${month}-${day}`;
    };

    const extractEntryDate = (entry) => {
      if (!entry) return null;
      if (entry.date) {
        // entry.date is already a string in YYYY-MM-DD format from backend
        // Use it directly - it's in location timezone format from the API
        return entry.date;
      }
      if (entry.dt) {
        // dt is a Unix timestamp - convert to date string
        // Use UTC date to match backend format (YYYY-MM-DD)
        const date = new Date(entry.dt * 1000);
        const year = date.getUTCFullYear();
        const month = String(date.getUTCMonth() + 1).padStart(2, '0');
        const day = String(date.getUTCDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
      }
      if (entry.timestamp) {
        const date = new Date(entry.timestamp * 1000);
        const year = date.getUTCFullYear();
        const month = String(date.getUTCMonth() + 1).padStart(2, '0');
        const day = String(date.getUTCDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
      }
      return null;
    };
 
    const normalizeWeather = (entry) => {
      if (!entry) return null;

      // Check multiple possible sources for condition/weather data
      // History entries might have condition directly, forecast entries might have weather array
      const conditionSource = entry.condition || entry.weather?.[0]?.main || entry.weather?.[0]?.description || entry.main?.condition;
      const descriptionSource = entry.description || entry.weather?.[0]?.description || entry.condition;
      const iconSource = entry.icon || entry.weather?.[0]?.icon;

      // If we have at least condition or description, return normalized data
      // Don't require both - history entries might only have condition
      if (!conditionSource && !descriptionSource) {
        return null;
      }

      const resolved = conditionSource?.toString() || descriptionSource?.toString() || 'Unknown';
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

      const description = descriptionSource?.toString() || resolved;

      return {
        condition: normalizedCondition,
        description,
        icon: iconSource || null
      };
    };

    const resolveTemp = (value) => {
      // Handle null/undefined
      if (value === null || value === undefined) {
        return null;
      }
      
      // Handle numbers (including 0, which is valid)
      if (typeof value === 'number' && !Number.isNaN(value) && Number.isFinite(value)) {
        return Math.round(value);
      }
      
      // Handle numeric strings
      if (typeof value === 'string') {
        const num = parseFloat(value);
        if (!Number.isNaN(num) && Number.isFinite(num)) {
          return Math.round(num);
        }
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
      
      // Extract date first - this is required
      const dateKey = extractEntryDate(entry);
      if (!dateKey) return null;

      // Parse date for weekday display - use local timezone parsing
      const dateObj = new Date(dateKey + 'T00:00:00');
      if (isNaN(dateObj.getTime())) {
        return null; // Invalid date
      }

      // Try to normalize weather, but don't fail if it's missing
      const normalized = normalizeWeather(entry);
      
      // Extract temperature data - try multiple possible locations
      const tempMaxSource = entry.temp_max ?? entry.main?.temp_max ?? entry.temp?.max ?? entry.temperature_max;
      const tempMinSource = entry.temp_min ?? entry.main?.temp_min ?? entry.temp?.min ?? entry.temperature_min;
      const tempMax = resolveTemp(tempMaxSource);
      const tempMin = resolveTemp(tempMinSource);

      const sunrise = Number.isFinite(entry.sunrise) ? entry.sunrise : Number(entry.sunrise);
      const sunset = Number.isFinite(entry.sunset) ? entry.sunset : Number(entry.sunset);
      const timezoneOffset = Number.isFinite(entry.timezone_offset)
        ? entry.timezone_offset
        : Number(entry.timezone_offset);

      const hourlyData = Array.isArray(entry.hourly) ? entry.hourly : [];
      const dailyPop = entry.precip_probability ?? entry.pop ?? entry.precipitationProbability;

      // Use normalized weather if available, otherwise use entry data directly
      // History entries from backend already have condition/description, so use those if normalized is null
      // Don't default to 'Unknown' yet - check if we actually have data first
      const rawCondition = normalized?.condition || entry.condition;
      const rawDescription = normalized?.description || entry.description || entry.condition;
      const rawIcon = normalized?.icon || entry.icon;
      
      // Determine if this entry has any meaningful data
      // Be lenient - if backend returned the entry, it likely has some data
      // Check for any of: temperature, condition, hourly data, solar data, or precipitation
      const hasTemp = tempMax !== null || tempMin !== null;
      const hasRawCondition = rawCondition && 
                             rawCondition !== 'Unknown' && 
                             rawCondition !== 'unknown' && 
                             String(rawCondition).trim() !== '' &&
                             String(rawCondition).trim() !== 'null';
      const hasHourly = Array.isArray(hourlyData) && hourlyData.length > 0;
      const hasSolar = (sunrise !== null && sunrise !== undefined && Number.isFinite(sunrise)) || 
                       (sunset !== null && sunset !== undefined && Number.isFinite(sunset));
      const hasPrecip = dailyPop !== null && dailyPop !== undefined;
      
      // If backend returned this entry, assume it has data unless ALL fields are missing
      // Only mark as noData if absolutely nothing is present
      const hasAnyData = hasTemp || hasRawCondition || hasHourly || hasSolar || hasPrecip;
      
      // Use actual values if available, otherwise set defaults
      // But don't mark as noData just because some fields are missing
      const condition = rawCondition || 'Unknown';
      const description = rawDescription || condition || 'Unknown';
      const icon = rawIcon || null;

      return {
        date: dateKey,
        day: dateObj.toLocaleDateString('en-US', { weekday: 'short' }),
        temp_max: tempMax,
        temp_min: tempMin,
        condition: condition || 'Unknown',
        description: description || condition || 'Unknown',
        icon: icon,
        isHistory: true,
        sunrise: Number.isFinite(sunrise) ? sunrise : null,
        sunset: Number.isFinite(sunset) ? sunset : null,
        timezone_offset: Number.isFinite(timezoneOffset) ? timezoneOffset : null,
        hourly: hourlyData,
        precip_probability: Number.isFinite(dailyPop) ? dailyPop : null,
        // Only mark as "no data" if truly no data exists
        // Don't mark as noData if we have any of: temperature, condition, hourly, or solar data
        noData: !hasAnyData
      };
    };

    // Build history entries from historyList and add them to the map
    // IMPORTANT: Process new history data AFTER reuseHistory, so new data can override old cached data
    if (historyList.length) {
      historyList.forEach((entry) => {
        const normalizedEntry = buildHistoryEntry(entry);
        if (normalizedEntry && normalizedEntry.date) {
          // New history data should override cached/reused history if it exists
          // This ensures we always use the most recent data from the backend
          historyEntriesMap.set(normalizedEntry.date, normalizedEntry);
        }
      });
    }

    const resolvedHistoryWindow = historyWindow
      ? Math.max(1, Math.min(historyWindow, 30))
      : null;

    // Determine "today" - use first forecast entry's date if available (location timezone),
    // otherwise use local browser timezone
    let todayStr = formatLocalDate(today);
    if (forecastList.length > 0 && forecastList[0]?.date) {
      // Backend returns dates in location's timezone, so use first forecast date as "today"
      todayStr = forecastList[0].date;
      // Also update the today date object to match
      const todayFromForecast = new Date(todayStr + 'T00:00:00');
      if (!isNaN(todayFromForecast.getTime())) {
        today.setTime(todayFromForecast.getTime());
      }
    }
    
    // Now add all history entries from the map to the timeline
    // IMPORTANT: Add ALL history entries from backend, excluding only today
    const processedHistoryDates = new Set();
    if (historyEntriesMap.size) {
      Array.from(historyEntriesMap.values())
        .filter(entry => {
          // Only exclude entries that match today's date exactly
          if (!entry?.date) return false;
          return entry.date !== todayStr;
        })
        .sort((a, b) => a.date.localeCompare(b.date)) // Sort chronologically
        .forEach(entry => {
          // Add all history entries, even if they don't have much data
          // The UI will show "No data" if needed
          timeline.push(entry);
          processedHistoryDates.add(entry.date);
        });
    }

    // Then, if we need to fill history window, add placeholders for missing dates
    // Calculate dates by parsing todayStr and subtracting days - ensure consistent format
    if (resolvedHistoryWindow) {
      // Parse todayStr as a date string (YYYY-MM-DD) - treat it as a date in location timezone
      // Since dates are just strings, we can calculate relative dates by parsing and manipulating
      const todayParts = todayStr.split('-');
      if (todayParts.length === 3) {
        const todayYear = parseInt(todayParts[0], 10);
        const todayMonth = parseInt(todayParts[1], 10) - 1; // JavaScript months are 0-indexed
        const todayDay = parseInt(todayParts[2], 10);
        
        // Create a date object at noon to avoid timezone issues
        const todayDateObj = new Date(todayYear, todayMonth, todayDay, 12, 0, 0);
        
        // Only add placeholders for dates that don't have data yet
        for (let i = resolvedHistoryWindow; i >= 1; i--) {
          const dateObj = new Date(todayDateObj);
          dateObj.setDate(dateObj.getDate() - i);
          
          // Format date consistently as YYYY-MM-DD (same format as backend)
          const year = dateObj.getFullYear();
          const month = String(dateObj.getMonth() + 1).padStart(2, '0');
          const day = String(dateObj.getDate()).padStart(2, '0');
          const dateKey = `${year}-${month}-${day}`;
          
          // Skip today's date - it will be added separately as "Today"
          if (dateKey === todayStr) {
            continue;
          }
          
          // Skip if we already have data for this date
          if (processedHistoryDates.has(dateKey)) {
            continue;
          }

          // Only add placeholder if we're supposed to include them
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
            processedHistoryDates.add(dateKey);
          }
        }
      }
    }

    // Add "Today" entry - ensure it's only added once and always marked correctly
    const weatherEntry = currentWeather?.weather?.[0];
    if (currentWeather && (weatherEntry || currentWeather.condition || currentWeather.description)) {
      // Remove any existing entry for today's date to avoid duplicates
      const todayIndex = timeline.findIndex(entry => entry?.date === todayStr);
      if (todayIndex !== -1) {
        timeline.splice(todayIndex, 1);
      }

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

      // Always add today's entry with isToday flag set
      timeline.push({
        date: todayStr,
        day: 'Today',
        temp_max: resolveTemp(currentWeather.main?.temp_max ?? currentWeather.main?.temp),
        temp_min: resolveTemp(currentWeather.main?.temp_min ?? currentWeather.main?.temp),
        condition: todayNormalized.condition,
        description: todayNormalized.description,
        icon: todayNormalized.icon,
        isToday: true,
        isHistory: false,
        isFuture: false
      });
    }

    // Process forecast entries - use dates from backend (location timezone)
    forecastList
      .filter(Boolean)
      .forEach((forecastDay) => {
        const normalized = normalizeWeather(forecastDay);
        if (!normalized) return;

        // Use date from backend (location timezone)
        const forecastDate = forecastDay.date;
        if (!forecastDate) return;
        
        // Skip today's date - it will be added separately as "Today" from currentWeather
        if (forecastDate === todayStr) {
          return;
        }

        const tempMax = resolveTemp(
          forecastDay.temp_max ?? forecastDay.main?.temp_max ?? forecastDay.temp?.max
        );
        const tempMin = resolveTemp(
          forecastDay.temp_min ?? forecastDay.main?.temp_min ?? forecastDay.temp?.min
        );

        // Calculate if this is tomorrow relative to today (in location timezone)
        const todayDateObj = new Date(todayStr + 'T00:00:00');
        const tomorrowDateObj = new Date(todayDateObj);
        tomorrowDateObj.setDate(tomorrowDateObj.getDate() + 1);
        const tomorrowStr = formatLocalDate(tomorrowDateObj);
        const isTomorrow = forecastDate === tomorrowStr;

        const forecastDateObj = new Date(forecastDate + 'T00:00:00');
        const dayLabel = isTomorrow 
          ? 'Tomorrow' 
          : forecastDateObj.toLocaleDateString('en-US', { weekday: 'short' });

        timeline.push({
          date: forecastDate,
          day: dayLabel,
          temp_max: tempMax,
          temp_min: tempMin,
          condition: normalized.condition,
          description: normalized.description,
          icon: normalized.icon,
          isFuture: true,
          isHistory: false,
          isToday: false
        });
      });

    // Sort timeline chronologically by date
    timeline.sort((a, b) => {
      if (!a.date || !b.date) return 0;
      return a.date.localeCompare(b.date);
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