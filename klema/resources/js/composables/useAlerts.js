import { ref } from 'vue'
import axios from 'axios'
import { DEFAULT_NOTIFICATION_SETTINGS, SUPPORTED_NOTIFICATION_SETTING_KEYS } from './useNotificationSettings'

const API_BASE_URL = '/api'
const isDev = import.meta.env.VITE_APP_ENV === 'local';
const log = (...args) => isDev && console.log(...args);

const PREFERENCE_TYPE_MAP = {
    rain: 'heavyRainAlerts',
    storm: 'stormAlerts',
    snow: 'stormAlerts',
    visibility: 'maintenanceAlerts',
    temperature: 'temperatureExtremes',
    wind: 'strongWindWarnings',
    extended: 'maintenanceAlerts',
    irrigation: 'irrigationAlerts',
    harvest: 'harvestAlerts',
    maintenance: 'maintenanceAlerts'
};

const sanitizePreferences = (preferences) => {
    const sanitized = {};
    SUPPORTED_NOTIFICATION_SETTING_KEYS.forEach((key) => {
        const defaultValue = DEFAULT_NOTIFICATION_SETTINGS[key];
        const incoming = preferences && Object.prototype.hasOwnProperty.call(preferences, key)
            ? preferences[key]
            : undefined;
        sanitized[key] = typeof incoming === 'boolean' ? incoming : defaultValue;
    });
    return sanitized;
};

const resolvePreferenceKey = (warning) => {
    if (!warning) return null;
    if (warning.preferenceKey) {
        return warning.preferenceKey;
    }
    if (warning.type && PREFERENCE_TYPE_MAP[warning.type]) {
        return PREFERENCE_TYPE_MAP[warning.type];
    }
    return null;
};

const filterWarningsByPreferences = (warnings, preferences) => {
    if (!Array.isArray(warnings)) {
        return [];
    }

    return warnings
        .filter((warning) => {
            const key = resolvePreferenceKey(warning);
            if (!key) return true;
            return preferences[key] !== false;
        })
        .map((warning) => {
            if (!warning) return warning;
            const key = resolvePreferenceKey(warning);
            if (key && warning.preferenceKey !== key) {
                return { ...warning, preferenceKey: key };
            }
            return warning;
        });
};

const extractForecastArray = (source) => {
    if (Array.isArray(source)) {
        return source;
    }

    if (Array.isArray(source?.forecast)) {
        return source.forecast;
    }

    if (Array.isArray(source?.daily)) {
        return source.daily;
    }

    if (Array.isArray(source?.list)) {
        return source.list;
    }

    return null;
};

const normalizeForecastOptions = (options) => {
    if (!options) {
        return {
            forecast: null,
            preferences: sanitizePreferences()
        };
    }

    if (Array.isArray(options)) {
        return {
            forecast: extractForecastArray(options),
            preferences: sanitizePreferences()
        };
    }

    const forecast = extractForecastArray(options.forecast ?? null);
    const preferences = sanitizePreferences(options.preferences);

    return {
        forecast,
        preferences
    };
};

const toCelsius = (value) => {
    if (value === null || value === undefined) {
        return null;
    }

    const numeric = Number(value);
    if (!Number.isFinite(numeric)) {
        return null;
    }

    if (numeric > 100) {
        return Math.round((numeric - 273.15) * 10) / 10;
    }

    return Math.round(numeric * 10) / 10;
};

const getTempMax = (day) => {
    const candidates = [
        day?.temp_max,
        day?.main?.temp_max,
        day?.temp?.max
    ];

    for (const candidate of candidates) {
        const temp = toCelsius(candidate);
        if (temp !== null) {
            return Math.round(temp);
        }
    }

    return null;
};

const getTempMin = (day) => {
    const candidates = [
        day?.temp_min,
        day?.main?.temp_min,
        day?.temp?.min
    ];

    for (const candidate of candidates) {
        const temp = toCelsius(candidate);
        if (temp !== null) {
            return Math.round(temp);
        }
    }

    return null;
};

