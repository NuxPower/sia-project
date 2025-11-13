import { ref, computed } from 'vue';

const STORAGE_KEY = 'appSettings';

const defaultSettings = {
  temperatureUnit: 'celsius',
  windSpeedUnit: 'ms',
  timeFormat: '24h'
};

// Load settings from localStorage
const loadSettings = () => {
  if (typeof window === 'undefined') {
    return { ...defaultSettings };
  }
  
  try {
    const stored = window.localStorage?.getItem(STORAGE_KEY);
    if (stored) {
      const parsed = JSON.parse(stored);
      return {
        ...defaultSettings,
        ...parsed
      };
    }
  } catch (error) {
    console.error('Failed to load display settings:', error);
  }
  
  return { ...defaultSettings };
};

// Reactive settings
const settings = ref(loadSettings());

// Watch for changes in localStorage (in case settings are updated in another tab/component)
if (typeof window !== 'undefined') {
  window.addEventListener('storage', (e) => {
    if (e.key === STORAGE_KEY) {
      settings.value = loadSettings();
    }
  });
  
  // Also listen for custom event for immediate updates in the same window
  window.addEventListener('appSettingsUpdated', (e) => {
    if (e.detail) {
      settings.value = { ...defaultSettings, ...e.detail };
    } else {
      settings.value = loadSettings();
    }
  });
}

export function useDisplaySettings() {
  // Convert temperature from Celsius to Fahrenheit
  const celsiusToFahrenheit = (celsius) => {
    return (celsius * 9/5) + 32;
  };

  // Convert wind speed from m/s to km/h
  const msToKmh = (ms) => {
    return ms * 3.6;
  };

  // Convert wind speed from m/s to mph
  const msToMph = (ms) => {
    return ms * 2.237;
  };

  // Format temperature based on unit setting
  const formatTemperature = (celsius, options = {}) => {
    if (celsius === null || celsius === undefined || isNaN(celsius)) {
      return 'N/A';
    }
    
    const temp = parseFloat(celsius);
    const unit = settings.value.temperatureUnit || 'celsius';
    const decimals = options.decimals ?? 0;
    
    if (unit === 'fahrenheit') {
      const fahrenheit = celsiusToFahrenheit(temp);
      return `${fahrenheit.toFixed(decimals)}°F`;
    }
    
    return `${temp.toFixed(decimals)}°C`;
  };

  // Format wind speed based on unit setting
  const formatWindSpeed = (ms, options = {}) => {
    if (ms === null || ms === undefined || isNaN(ms)) {
      return 'N/A';
    }
    
    const speed = parseFloat(ms);
    const unit = settings.value.windSpeedUnit || 'ms';
    const decimals = options.decimals ?? 1;
    
    if (unit === 'kmh') {
      const kmh = msToKmh(speed);
      return `${kmh.toFixed(decimals)} km/h`;
    } else if (unit === 'mph') {
      const mph = msToMph(speed);
      return `${mph.toFixed(decimals)} mph`;
    }
    
    return `${speed.toFixed(decimals)} m/s`;
  };

  // Format time based on format setting
  const formatTime = (date, options = {}) => {
    if (!date) return '';
    
    const dateObj = date instanceof Date ? date : new Date(date);
    if (isNaN(dateObj.getTime())) return '';
    
    const format = settings.value.timeFormat || '24h';
    const includeSeconds = options.includeSeconds ?? false;
    
    if (format === '12h') {
      return dateObj.toLocaleTimeString('en-US', {
        hour: 'numeric',
        minute: '2-digit',
        second: includeSeconds ? '2-digit' : undefined,
        hour12: true
      });
    }
    
    return dateObj.toLocaleTimeString('en-US', {
      hour: '2-digit',
      minute: '2-digit',
      second: includeSeconds ? '2-digit' : undefined,
      hour12: false
    });
  };

  // Format date and time together
  const formatDateTime = (date, options = {}) => {
    if (!date) return '';
    
    const dateObj = date instanceof Date ? date : new Date(date);
    if (isNaN(dateObj.getTime())) return '';
    
    const format = settings.value.timeFormat || '24h';
    const dateFormat = options.dateFormat ?? { month: 'short', day: 'numeric', year: 'numeric' };
    
    const dateStr = dateObj.toLocaleDateString('en-US', dateFormat);
    const timeStr = formatTime(dateObj, options);
    
    return `${dateStr} ${timeStr}`;
  };

  // Get raw temperature value in the selected unit (for calculations)
  const getTemperatureValue = (celsius) => {
    if (celsius === null || celsius === undefined || isNaN(celsius)) {
      return null;
    }
    
    const temp = parseFloat(celsius);
    const unit = settings.value.temperatureUnit || 'celsius';
    
    if (unit === 'fahrenheit') {
      return celsiusToFahrenheit(temp);
    }
    
    return temp;
  };

  // Get raw wind speed value in the selected unit (for calculations)
  const getWindSpeedValue = (ms) => {
    if (ms === null || ms === undefined || isNaN(ms)) {
      return null;
    }
    
    const speed = parseFloat(ms);
    const unit = settings.value.windSpeedUnit || 'ms';
    
    if (unit === 'kmh') {
      return msToKmh(speed);
    } else if (unit === 'mph') {
      return msToMph(speed);
    }
    
    return speed;
  };

  // Refresh settings (call this when settings are updated)
  const refreshSettings = () => {
    settings.value = loadSettings();
  };

  return {
    // Settings
    settings: computed(() => settings.value),
    
    // Formatting functions
    formatTemperature,
    formatWindSpeed,
    formatTime,
    formatDateTime,
    
    // Value getters (for calculations)
    getTemperatureValue,
    getWindSpeedValue,
    
    // Utility
    refreshSettings
  };
}

