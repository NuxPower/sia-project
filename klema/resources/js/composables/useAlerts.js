import { ref } from 'vue'
import axios from 'axios'

const API_BASE_URL = '/api'
const isDev = import.meta.env.VITE_APP_ENV === 'local';
const log = (...args) => isDev && console.log(...args);

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

    const fetchForecastWarnings = async (weatherData = null) => {
        loading.value = true
        error.value = null
        
        try {
            if (weatherData && weatherData.forecast) {
                forecastWarnings.value = generateForecastWarnings(weatherData.forecast)
            } else {
                const response = await axios.get(`${API_BASE_URL}/alerts/forecast-warnings`)
                forecastWarnings.value = response.data.warnings || []
            }
        } catch (err) {
            error.value = err.response?.data?.message || 'Failed to fetch forecast warnings'
            console.error('Error fetching forecast warnings:', err)
        } finally {
            loading.value = false
        }
    }
    
    const generateForecastWarnings = (forecastData) => {
        log('Generating forecast warnings from API data');
        
        if (!forecastData || !Array.isArray(forecastData)) {
            return []
        }
        
        const warnings = []
        const forecastDays = forecastData.slice(0, 7)
        
        forecastDays.forEach((day, index) => {
            const dayName = getDayName(index);
            
            checkWeatherConditions(day, dayName, index, warnings);
            checkTemperatureExtremes(day, dayName, index, warnings);
            checkWindConditions(day, dayName, index, warnings);
        });
        
        warnings.push(...checkExtendedPatterns(forecastDays));
        
        log('Generated warnings:', warnings);
        return warnings
    }
    
    const getDayName = (index) => {
        const names = ['Tomorrow', 'Day After Tomorrow'];
        return names[index] || `In ${index + 1} days`;
    }
    
    const checkWeatherConditions = (day, dayName, index, warnings) => {
        const condition = day.condition || day.weather?.[0]?.main || day.weather?.[0]?.description || '';
        const conditionLower = condition.toLowerCase();
        
        if (!conditionLower) return;
        
        if (conditionLower.includes('rain') || conditionLower.includes('drizzle')) {
            const severity = conditionLower.includes('heavy') || conditionLower.includes('extreme') ? 'Heavy' : 'Light';
            warnings.push({
                id: `rain-${index}`,
                day: dayName,
                icon: 'fas fa-cloud-rain',
                message: `${severity} rainfall expected`,
                value: condition,
                severity: 'warning',
                type: 'rain'
            });
        }
        
        if (conditionLower.includes('storm') || conditionLower.includes('thunderstorm') || conditionLower.includes('thunder')) {
            warnings.push({
                id: `storm-${index}`,
                day: dayName,
                icon: 'fas fa-bolt',
                message: 'Severe storm conditions expected',
                value: condition,
                severity: 'error',
                type: 'storm'
            });
        }
        
        if (conditionLower.includes('snow')) {
            warnings.push({
                id: `snow-${index}`,
                day: dayName,
                icon: 'fas fa-snowflake',
                message: 'Snow conditions expected',
                value: condition,
                severity: 'warning',
                type: 'snow'
            });
        }
        
        if (conditionLower.includes('fog') || conditionLower.includes('mist') || conditionLower.includes('haze')) {
            warnings.push({
                id: `fog-${index}`,
                day: dayName,
                icon: 'fas fa-smog',
                message: 'Reduced visibility expected',
                value: condition,
                severity: 'info',
                type: 'visibility'
            });
        }
    }
    
    const checkTemperatureExtremes = (day, dayName, index, warnings) => {
        let tempMax = day.temp_max || day.main?.temp_max || day.temp?.max;
        let tempMin = day.temp_min || day.main?.temp_min || day.temp?.min;
        
        if (tempMax > 100) tempMax = Math.round(tempMax - 273.15);
        if (tempMin > 100) tempMin = Math.round(tempMin - 273.15);
        
        if (tempMax === undefined || tempMin === undefined) return;
        
        tempMax = Math.round(tempMax);
        tempMin = Math.round(tempMin);
        
        if (tempMax > 32) {
            warnings.push({
                id: `heat-${index}`,
                day: dayName,
                icon: 'fas fa-thermometer-full',
                message: 'High temperatures expected',
                value: `Up to ${tempMax}°C`,
                severity: 'warning',
                type: 'temperature'
            });
        }
        
        if (tempMin < 10) {
            warnings.push({
                id: `cold-${index}`,
                day: dayName,
                icon: 'fas fa-thermometer-empty',
                message: 'Cold temperatures expected',
                value: `Down to ${tempMin}°C`,
                severity: 'warning',
                type: 'temperature'
            });
        }
        
        if ((tempMax - tempMin) > 15) {
            warnings.push({
                id: `swing-${index}`,
                day: dayName,
                icon: 'fas fa-thermometer-half',
                message: 'Large temperature variation',
                value: `${tempMin}°C to ${tempMax}°C`,
                severity: 'info',
                type: 'temperature'
            });
        }
    }
    
    const checkWindConditions = (day, dayName, index, warnings) => {
        const windSpeed = day.wind?.speed || day.wind_speed;
        
        if (windSpeed) {
            const windKmh = Math.round(windSpeed * 3.6);
            if (windKmh > 25) {
                warnings.push({
                    id: `wind-${index}`,
                    day: dayName,
                    icon: 'fas fa-wind',
                    message: 'Strong winds expected',
                    value: `${windKmh} km/h`,
                    severity: 'warning',
                    type: 'wind'
                });
            }
        }
    }
    
    const checkExtendedPatterns = (forecastDays) => {
        const patterns = [];
        
        const rainyDays = forecastDays.filter(day => {
            const condition = (day.condition || day.weather?.[0]?.main || day.weather?.[0]?.description || '').toLowerCase();
            return condition.includes('rain') || condition.includes('drizzle');
        }).length;
        
        if (rainyDays >= 3) {
            patterns.push({
                id: 'extended-rain',
                day: 'Extended Period',
                icon: 'fas fa-umbrella',
                message: 'Extended rainfall period',
                value: `${rainyDays} consecutive days`,
                severity: 'warning',
                type: 'extended'
            });
        }
        
        const hotDays = forecastDays.filter(day => {
            let tempMax = day.temp_max || day.main?.temp_max || day.temp?.max;
            if (tempMax > 100) tempMax = tempMax - 273.15;
            return tempMax && tempMax > 30;
        }).length;
        
        if (hotDays >= 3) {
            patterns.push({
                id: 'extended-heat',
                day: 'Extended Period',
                icon: 'fas fa-sun',
                message: 'Extended heat wave',
                value: `${hotDays} consecutive days`,
                severity: 'warning',
                type: 'extended'
            });
        }
        
        const coldDays = forecastDays.filter(day => {
            let tempMin = day.temp_min || day.main?.temp_min || day.temp?.min;
            if (tempMin > 100) tempMin = tempMin - 273.15;
            return tempMin && tempMin < 10;
        }).length;
        
        if (coldDays >= 3) {
            patterns.push({
                id: 'extended-cold',
                day: 'Extended Period',
                icon: 'fas fa-icicles',
                message: 'Extended cold period',
                value: `${coldDays} consecutive days`,
                severity: 'warning',
                type: 'extended'
            });
        }
        
        return patterns;
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