const getWindSpeedKmh = (day) => {
    const speed = day?.wind?.speed ?? day?.wind_speed ?? day?.windSpeed;
    if (speed === null || speed === undefined) {
        return null;
    }

    const numeric = Number(speed);
    if (!Number.isFinite(numeric)) {
        return null;
    }

    return Math.round(numeric * 3.6);
};

const getPrecipitationAmount = (day) => {
    if (!day) return 0;

    const rain = day.rain;
    if (typeof rain === 'number') {
        return rain;
    }

    if (rain && typeof rain === 'object') {
        const rainValues = Object.values(rain)
            .map((value) => Number(value))
            .filter((value) => Number.isFinite(value));
        if (rainValues.length) {
            return Math.max(...rainValues);
        }
    }

    if (typeof day.precipitation === 'number') {
        return day.precipitation;
    }

    if (typeof day.snow === 'number') {
        return day.snow;
    }

    return 0;
};

const hasMeaningfulRain = (day) => {
    const condition = (day?.condition || day?.weather?.[0]?.main || day?.weather?.[0]?.description || '').toLowerCase();
    if (!condition && !day) {
        return false;
    }

    if (condition.includes('rain') || condition.includes('drizzle') || condition.includes('storm') || condition.includes('thunder')) {
        return true;
    }

    const precipitationAmount = getPrecipitationAmount(day);
    return precipitationAmount >= 2;
};

const getDayName = (index) => {
    const names = ['Tomorrow', 'Day After Tomorrow'];
    return names[index] || `In ${index + 1} days`;
};

const buildDrySpellWarning = (streak) => {
    const start = streak[0];
    const maxTemp = Math.max(...streak.map((entry) => {
        const value = getTempMax(entry.day);
        return Number.isFinite(value) ? value : -Infinity;
    }));

    const severity = streak.length >= 5 || maxTemp >= 34 ? 'error' : 'warning';
    const descriptor = streak.length === 1
        ? getDayName(start.index)
        : `${streak.length}-day stretch`;

    const tempDetail = Number.isFinite(maxTemp) ? `Highs up to ${maxTemp}°C` : null;
    const detailParts = [`No rain expected`, tempDetail].filter(Boolean);

    return {
        id: `irrigation-${start.index}`,
        day: descriptor,
        icon: 'fas fa-tint',
        message: 'Dry spell expected — consider irrigation',
        value: detailParts.join(' • '),
        severity,
        type: 'irrigation',
        preferenceKey: 'irrigationAlerts'
    };
};

const buildHarvestWindowWarning = (window) => {
    const start = window[0];
    const length = window.length;
    const descriptor = length === 1
        ? getDayName(start.index)
        : `${length}-day window`;

    const highTemps = window.map((entry) => getTempMax(entry.day)).filter((value) => Number.isFinite(value));
    const maxTemp = highTemps.length ? Math.max(...highTemps) : null;
    const windValues = window.map((entry) => getWindSpeedKmh(entry.day)).filter((value) => Number.isFinite(value));
    const maxWind = windValues.length ? Math.max(...windValues) : null;

    const detailParts = [];
    if (maxTemp !== null) {
        detailParts.push(`Highs up to ${maxTemp}°C`);
    }
    if (maxWind !== null) {
        detailParts.push(`Winds below ${maxWind} km/h`);
    }
    if (!detailParts.length) {
        detailParts.push('Stable weather expected');
    }

    return {
        id: `harvest-${start.index}`,
        day: descriptor,
        icon: 'fas fa-cut',
        message: 'Favorable harvest window approaching',
        value: detailParts.join(' • '),
        severity: 'info',
        type: 'harvest',
        preferenceKey: 'harvestAlerts'
    };
};

const buildMaintenanceAdvisory = (index, details, severity) => {
    const detailList = details.length ? details : ['Severe weather expected'];
    return {
        id: `maintenance-${index}`,
        day: getDayName(index),
        icon: 'fas fa-wrench',
        message: 'Prepare equipment ahead of severe weather',
        value: detailList.join(' • '),
        severity,
        type: 'maintenance',
        preferenceKey: 'maintenanceAlerts'
    };
};

