const DEFAULT_ICON = 'fas fa-cloud';

const ICON_CODE_MAP = {
  '01d': 'fas fa-sun',
  '01n': 'fas fa-moon',
  '02d': 'fas fa-cloud-sun',
  '02n': 'fas fa-cloud-moon',
  '03d': 'fas fa-cloud',
  '03n': 'fas fa-cloud',
  '04d': 'fas fa-cloud',
  '04n': 'fas fa-cloud',
  '09d': 'fas fa-cloud-showers-heavy',
  '09n': 'fas fa-cloud-showers-heavy',
  '10d': 'fas fa-cloud-sun-rain',
  '10n': 'fas fa-cloud-moon-rain',
  '11d': 'fas fa-bolt',
  '11n': 'fas fa-bolt',
  '13d': 'fas fa-snowflake',
  '13n': 'fas fa-snowflake',
  '50d': 'fas fa-smog',
  '50n': 'fas fa-smog'
};

const EXACT_CONDITION_MAP = {
  'clear': 'fas fa-sun',
  'sunny': 'fas fa-sun',
  'few clouds': 'fas fa-cloud-sun',
  'partly cloudy': 'fas fa-cloud-sun',
  'clouds': 'fas fa-cloud',
  'overcast clouds': 'fas fa-cloud',
  'broken clouds': 'fas fa-cloud',
  'scattered clouds': 'fas fa-cloud',
  'light rain': 'fas fa-cloud-rain',
  'moderate rain': 'fas fa-cloud-showers-heavy',
  'heavy intensity rain': 'fas fa-cloud-showers-heavy',
  'very heavy rain': 'fas fa-cloud-showers-heavy',
  'extreme rain': 'fas fa-cloud-showers-heavy',
  'drizzle': 'fas fa-cloud-rain',
  'light drizzle': 'fas fa-cloud-rain',
  'heavy drizzle': 'fas fa-cloud-rain',
  'rain': 'fas fa-cloud-showers-heavy',
  'shower rain': 'fas fa-cloud-showers-heavy',
  'thunderstorm': 'fas fa-bolt',
  'snow': 'fas fa-snowflake',
  'sleet': 'fas fa-snowflake',
  'hail': 'fas fa-cloud-meatball',
  'mist': 'fas fa-smog',
  'fog': 'fas fa-smog',
  'haze': 'fas fa-smog',
  'smoke': 'fas fa-smog',
  'dust': 'fas fa-smog',
  'sand': 'fas fa-smog',
  'ash': 'fas fa-smog',
  'squall': 'fas fa-wind',
  'tornado': 'fas fa-wind',
  'wind': 'fas fa-wind',
  'windy': 'fas fa-wind'
};

const KEYWORD_RULES = [
  { keywords: ['thunder', 'storm', 'lightning'], icon: 'fas fa-bolt' },
  { keywords: ['snow', 'sleet', 'blizzard'], icon: 'fas fa-snowflake' },
  { keywords: ['hail', 'pellet'], icon: 'fas fa-cloud-meatball' },
  { keywords: ['shower', 'rain'], icon: 'fas fa-cloud-showers-heavy' },
  { keywords: ['drizzle', 'sprinkle'], icon: 'fas fa-cloud-rain' },
  { keywords: ['partly', 'sun'], icon: 'fas fa-cloud-sun' },
  { keywords: ['moon'], icon: 'fas fa-cloud-moon' },
  { keywords: ['clear'], icon: 'fas fa-sun' },
  { keywords: ['cloud'], icon: 'fas fa-cloud' },
  { keywords: ['mist', 'fog', 'haze', 'smoke', 'smog', 'dust', 'sand', 'ash'], icon: 'fas fa-smog' },
  { keywords: ['wind', 'squall', 'tornado', 'hurricane'], icon: 'fas fa-wind' }
];

const normalizeInput = (input) => {
  if (!input) {
    return { condition: null, iconCode: null };
  }

  if (typeof input === 'string') {
    return { condition: input, iconCode: null };
  }

  if (typeof input === 'object') {
    return {
      condition:
        input.condition ??
        input.description ??
        input.weather ??
        input.main ??
        input.text ??
        null,
      iconCode: input.icon ?? input.iconCode ?? input.code ?? null
    };
  }

  return { condition: null, iconCode: null };
};

const resolveIconFromCode = (code) => {
  if (!code) return null;
  const normalized = code.toString().trim().toLowerCase();
  return ICON_CODE_MAP[normalized] ?? null;
};

const resolveIconFromCondition = (condition) => {
  if (!condition) return null;

  const normalized = condition.toString().trim().toLowerCase();
  if (!normalized) return null;

  if (EXACT_CONDITION_MAP[normalized]) {
    return EXACT_CONDITION_MAP[normalized];
  }

  for (const rule of KEYWORD_RULES) {
    if (rule.keywords.some((keyword) => normalized.includes(keyword))) {
      return rule.icon;
    }
  }

  return null;
};

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
  
  const getWeatherIcon = (payload) => {
    const { condition, iconCode } = normalizeInput(payload);

    const iconFromCode = resolveIconFromCode(iconCode);
    if (iconFromCode) {
      return iconFromCode;
    }

    const iconFromCondition = resolveIconFromCondition(condition);
    if (iconFromCondition) {
      return iconFromCondition;
    }

    return DEFAULT_ICON;
  };
  
  return {
    getDayLabel,
    getWeatherIcon
  };
}