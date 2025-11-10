import { ref } from 'vue'
import { DEFAULT_NOTIFICATION_SETTINGS, SUPPORTED_NOTIFICATION_SETTING_KEYS } from './useNotificationSettings'

const isDev = import.meta.env.VITE_APP_ENV === 'local';
const log = (...args) => isDev && console.log(...args);

const MAX_VISIBLE_ALERTS = 1;
const activeAlertKeys = new Set();
const alertQueue = [];
const MULTIPLE_ALERT_THRESHOLD = 3;
const SUMMARY_ALERT_KEY = 'summary::multiple-alerts';

let hasShownPrimaryAlert = false;

const alerts = ref([])
const isVisible = ref(false)

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

const normalizeForecastArray = (source) => {
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

const getPrecipitationAmount = (day) => {
    if (!day) return 0;

    const rain = day?.rain;
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

    if (typeof day?.precipitation === 'number') {
        return day.precipitation;
    }

    if (typeof day?.snow === 'number') {
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
    return Number.isFinite(precipitationAmount) && precipitationAmount >= 2;
};

const computeLongestRainStreak = (forecastDays) => {
    let longest = 0;
    let current = 0;

    forecastDays.forEach((day) => {
        if (hasMeaningfulRain(day)) {
            current += 1;
        } else {
            if (current > longest) {
                longest = current;
            }
            current = 0;
        }
    });

    if (current > longest) {
        longest = current;
    }

    return longest;
};

export function useGlobalAlerts() {
    const createSummaryAlert = () => ({
        id: Date.now() + Math.random(),
        type: 'info',
        title: 'Multiple Alerts Detected',
        message: 'Please see alerts for more details.',
        duration: 6000,
        persistent: false,
        action: null,
        createdAt: new Date(),
        slidingOut: false,
        timeoutId: null,
        key: SUMMARY_ALERT_KEY
    });

    const replacePendingWithSummary = () => {
        const summaryIndex = alertQueue.findIndex(alert => alert.key === SUMMARY_ALERT_KEY);
        const summaryAlert = summaryIndex > -1 
            ? alertQueue.splice(summaryIndex, 1)[0]
            : createSummaryAlert();

        const discardedAlerts = alertQueue.splice(0);
        discardedAlerts.forEach(alert => {
            if (alert?.timeoutId) {
                clearTimeout(alert.timeoutId);
            }
            if (alert?.key && alert.key !== SUMMARY_ALERT_KEY) {
                activeAlertKeys.delete(alert.key);
            }
        });

        alertQueue.push(summaryAlert);
        if (summaryIndex === -1) {
            activeAlertKeys.add(summaryAlert.key);
        }
    };

    const scheduleAutoDismiss = (alert) => {
        if (alert.persistent) return;

        if (alert.timeoutId) {
            clearTimeout(alert.timeoutId);
        }

        alert.timeoutId = setTimeout(() => {
            removeAlert(alert.id);
        }, alert.duration);
    };

    const processQueue = () => {
        if (hasShownPrimaryAlert && alertQueue.length >= MULTIPLE_ALERT_THRESHOLD) {
            replacePendingWithSummary();
        }

        while (alerts.value.length < MAX_VISIBLE_ALERTS && alertQueue.length > 0) {
            const nextAlert = alertQueue.shift();
            if (!hasShownPrimaryAlert && nextAlert.key !== SUMMARY_ALERT_KEY) {
                hasShownPrimaryAlert = true;
            } else if (nextAlert.key === SUMMARY_ALERT_KEY) {
                hasShownPrimaryAlert = true;
            }
            alerts.value.push(nextAlert);
            isVisible.value = true;
            scheduleAutoDismiss(nextAlert);
        }

        if (alerts.value.length === 0 && alertQueue.length === 0) {
            isVisible.value = false;
            hasShownPrimaryAlert = false;
        }
    };

    const createAlertKey = (alert) => {
        return [
            alert.type || 'info',
            alert.title || 'Alert',
            alert.message || ''
        ].join('::');
    };

    const addAlert = (alert) => {
        const key = createAlertKey(alert);

        if (activeAlertKeys.has(key)) {
            return null;
        }

        const newAlert = {
            id: Date.now() + Math.random(),
            type: alert.type || 'info',
            title: alert.title || 'Alert',
            message: alert.message || '',
            duration: alert.duration || 5000,
            persistent: alert.persistent || false,
            action: alert.action || null,
            createdAt: new Date(),
            slidingOut: false,
            timeoutId: null,
            key
        }

        alertQueue.push(newAlert)
        activeAlertKeys.add(key);
        processQueue()

        return newAlert.id
    }
    
    const removeAlert = (alertId) => {
        const index = alerts.value.findIndex(alert => alert.id === alertId)
        if (index > -1) {
            const [removedAlert] = alerts.value.splice(index, 1)
            if (removedAlert?.timeoutId) {
                clearTimeout(removedAlert.timeoutId)
            }
            if (removedAlert?.key) {
                activeAlertKeys.delete(removedAlert.key);
            }
        } else {
            const queueIndex = alertQueue.findIndex(alert => alert.id === alertId)
            if (queueIndex > -1) {
                const [queuedAlert] = alertQueue.splice(queueIndex, 1)
                if (queuedAlert?.timeoutId) {
                    clearTimeout(queuedAlert.timeoutId)
                }
                if (queuedAlert?.key) {
                    activeAlertKeys.delete(queuedAlert.key);
                }
            }
        }
        
        processQueue()
    }
    
    const clearAllAlerts = () => {
        alerts.value.forEach(alert => {
            if (alert?.timeoutId) {
                clearTimeout(alert.timeoutId)
            }
        })
        alerts.value.forEach(alert => {
            if (alert?.key) {
                activeAlertKeys.delete(alert.key);
            }
        })
        alertQueue.forEach(alert => {
            if (alert?.key) {
                activeAlertKeys.delete(alert.key);
            }
        })
        alerts.value = []
        alertQueue.length = 0
        activeAlertKeys.clear()
        isVisible.value = false
        hasShownPrimaryAlert = false;
    }
    
    const showSuccess = (title, message, options = {}) => {
        return addAlert({ type: 'success', title, message, ...options })
    }
    
    const showError = (title, message, options = {}) => {
        return addAlert({ type: 'error', title, message, ...options })
    }
    
    const showWarning = (title, message, options = {}) => {
        return addAlert({ type: 'warning', title, message, ...options })
    }
    
    const showInfo = (title, message, options = {}) => {
        return addAlert({ type: 'info', title, message, ...options })
    }
    
    const fetchRealWeatherAlerts = async (location = 'Butuan, Caraga, PH') => {
        try {
            log('Fetching real weather data for alerts...');
            
            const { fetchWeatherByLocation } = await import('./useWeatherAPI');
            const { current, forecastData } = await fetchWeatherByLocation(location);
            
            log('Weather data fetched:', { current, forecastData });
            
            const weatherData = { current, forecast: forecastData };
            const hasAlerts = checkWeatherConditions(weatherData);
            
            if (!hasAlerts) {
                const temp = Math.round(current.main.temp > 100 ? current.main.temp - 273.15 : current.main.temp);
                const windSpeed = Math.round((current.wind?.speed || 0) * 3.6);
                const humidity = current.main.humidity || 0;
                
                showInfo(
                    'Weather Status', 
                    `Current: ${current.weather[0].description} at ${temp}°C, Wind: ${windSpeed} km/h, Humidity: ${humidity}%`,
                    { duration: 8000 }
                );
            }
            
            return hasAlerts;
        } catch (error) {
            console.error('Failed to fetch weather data:', error);
            showError(
                'Weather Data Error',
                'Unable to fetch current weather data. Please check your connection.',
                { duration: 8000 }
            );
            return false;
        }
    }
    
    const showTestAlert = async () => {
        return await fetchRealWeatherAlerts();
    }
    
    const showWeatherAlert = async () => {
        return await fetchRealWeatherAlerts();
    }
    
    const showIrrigationAlert = () => {
        return showInfo(
            'Irrigation Schedule',
            'Automatic irrigation started for Field B. Estimated completion in 45 minutes.',
            { duration: 5000 }
        )
    }
    
    const showHarvestAlert = () => {
        return showSuccess(
            'Optimal Harvest Time',
            'Weather conditions are perfect for harvesting Field C.',
            { duration: 7000 }
        )
    }
    
    const checkWeatherConditions = (weatherData, preferenceOverrides = null) => {
        if (!weatherData) return false;
        
        log('Analyzing weather data for alerts');
        
        const preferences = sanitizePreferences(preferenceOverrides);
        const alertsGenerated = [];
        
        checkCurrentWeatherConditions(weatherData.current, alertsGenerated, preferences);
        const normalizedForecast = normalizeForecastArray(weatherData.forecast);
        checkForecastWeatherConditions(normalizedForecast, alertsGenerated, preferences);
        
        const todayAlerts = alertsGenerated.filter((alert) =>
            typeof alert.title === 'string' && alert.title.includes('- Today')
        );
        const futureAlerts = alertsGenerated.filter((alert) => !todayAlerts.includes(alert));

        const payloads = futureAlerts.length >= MULTIPLE_ALERT_THRESHOLD
            ? [...todayAlerts, createSummaryAlert()]
            : [...todayAlerts, ...futureAlerts];

        payloads.forEach(alertData => addAlert(alertData));
        
        return alertsGenerated.length > 0;
    }
    
    const checkCurrentWeatherConditions = (currentWeather, alerts, prefs) => {
        if (!currentWeather?.weather?.[0]?.main) return;
        
        const mainWeather = currentWeather.weather[0].main.toLowerCase();
        const description = currentWeather.weather[0].description;
        
        if (prefs.heavyRainAlerts && mainWeather.includes('rain')) {
            const severity = description.includes('heavy') || description.includes('extreme') ? 'Heavy' : 'Light';
            alerts.push({
                type: 'warning',
                title: `${severity} Rainfall Alert - Today`,
                message: `${severity.toLowerCase()} rainfall: ${description}. Consider postponing outdoor activities.`,
                duration: 8000
            });
        }
        
        if (prefs.stormAlerts && (mainWeather.includes('storm') || mainWeather.includes('thunderstorm'))) {
            alerts.push({
                type: 'error',
                title: 'Storm Warning - Today',
                message: `Storm conditions: ${description}. Seek shelter immediately.`,
                duration: 10000,
                persistent: true
            });
        }
        
        if (prefs.stormAlerts && mainWeather.includes('snow')) {
            alerts.push({
                type: 'warning',
                title: 'Snow Alert - Today',
                message: `Snow conditions: ${description}. Be cautious of icy conditions.`,
                duration: 6000
            });
        }
        
        if (prefs.maintenanceAlerts && mainWeather.includes('cloud') && description.includes('overcast')) {
            alerts.push({
                type: 'info',
                title: 'Overcast Conditions - Today',
                message: `Overcast skies: ${description}. Limited sunlight may affect plant growth.`,
                duration: 5000
            });
        }
        
        if (prefs.temperatureExtremes && currentWeather?.main?.temp) {
            let tempC = currentWeather.main.temp > 100 
                ? Math.round(currentWeather.main.temp - 273.15) 
                : Math.round(currentWeather.main.temp);
            
            if (tempC > 30) {
                alerts.push({
                    type: 'warning',
                    title: 'High Temperature Alert - Today',
                    message: `Hot weather: ${tempC}°C. Stay hydrated and limit outdoor activities.`,
                    duration: 7000
                });
            } else if (tempC < 15) {
                alerts.push({
                    type: 'warning',
                    title: 'Cool Temperature Alert - Today',
                    message: `Cool conditions: ${tempC}°C. Dress warmly and protect sensitive plants.`,
                    duration: 7000
                });
            }
        }
        
        if (prefs.strongWindWarnings && currentWeather?.wind?.speed) {
            const windKmh = Math.round(currentWeather.wind.speed * 3.6);
            
            if (windKmh > 25) {
                alerts.push({
                    type: 'warning',
                    title: 'Wind Alert - Today',
                    message: `Moderate to strong winds: ${windKmh} km/h. Be cautious outdoors.`,
                    duration: 6000
                });
            }
        }
    }
    
    const checkForecastWeatherConditions = (forecastArray, alerts, prefs) => {
        if (!Array.isArray(forecastArray) || !forecastArray.length) return;
        
        const forecastDays = forecastArray.slice(1, 8);
        
        forecastDays.forEach((day, index) => {
            const dayName = getDayName(index);
            const condition = day.condition || day.weather?.[0]?.main || day.weather?.[0]?.description || '';
            const conditionLower = condition.toLowerCase();
            
            if (!conditionLower) return;
            
            checkForecastCondition(conditionLower, condition, dayName, alerts, prefs);
            checkForecastTemperature(day, dayName, alerts, prefs);
        });
        
        checkExtendedWeatherPatterns(forecastDays, alerts, prefs);
    }
    
    const getDayName = (index) => {
        const names = ['Tomorrow', 'Day After Tomorrow'];
        return names[index] || `In ${index + 1} days`;
    }
    
    const checkForecastCondition = (conditionLower, condition, dayName, alerts, prefs) => {
        if (prefs.heavyRainAlerts && (conditionLower.includes('rain') || conditionLower.includes('drizzle'))) {
            const severity = conditionLower.includes('heavy') || conditionLower.includes('extreme') ? 'Heavy' : 'Light';
            alerts.push({
                type: 'warning',
                title: `${severity} Rainfall Warning - ${dayName}`,
                message: `${severity} rainfall expected ${dayName.toLowerCase()}.`,
                duration: 8000
            });
        }
        
        if (prefs.stormAlerts && (conditionLower.includes('storm') || conditionLower.includes('thunder'))) {
            alerts.push({
                type: 'error',
                title: `Storm Warning - ${dayName}`,
                message: `Severe storm conditions expected ${dayName.toLowerCase()}.`,
                duration: 10000,
                persistent: true
            });
        }
        
        if (prefs.stormAlerts && conditionLower.includes('snow')) {
            alerts.push({
                type: 'warning',
                title: `Snow Warning - ${dayName}`,
                message: `Snow conditions expected ${dayName.toLowerCase()}.`,
                duration: 7000
            });
        }
        
        if (prefs.maintenanceAlerts && (conditionLower.includes('fog') || conditionLower.includes('mist') || conditionLower.includes('haze'))) {
            alerts.push({
                type: 'info',
                title: `Visibility Warning - ${dayName}`,
                message: `Reduced visibility expected ${dayName.toLowerCase()}.`,
                duration: 6000
            });
        }
    }
    
    const checkForecastTemperature = (day, dayName, alerts, prefs) => {
        if (!prefs.temperatureExtremes) {
            return;
        }

        let tempMax = day.temp_max || day.main?.temp_max || day.temp?.max;
        let tempMin = day.temp_min || day.main?.temp_min || day.temp?.min;
        
        if (tempMax > 100) tempMax = Math.round(tempMax - 273.15);
        if (tempMin > 100) tempMin = Math.round(tempMin - 273.15);
        
        if (tempMax === undefined || tempMin === undefined) return;
        
        tempMax = Math.round(tempMax);
        tempMin = Math.round(tempMin);
        
        if (tempMax > 32) {
            alerts.push({
                type: 'warning',
                title: `Heat Wave Warning - ${dayName}`,
                message: `High temperatures expected: up to ${tempMax}°C.`,
                duration: 8000
            });
        }
        
        if (tempMin < 10) {
            alerts.push({
                type: 'warning',
                title: `Cold Weather Warning - ${dayName}`,
                message: `Cold temperatures expected: down to ${tempMin}°C.`,
                duration: 8000
            });
        }
        
        if ((tempMax - tempMin) > 15) {
            alerts.push({
                type: 'info',
                title: `Temperature Swing - ${dayName}`,
                message: `Large variation: ${tempMin}°C to ${tempMax}°C.`,
                duration: 6000
            });
        }
    }
    
    const checkExtendedWeatherPatterns = (forecastDays, alerts, prefs) => {
        if (!Array.isArray(forecastDays) || !forecastDays.length) {
            return;
        }

        if (prefs.maintenanceAlerts) {
            const longestRainStreak = computeLongestRainStreak(forecastDays);
            if (longestRainStreak >= 3) {
                alerts.push({
                    type: 'warning',
                    title: 'Extended Rainfall Period',
                    message: `Rain expected for ${longestRainStreak} consecutive days.`,
                    duration: 10000
                });
            }
        }

        if (prefs.temperatureExtremes) {
            const hotStreak = forecastDays.filter(day => {
                let tempMax = day.temp_max || day.main?.temp_max || day.temp?.max;
                if (tempMax > 100) tempMax -= 273.15;
                return tempMax && tempMax > 30;
            }).length;

            if (hotStreak >= 3) {
                alerts.push({
                    type: 'warning',
                    title: 'Extended Heat Wave',
                    message: `High temperatures for ${hotStreak} consecutive days.`,
                    duration: 10000
                });
            }

            const coldStreak = forecastDays.filter(day => {
                let tempMin = day.temp_min || day.main?.temp_min || day.temp?.min;
                if (tempMin > 100) tempMin -= 273.15;
                return tempMin && tempMin < 10;
            }).length;

            if (coldStreak >= 3) {
                alerts.push({
                    type: 'warning',
                    title: 'Extended Cold Period',
                    message: `Cold temperatures for ${coldStreak} consecutive days.`,
                    duration: 10000
                });
            }
        }
    }
    
    return {
        alerts,
        isVisible,
        addAlert,
        removeAlert,
        clearAllAlerts,
        showSuccess,
        showError,
        showWarning,
        showInfo,
        showTestAlert,
        fetchRealWeatherAlerts,
        showWeatherAlert,
        showIrrigationAlert,
        showHarvestAlert,
        checkWeatherConditions
    }
}