export function useAlerts() {
    const alerts = ref([])
    const forecastWarnings = ref([])
    const loading = ref(false)
    const error = ref(null)

    const fetchAlerts = async () => {
        loading.value = true
        error.value = null
        
        try {
            const response = await axios.get(`${API_BASE_URL}/alerts`)
            alerts.value = response.data.alerts || []
        } catch (err) {
            error.value = err.response?.data?.message || 'Failed to fetch alerts'
            console.error('Error fetching alerts:', err)
        } finally {
            loading.value = false
        }
    }

    const fetchActiveAlerts = async () => {
        loading.value = true
        error.value = null
        
        try {
            const response = await axios.get(`${API_BASE_URL}/alerts/active`)
            alerts.value = response.data.alerts || []
        } catch (err) {
            error.value = err.response?.data?.message || 'Failed to fetch active alerts'
            console.error('Error fetching active alerts:', err)
        } finally {
            loading.value = false
        }
    }

    const createAlert = async (alertData) => {
        loading.value = true
        error.value = null
        
        try {
            const response = await axios.post(`${API_BASE_URL}/alerts`, alertData)
            
            if (response.data.success) {
                alerts.value.unshift(response.data.alert)
                return response.data.alert
            }
        } catch (err) {
            error.value = err.response?.data?.message || 'Failed to create alert'
            console.error('Error creating alert:', err)
            throw err
        } finally {
            loading.value = false
        }
    }

    const resolveAlert = async (alertId) => {
        loading.value = true
        error.value = null
        
        try {
            const response = await axios.patch(`${API_BASE_URL}/alerts/${alertId}/resolve`)
            
            if (response.data.success) {
                alerts.value = alerts.value.filter(alert => alert.alert_id !== alertId)
            }
        } catch (err) {
            error.value = err.response?.data?.message || 'Failed to resolve alert'
            console.error('Error resolving alert:', err)
            throw err
        } finally {
            loading.value = false
        }
    }

    const deleteAlert = async (alertId) => {
        loading.value = true
        error.value = null
        
        try {
            const response = await axios.delete(`${API_BASE_URL}/alerts/${alertId}`)
            
            if (response.data.success) {
                alerts.value = alerts.value.filter(alert => alert.alert_id !== alertId)
            }
        } catch (err) {
            error.value = err.response?.data?.message || 'Failed to delete alert'
            console.error('Error deleting alert:', err)
            throw err
        } finally {
            loading.value = false
        }
    }

    const fetchForecastWarnings = async (options = null) => {
        loading.value = true
        error.value = null

        const { forecast, preferences } = normalizeForecastOptions(options)

        try {
            if (Array.isArray(forecast)) {
                forecastWarnings.value = generateForecastWarnings(forecast, preferences)
                return
            }

            const response = await axios.get(`${API_BASE_URL}/alerts/forecast-warnings`)
            const warnings = response.data.warnings || []
            forecastWarnings.value = filterWarningsByPreferences(warnings, preferences)
        } catch (err) {
            error.value = err.response?.data?.message || 'Failed to fetch forecast warnings'
            console.error('Error fetching forecast warnings:', err)
        } finally {
            loading.value = false
        }
    }
    
    const generateForecastWarnings = (forecastData, preferences) => {
        log('Generating forecast warnings from API data');
        
        if (!Array.isArray(forecastData)) {
            return []
        }
        
        const prefs = sanitizePreferences(preferences);
        const warnings = []
        const forecastDays = forecastData.slice(1, 8)
        const dayNames = forecastDays.map((_, index) => getDayName(index));
        
        forecastDays.forEach((day, index) => {
            const dayName = dayNames[index];
            
            checkWeatherConditions(day, dayName, index, warnings, prefs);
            checkTemperatureExtremes(day, dayName, index, warnings, prefs);
            checkWindConditions(day, dayName, index, warnings, prefs);
        });
        
        warnings.push(...checkExtendedPatterns(forecastDays, prefs));
        warnings.push(...detectDrySpellWarnings(forecastDays, prefs));
        warnings.push(...detectHarvestWindows(forecastDays, prefs));
        warnings.push(...generateMaintenanceAdvisories(forecastDays, prefs));
        
        const filteredWarnings = filterWarningsByPreferences(warnings, prefs);
        
        log('Generated warnings:', filteredWarnings);
        return filteredWarnings
    }
    
    const checkWeatherConditions = (day, dayName, index, warnings, prefs) => {
        const condition = day.condition || day.weather?.[0]?.main || day.weather?.[0]?.description || '';
        const conditionLower = condition.toLowerCase();
        
        if (!conditionLower) return;
        
        if (prefs.heavyRainAlerts && (conditionLower.includes('rain') || conditionLower.includes('drizzle'))) {
            const severity = conditionLower.includes('heavy') || conditionLower.includes('extreme') ? 'Heavy' : 'Light';
            warnings.push({
                id: `rain-${index}`,
                day: dayName,
                icon: 'fas fa-cloud-rain',
                message: `${severity} rainfall expected`,
                value: condition,
                severity: 'warning',
                type: 'rain',
                preferenceKey: 'heavyRainAlerts'
            });
        }
        
        if (prefs.stormAlerts && (conditionLower.includes('storm') || conditionLower.includes('thunderstorm') || conditionLower.includes('thunder'))) {
            warnings.push({
                id: `storm-${index}`,
                day: dayName,
                icon: 'fas fa-bolt',
                message: 'Severe storm conditions expected',
                value: condition,
                severity: 'error',
                type: 'storm',
                preferenceKey: 'stormAlerts'
            });
        }
        
        if (prefs.stormAlerts && conditionLower.includes('snow')) {
            warnings.push({
                id: `snow-${index}`,
                day: dayName,
                icon: 'fas fa-snowflake',
                message: 'Snow conditions expected',
                value: condition,
                severity: 'warning',
                type: 'snow',
                preferenceKey: 'stormAlerts'
            });
        }
        
        if (prefs.maintenanceAlerts && (conditionLower.includes('fog') || conditionLower.includes('mist') || conditionLower.includes('haze'))) {
            warnings.push({
                id: `fog-${index}`,
                day: dayName,
                icon: 'fas fa-smog',
                message: 'Reduced visibility expected',
                value: condition,
                severity: 'info',
                type: 'visibility',
                preferenceKey: 'maintenanceAlerts'
            });
        }
    }
    
    const checkTemperatureExtremes = (day, dayName, index, warnings, prefs) => {
        if (!prefs.temperatureExtremes) {
            return;
        }

        const tempMax = getTempMax(day);
        const tempMin = getTempMin(day);

        if (tempMax === null && tempMin === null) {
            return;
        }

        if (tempMax !== null && tempMax > 32) {
            warnings.push({
                id: `heat-${index}`,
                day: dayName,
                icon: 'fas fa-thermometer-full',
                message: 'High temperatures expected',
                value: `Up to ${tempMax}°C`,
                severity: 'warning',
                type: 'temperature',
                preferenceKey: 'temperatureExtremes'
            });
        }
        
        if (tempMin !== null && tempMin < 10) {
            warnings.push({
                id: `cold-${index}`,
                day: dayName,
                icon: 'fas fa-thermometer-empty',
                message: 'Cold temperatures expected',
                value: `Down to ${tempMin}°C`,
                severity: 'warning',
                type: 'temperature',
                preferenceKey: 'temperatureExtremes'
            });
        }
        
        if (tempMax !== null && tempMin !== null && (tempMax - tempMin) > 15) {
            warnings.push({
                id: `swing-${index}`,
                day: dayName,
                icon: 'fas fa-thermometer-half',
                message: 'Large temperature variation',
                value: `${tempMin}°C to ${tempMax}°C`,
                severity: 'info',
                type: 'temperature',
                preferenceKey: 'temperatureExtremes'
            });
        }
    }
    
    const checkWindConditions = (day, dayName, index, warnings, prefs) => {
        if (!prefs.strongWindWarnings) {
            return;
        }

        const windKmh = getWindSpeedKmh(day);
        if (!Number.isFinite(windKmh)) {
            return;
        }

        if (windKmh > 25) {
            const severity = windKmh >= 45 ? 'error' : 'warning';
            warnings.push({
                id: `wind-${index}`,
                day: dayName,
                icon: 'fas fa-wind',
                message: 'Strong winds expected',
                value: `${windKmh} km/h`,
                severity,
                type: 'wind',
                preferenceKey: 'strongWindWarnings'
            });
        }
    }
    
    const checkExtendedPatterns = (forecastDays, prefs) => {
        const patterns = [];
        
        const rainyDays = forecastDays
            .map((day) => ({
                day,
                condition: (day?.condition || day?.weather?.[0]?.main || day?.weather?.[0]?.description || '').toLowerCase(),
                precipitation: getPrecipitationAmount(day)
            }))
            .filter(({ condition }) => condition.includes('rain') || condition.includes('drizzle'))
            .map(({ day, condition, precipitation }) => ({
                day,
                hasRainyCondition: hasMeaningfulRain(day),
                condition,
                precipitation
            }));

        const rainyDaysCount = rainyDays.length;
        const rainyConditionsCount = rainyDays.filter(({ hasRainyCondition }) => hasRainyCondition).length;
        const hasAdditionalWetSignal = forecastDays.some((day) => {
            const precipitation = getPrecipitationAmount(day);
            return Number.isFinite(precipitation) && precipitation >= 5;
        });

        if (prefs.maintenanceAlerts && (rainyDaysCount >= 3 || (rainyConditionsCount >= 3 && hasAdditionalWetSignal))) {
            patterns.push({
                id: 'extended-rain',
                day: 'Extended Period',
                icon: 'fas fa-umbrella',
                message: 'Extended rainfall period',
                value: `${rainyDaysCount} consecutive days`,
                severity: 'warning',
                type: 'extended',
                preferenceKey: 'maintenanceAlerts'
            });
        }
        
        const hotDays = forecastDays.filter(day => {
            const tempMax = getTempMax(day);
            return tempMax !== null && tempMax > 30;
        }).length;
        
        if (prefs.irrigationAlerts && hotDays >= 3) {
            patterns.push({
                id: 'extended-heat',
                day: 'Extended Period',
                icon: 'fas fa-sun',
                message: 'Extended heat wave',
                value: `${hotDays} consecutive days`,
                severity: 'warning',
                type: 'irrigation',
                preferenceKey: 'irrigationAlerts'
            });
        }
        
        const coldDays = forecastDays.filter(day => {
            const tempMin = getTempMin(day);
            return tempMin !== null && tempMin < 10;
        }).length;
        
        if (prefs.temperatureExtremes && coldDays >= 3) {
            patterns.push({
                id: 'extended-cold',
                day: 'Extended Period',
                icon: 'fas fa-icicles',
                message: 'Extended cold period',
                value: `${coldDays} consecutive days`,
                severity: 'warning',
                type: 'temperature',
                preferenceKey: 'temperatureExtremes'
            });
        }
        
        return patterns;
    }

    const detectDrySpellWarnings = (forecastDays, prefs) => {
        if (!prefs.irrigationAlerts || !Array.isArray(forecastDays)) {
            return [];
        }

        const warnings = [];
        let streak = [];

        forecastDays.forEach((day, index) => {
            const hasRain = hasMeaningfulRain(day);
            const precipitation = getPrecipitationAmount(day);
            const hasWetSignal = hasRain || (Number.isFinite(precipitation) && precipitation >= 2);

            if (!hasWetSignal) {
                streak.push({ day, index });
            } else {
                if (streak.length >= 3) {
                    warnings.push(buildDrySpellWarning(streak));
                }
                streak = [];
            }
        });

        if (streak.length >= 3) {
            warnings.push(buildDrySpellWarning(streak));
        }

        return warnings;
    }

    const detectHarvestWindows = (forecastDays, prefs) => {
        if (!prefs.harvestAlerts || !Array.isArray(forecastDays)) {
            return [];
        }

        const windows = [];
        let currentWindow = [];

        const isFavorableHarvestDay = (day) => {
            if (!day) return false;
            if (hasMeaningfulRain(day)) return false;

            const wind = getWindSpeedKmh(day);
            if (Number.isFinite(wind) && wind > 25) return false;

            const tempMax = getTempMax(day);
            if (Number.isFinite(tempMax) && tempMax > 32) return false;

            return true;
        };

        forecastDays.forEach((day, index) => {
            if (isFavorableHarvestDay(day)) {
                currentWindow.push({ day, index });
            } else {
                if (currentWindow.length >= 2) {
                    windows.push(buildHarvestWindowWarning(currentWindow));
                }
                currentWindow = [];
            }
        });

        if (currentWindow.length >= 2) {
            windows.push(buildHarvestWindowWarning(currentWindow));
        }

        return windows;
    }

    const generateMaintenanceAdvisories = (forecastDays, prefs) => {
        if (!prefs.maintenanceAlerts || !Array.isArray(forecastDays)) {
            return [];
        }

        const advisories = [];

        forecastDays.forEach((day, index) => {
            const condition = (day?.condition || day?.weather?.[0]?.main || day?.weather?.[0]?.description || '').toLowerCase();
            const severeStorm = condition.includes('storm') || condition.includes('thunder');
            const wind = getWindSpeedKmh(day);
            const highWind = Number.isFinite(wind) && wind >= 45;
            const precipitation = getPrecipitationAmount(day);
            const heavyRain = Number.isFinite(precipitation) && precipitation >= 25;

            if (!severeStorm && !highWind && !heavyRain) {
                return;
            }

            const details = [];
            if (severeStorm) {
                details.push('Storm conditions forecast');
            }
            if (heavyRain) {
                details.push(`Rainfall ~${Math.round(precipitation)} mm`);
            }
            if (highWind) {
                details.push(`Winds up to ${wind} km/h`);
            }

            const severity = (severeStorm || heavyRain) ? 'error' : 'warning';
            advisories.push(buildMaintenanceAdvisory(index, details, severity));
        });

        return advisories;
    }

    const getAlertTypeInfo = (alertType) => {
        const typeMap = {
            'weather': { icon: 'fas fa-cloud-rain', color: 'warning', label: 'Weather Alert' },
            'pest': { icon: 'fas fa-bug', color: 'danger', label: 'Pest Alert' },
            'irrigation': { icon: 'fas fa-tint', color: 'info', label: 'Irrigation Alert' },
            'harvest': { icon: 'fas fa-cut', color: 'success', label: 'Harvest Alert' },
            'maintenance': { icon: 'fas fa-wrench', color: 'secondary', label: 'Maintenance Alert' }
        }

        return typeMap[alertType] || {
            icon: 'fas fa-exclamation-triangle',
            color: 'warning',
            label: 'Alert'
        }
    }

    const formatAlertTime = (issuedAt) => {
        const now = new Date()
        const alertTime = new Date(issuedAt)
        const diffInHours = Math.floor((now - alertTime) / (1000 * 60 * 60))
        
        if (diffInHours < 1) return 'Just now'
        if (diffInHours < 24) return `${diffInHours} hour${diffInHours > 1 ? 's' : ''} ago`
        
        const diffInDays = Math.floor(diffInHours / 24)
        return `${diffInDays} day${diffInDays > 1 ? 's' : ''} ago`
    }

    return {
        alerts,
        forecastWarnings,
        loading,
        error,
        fetchAlerts,
        fetchActiveAlerts,
        createAlert,
        resolveAlert,
        deleteAlert,
        fetchForecastWarnings,
        getAlertTypeInfo,
        formatAlertTime
    }
}