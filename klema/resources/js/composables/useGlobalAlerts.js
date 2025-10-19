import { ref, reactive } from 'vue'

// Global alerts state - these need to be reactive
const alerts = ref([])
const isVisible = ref(false)

export function useGlobalAlerts() {
    // Add a new global alert
    const addAlert = (alert) => {
        console.log('addAlert called with:', alert);
        const newAlert = {
            id: Date.now() + Math.random(),
            type: alert.type || 'info', // info, success, warning, error
            title: alert.title || 'Alert',
            message: alert.message || '',
            duration: alert.duration || 5000, // Auto-dismiss after 5 seconds
            persistent: alert.persistent || false, // If true, won't auto-dismiss
            action: alert.action || null, // Optional action button
            createdAt: new Date()
        }
        
        console.log('Adding new alert:', newAlert);
        alerts.value.push(newAlert)
        isVisible.value = true
        console.log('Alerts array now has:', alerts.value.length, 'alerts');
        console.log('isVisible is now:', isVisible.value);
        
        // Auto-dismiss after duration (unless persistent)
        if (!newAlert.persistent) {
            setTimeout(() => {
                removeAlert(newAlert.id)
            }, newAlert.duration)
        }
        
        return newAlert.id
    }
    
    // Remove an alert by ID
    const removeAlert = (alertId) => {
        const index = alerts.value.findIndex(alert => alert.id === alertId)
        if (index > -1) {
            alerts.value.splice(index, 1)
        }
        
        // Hide container if no alerts remain
        if (alerts.value.length === 0) {
            isVisible.value = false
        }
    }
    
    // Clear all alerts
    const clearAllAlerts = () => {
        alerts.value = []
        isVisible.value = false
    }
    
    // Convenience methods for different alert types
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
    
    // Test alert method - generates random alerts for UI testing
    const showTestAlert = () => {
        console.log('showTestAlert called!');
        const alertTypes = ['success', 'error', 'warning', 'info']
        const randomType = alertTypes[Math.floor(Math.random() * alertTypes.length)]
        const messages = {
            success: ['Great job!', 'Operation completed successfully!', 'Data saved successfully!'],
            error: ['Something went wrong!', 'Failed to save data!', 'Connection error!'],
            warning: ['Please check your input!', 'This action cannot be undone!', 'Low disk space!'],
            info: ['New feature available!', 'System maintenance scheduled!', 'Weather update available!']
        }
        
        const randomMessage = messages[randomType][Math.floor(Math.random() * messages[randomType].length)]
        
        console.log('Adding alert:', randomType, randomMessage);
        return addAlert({
            type: randomType,
            title: `${randomType.charAt(0).toUpperCase() + randomType.slice(1)} Alert`,
            message: randomMessage,
            duration: 4000
        })
    }
    
    // Realistic test scenarios for different alert types
    const showWeatherAlert = () => {
        return showWarning(
            'Severe Weather Warning',
            'Heavy rainfall expected in your area within the next 2 hours. Consider postponing outdoor activities.',
            { duration: 8000 }
        )
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
            'Weather conditions are perfect for harvesting Field C. Consider starting harvest operations.',
            { duration: 7000 }
        )
    }
    
    // Real weather alert system
    const checkWeatherConditions = (weatherData) => {
        if (!weatherData) return;
        
        console.log('Weather data received for alert analysis:', weatherData);
        
        const alerts = [];
        
        // Check for severe weather conditions
        if (weatherData.current?.weather?.[0]?.main) {
            const mainWeather = weatherData.current.weather[0].main.toLowerCase();
            const description = weatherData.current.weather[0].description;
            
            // Rain alert (more sensitive)
            if (mainWeather.includes('rain')) {
                const severity = description.includes('heavy') || description.includes('extreme') ? 'Heavy' : 'Light';
                alerts.push({
                    type: 'warning',
                    title: `${severity} Rainfall Alert`,
                    message: `${severity.toLowerCase()} rainfall detected: ${description}. Consider postponing outdoor activities.`,
                    duration: 8000,
                    persistent: false
                });
            }
            
            // Storm alert
            if (mainWeather.includes('storm') || mainWeather.includes('thunderstorm')) {
                alerts.push({
                    type: 'error',
                    title: 'Storm Warning',
                    message: `Storm conditions detected: ${description}. Seek shelter immediately.`,
                    duration: 10000,
                    persistent: true // Critical alert - don't auto-dismiss
                });
            }
            
            // Snow alert
            if (mainWeather.includes('snow')) {
                alerts.push({
                    type: 'warning',
                    title: 'Snow Alert',
                    message: `Snow conditions detected: ${description}. Be cautious of icy conditions.`,
                    duration: 6000,
                    persistent: false
                });
            }
            
            // Cloudy conditions alert
            if (mainWeather.includes('cloud') && description.includes('overcast')) {
                alerts.push({
                    type: 'info',
                    title: 'Overcast Conditions',
                    message: `Overcast skies detected: ${description}. Limited sunlight may affect plant growth.`,
                    duration: 5000,
                    persistent: false
                });
            }
        }
        
        // Check temperature extremes
        if (weatherData.current?.main?.temp) {
            let tempC = weatherData.current.main.temp;
            
            // Check if temperature is already in Celsius (mock data) or needs conversion from Kelvin (API data)
            if (tempC > 100) {
                // Temperature is in Kelvin, convert to Celsius
                tempC = Math.round(tempC - 273.15);
            } else {
                // Temperature is already in Celsius
                tempC = Math.round(tempC);
            }
            
            console.log('Temperature analysis:', {
                original: weatherData.current.main.temp,
                converted: tempC,
                isKelvin: weatherData.current.main.temp > 100,
                dataSource: weatherData.current.main.temp > 100 ? 'API (Kelvin)' : 'Mock/API (Celsius)'
            });
            
            if (tempC > 30) {
                alerts.push({
                    type: 'warning',
                    title: 'High Temperature Alert',
                    message: `Hot weather detected: ${tempC}°C. Consider staying hydrated and limiting outdoor activities.`,
                    duration: 7000,
                    persistent: false
                });
            } else if (tempC < 15) {
                alerts.push({
                    type: 'warning',
                    title: 'Cool Temperature Alert',
                    message: `Cool conditions: ${tempC}°C. Dress warmly and consider protecting sensitive plants.`,
                    duration: 7000,
                    persistent: false
                });
            }
        }
        
        // Check wind speed
        if (weatherData.current?.wind?.speed) {
            const windSpeed = weatherData.current.wind.speed;
            const windKmh = Math.round(windSpeed * 3.6); // Convert m/s to km/h
            
            console.log('Wind analysis:', {
                original: windSpeed,
                converted: windKmh,
                unit: 'm/s to km/h'
            });
            
            if (windKmh > 25) {
                alerts.push({
                    type: 'warning',
                    title: 'Wind Alert',
                    message: `Moderate to strong winds: ${windKmh} km/h. Be cautious with outdoor activities.`,
                    duration: 6000,
                    persistent: false
                });
            }
        }
        
        // Process all alerts
        alerts.forEach(alertData => {
            addAlert(alertData);
        });
        
        return alerts.length > 0;
    }
    
    return {
        // State - return reactive references, not values
        alerts,
        isVisible,
        
        // Methods
        addAlert,
        removeAlert,
        clearAllAlerts,
        showSuccess,
        showError,
        showWarning,
        showInfo,
        showTestAlert,
        
        // Realistic test scenarios
        showWeatherAlert,
        showIrrigationAlert,
        showHarvestAlert,
        
        // Real weather alert system
        checkWeatherConditions
    }
}
