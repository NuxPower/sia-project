import { ref, reactive } from 'vue'
import axios from 'axios'

const API_BASE_URL = '/api'

export function useAlerts() {
    const alerts = ref([])
    const forecastWarnings = ref([])
    const loading = ref(false)
    const error = ref(null)

    // Fetch all alerts for the authenticated user
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

    // Fetch only active (unresolved) alerts
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

    // Create a new alert
    const createAlert = async (alertData) => {
        loading.value = true
        error.value = null
        
        try {
            const response = await axios.post(`${API_BASE_URL}/alerts`, alertData)
            
            if (response.data.success) {
                // Add the new alert to the beginning of the list
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

    // Resolve an alert
    const resolveAlert = async (alertId) => {
        loading.value = true
        error.value = null
        
        try {
            const response = await axios.patch(`${API_BASE_URL}/alerts/${alertId}/resolve`)
            
            if (response.data.success) {
                // Remove the alert from the list
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

    // Delete an alert
    const deleteAlert = async (alertId) => {
        loading.value = true
        error.value = null
        
        try {
            const response = await axios.delete(`${API_BASE_URL}/alerts/${alertId}`)
            
            if (response.data.success) {
                // Remove the alert from the list
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

    // Fetch forecast warnings - enhanced to analyze weather data
    const fetchForecastWarnings = async (weatherData = null) => {
        loading.value = true
        error.value = null
        
        try {
            // If weather data is provided, generate warnings from it
            if (weatherData && weatherData.forecast) {
                forecastWarnings.value = generateForecastWarnings(weatherData.forecast)
            } else {
                // Fallback to API call if no weather data provided
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
    
    // Generate forecast warnings from REAL API weather data
    const generateForecastWarnings = (forecastData) => {
        console.log('Generating forecast warnings from real API data:', forecastData);
        
        if (!forecastData || !Array.isArray(forecastData)) {
            console.log('No valid forecast data provided');
            return []
        }
        
        const warnings = []
        const forecastDays = forecastData.slice(0, 7) // Next 7 days
        
        forecastDays.forEach((day, index) => {
            const dayName = index === 0 ? 'Tomorrow' : 
                           index === 1 ? 'Day After Tomorrow' : 
                           `In ${index + 1} days`
            
            console.log(`Processing day ${index + 1}:`, day);
            
            // Check for severe weather conditions - handle both mock and real API data
            const condition = day.condition || day.weather?.[0]?.main || day.weather?.[0]?.description || '';
            const conditionLower = condition.toLowerCase()
            
            if (conditionLower) {
                // Rain forecast warning
                if (conditionLower.includes('rain') || conditionLower.includes('drizzle')) {
                    const severity = conditionLower.includes('heavy') || conditionLower.includes('extreme') ? 'Heavy' : 'Light'
                    warnings.push({
                        id: `rain-${index}`,
                        day: dayName,
                        icon: 'fas fa-cloud-rain',
                        message: `${severity} rainfall expected`,
                        value: condition,
                        severity: 'warning',
                        type: 'rain'
                    })
                }
                
                // Storm forecast warning
                if (conditionLower.includes('storm') || conditionLower.includes('thunderstorm') || conditionLower.includes('thunder')) {
                    warnings.push({
                        id: `storm-${index}`,
                        day: dayName,
                        icon: 'fas fa-bolt',
                        message: 'Severe storm conditions expected',
                        value: condition,
                        severity: 'error',
                        type: 'storm'
                    })
                }
                
                // Snow forecast warning
                if (conditionLower.includes('snow')) {
                    warnings.push({
                        id: `snow-${index}`,
                        day: dayName,
                        icon: 'fas fa-snowflake',
                        message: 'Snow conditions expected',
                        value: condition,
                        severity: 'warning',
                        type: 'snow'
                    })
                }
                
                // Fog/mist warning
                if (conditionLower.includes('fog') || conditionLower.includes('mist') || conditionLower.includes('haze')) {
                    warnings.push({
                        id: `fog-${index}`,
                        day: dayName,
                        icon: 'fas fa-smog',
                        message: 'Reduced visibility expected',
                        value: condition,
                        severity: 'info',
                        type: 'visibility'
                    })
                }
            }
            
            // Check temperature extremes - handle both mock and real API data structures
            let tempMax = day.temp_max || day.main?.temp_max || day.temp?.max;
            let tempMin = day.temp_min || day.main?.temp_min || day.temp?.min;
            
            // Convert from Kelvin if needed (API data might be in Kelvin)
            if (tempMax > 100) tempMax = Math.round(tempMax - 273.15);
            if (tempMin > 100) tempMin = Math.round(tempMin - 273.15);
            
            if (tempMax !== undefined && tempMin !== undefined) {
                tempMax = Math.round(tempMax);
                tempMin = Math.round(tempMin);
                
                // High temperature warning
                if (tempMax > 32) {
                    warnings.push({
                        id: `heat-${index}`,
                        day: dayName,
                        icon: 'fas fa-thermometer-full',
                        message: 'High temperatures expected',
                        value: `Up to ${tempMax}°C`,
                        severity: 'warning',
                        type: 'temperature'
                    })
                }
                
                // Cold temperature warning
                if (tempMin < 10) {
                    warnings.push({
                        id: `cold-${index}`,
                        day: dayName,
                        icon: 'fas fa-thermometer-empty',
                        message: 'Cold temperatures expected',
                        value: `Down to ${tempMin}°C`,
                        severity: 'warning',
                        type: 'temperature'
                    })
                }
                
                // Temperature swing warning
                if ((tempMax - tempMin) > 15) {
                    warnings.push({
                        id: `swing-${index}`,
                        day: dayName,
                        icon: 'fas fa-thermometer-half',
                        message: 'Large temperature variation',
                        value: `${tempMin}°C to ${tempMax}°C`,
                        severity: 'info',
                        type: 'temperature'
                    })
                }
            }
            
            // Check wind conditions if available
            const windSpeed = day.wind?.speed || day.wind_speed;
            if (windSpeed) {
                const windKmh = Math.round(windSpeed * 3.6); // Convert m/s to km/h
                if (windKmh > 25) {
                    warnings.push({
                        id: `wind-${index}`,
                        day: dayName,
                        icon: 'fas fa-wind',
                        message: 'Strong winds expected',
                        value: `${windKmh} km/h`,
                        severity: 'warning',
                        type: 'wind'
                    })
                }
            }
        })
        
        // Check for extended weather patterns
        const extendedPatterns = checkExtendedWeatherPatterns(forecastDays)
        warnings.push(...extendedPatterns)
        
        console.log('Generated warnings:', warnings);
        return warnings
    }
    
    // Check for extended weather patterns from REAL API data
    const checkExtendedWeatherPatterns = (forecastDays) => {
        const patterns = []
        
        // Check for extended rain periods - handle both mock and real API data
        const rainyDays = forecastDays.filter(day => {
            const condition = day.condition || day.weather?.[0]?.main || day.weather?.[0]?.description || '';
            return condition.toLowerCase().includes('rain') || condition.toLowerCase().includes('drizzle');
        }).length
        
        if (rainyDays >= 3) {
            patterns.push({
                id: 'extended-rain',
                day: 'Extended Period',
                icon: 'fas fa-umbrella',
                message: 'Extended rainfall period',
                value: `${rainyDays} consecutive days`,
                severity: 'warning',
                type: 'extended'
            })
        }
        
        // Check for extended heat periods - handle both mock and real API data
        const hotDays = forecastDays.filter(day => {
            let tempMax = day.temp_max || day.main?.temp_max || day.temp?.max;
            if (tempMax > 100) tempMax = tempMax - 273.15; // Convert from Kelvin
            return tempMax && tempMax > 30;
        }).length
        
        if (hotDays >= 3) {
            patterns.push({
                id: 'extended-heat',
                day: 'Extended Period',
                icon: 'fas fa-sun',
                message: 'Extended heat wave',
                value: `${hotDays} consecutive days`,
                severity: 'warning',
                type: 'extended'
            })
        }
        
        // Check for extended cold periods - handle both mock and real API data
        const coldDays = forecastDays.filter(day => {
            let tempMin = day.temp_min || day.main?.temp_min || day.temp?.min;
            if (tempMin > 100) tempMin = tempMin - 273.15; // Convert from Kelvin
            return tempMin && tempMin < 10;
        }).length
        
        if (coldDays >= 3) {
            patterns.push({
                id: 'extended-cold',
                day: 'Extended Period',
                icon: 'fas fa-icicles',
                message: 'Extended cold period',
                value: `${coldDays} consecutive days`,
                severity: 'warning',
                type: 'extended'
            })
        }
        
        return patterns
    }

    // Get alert type display information
    const getAlertTypeInfo = (alertType) => {
        const typeMap = {
            'weather': {
                icon: 'fas fa-cloud-rain',
                color: 'warning',
                label: 'Weather Alert'
            },
            'pest': {
                icon: 'fas fa-bug',
                color: 'danger',
                label: 'Pest Alert'
            },
            'irrigation': {
                icon: 'fas fa-tint',
                color: 'info',
                label: 'Irrigation Alert'
            },
            'harvest': {
                icon: 'fas fa-cut',
                color: 'success',
                label: 'Harvest Alert'
            },
            'maintenance': {
                icon: 'fas fa-wrench',
                color: 'secondary',
                label: 'Maintenance Alert'
            }
        }

        return typeMap[alertType] || {
            icon: 'fas fa-exclamation-triangle',
            color: 'warning',
            label: 'Alert'
        }
    }

    // Format alert time for display
    const formatAlertTime = (issuedAt) => {
        const now = new Date()
        const alertTime = new Date(issuedAt)
        const diffInHours = Math.floor((now - alertTime) / (1000 * 60 * 60))
        
        if (diffInHours < 1) {
            return 'Just now'
        } else if (diffInHours < 24) {
            return `${diffInHours} hour${diffInHours > 1 ? 's' : ''} ago`
        } else {
            const diffInDays = Math.floor(diffInHours / 24)
            return `${diffInDays} day${diffInDays > 1 ? 's' : ''} ago`
        }
    }

    return {
        // State
        alerts,
        forecastWarnings,
        loading,
        error,
        
        // Methods
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
