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
    
    // Fetch real weather data and generate alerts
    const fetchRealWeatherAlerts = async (location = 'Butuan, Caraga, PH') => {
        try {
            console.log('Fetching real weather data for alerts...');
            
            // Import the weather API function dynamically to avoid circular imports
            const { fetchWeatherByLocation } = await import('./useWeatherAPI');
            
            const { current, forecastData } = await fetchWeatherByLocation(location);
            console.log('Real weather data fetched:', { current, forecastData });
            
            const weatherData = { current, forecast: forecastData };
            const hasAlerts = checkWeatherConditions(weatherData);
            
            if (!hasAlerts) {
                // If no alerts triggered, show current weather status
                const temp = Math.round(current.main.temp > 100 ? current.main.temp - 273.15 : current.main.temp);
                const windSpeed = Math.round((current.wind?.speed || 0) * 3.6);
                const humidity = current.main.humidity || 0;
                
                showInfo(
                    'Weather Status', 
                    `Current conditions: ${current.weather[0].description} at ${temp}°C, Wind: ${windSpeed} km/h, Humidity: ${humidity}%`,
                    { duration: 8000 }
                );
            }
            
            return hasAlerts;
        } catch (error) {
            console.error('Failed to fetch real weather data for alerts:', error);
            showError(
                'Weather Data Error',
                'Unable to fetch current weather data. Please check your connection and try again.',
                { duration: 8000 }
            );
            return false;
        }
    }
    
    // Legacy test alert method - now fetches real data
    const showTestAlert = async () => {
        console.log('showTestAlert called - fetching real weather data!');
        return await fetchRealWeatherAlerts();
    }
    
    // Realistic alert scenarios using real data
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
            'Weather conditions are perfect for harvesting Field C. Consider starting harvest operations.',
            { duration: 7000 }
        )
    }
    
    // Enhanced weather alert system with multi-day forecast analysis
    const checkWeatherConditions = (weatherData) => {
        if (!weatherData) return;
        
        console.log('Weather data received for alert analysis:', weatherData);
        
        const alerts = [];
        
        // Check current weather conditions
        checkCurrentWeatherConditions(weatherData.current, alerts);
        
        // Check forecast weather conditions for upcoming days
        checkForecastWeatherConditions(weatherData.forecast, alerts);
        
        // Process all alerts
        alerts.forEach(alertData => {
            addAlert(alertData);
        });
        
        return alerts.length > 0;
    }
    
    // Check current weather conditions from REAL API data
    const checkCurrentWeatherConditions = (currentWeather, alerts) => {
        if (!currentWeather?.weather?.[0]?.main) return;
        
        const mainWeather = currentWeather.weather[0].main.toLowerCase();
        const description = currentWeather.weather[0].description;
        
        // Rain alert (more sensitive)
        if (mainWeather.includes('rain')) {
            const severity = description.includes('heavy') || description.includes('extreme') ? 'Heavy' : 'Light';
            alerts.push({
                type: 'warning',
                title: `${severity} Rainfall Alert - Today`,
                message: `${severity.toLowerCase()} rainfall detected: ${description}. Consider postponing outdoor activities.`,
                duration: 8000,
                persistent: false
            });
        }
        
        // Storm alert
        if (mainWeather.includes('storm') || mainWeather.includes('thunderstorm')) {
            alerts.push({
                type: 'error',
                title: 'Storm Warning - Today',
                message: `Storm conditions detected: ${description}. Seek shelter immediately.`,
                duration: 10000,
                persistent: true // Critical alert - don't auto-dismiss
            });
        }
        
        // Snow alert
        if (mainWeather.includes('snow')) {
            alerts.push({
                type: 'warning',
                title: 'Snow Alert - Today',
                message: `Snow conditions detected: ${description}. Be cautious of icy conditions.`,
                duration: 6000,
                persistent: false
            });
        }
        
        // Cloudy conditions alert
        if (mainWeather.includes('cloud') && description.includes('overcast')) {
            alerts.push({
                type: 'info',
                title: 'Overcast Conditions - Today',
                message: `Overcast skies detected: ${description}. Limited sunlight may affect plant growth.`,
                duration: 5000,
                persistent: false
            });
        }
        
        // Check temperature extremes - handle both mock and real API data
        if (currentWeather?.main?.temp) {
            let tempC = currentWeather.main.temp;
            
            // Check if temperature is already in Celsius (mock data) or needs conversion from Kelvin (API data)
            if (tempC > 100) {
                // Temperature is in Kelvin, convert to Celsius
                tempC = Math.round(tempC - 273.15);
            } else {
                // Temperature is already in Celsius
                tempC = Math.round(tempC);
            }
            
            if (tempC > 30) {
                alerts.push({
                    type: 'warning',
                    title: 'High Temperature Alert - Today',
                    message: `Hot weather detected: ${tempC}°C. Consider staying hydrated and limiting outdoor activities.`,
                    duration: 7000,
                    persistent: false
                });
            } else if (tempC < 15) {
                alerts.push({
                    type: 'warning',
                    title: 'Cool Temperature Alert - Today',
                    message: `Cool conditions: ${tempC}°C. Dress warmly and consider protecting sensitive plants.`,
                    duration: 7000,
                    persistent: false
                });
            }
        }
        
        // Check wind speed - handle both mock and real API data
        if (currentWeather?.wind?.speed) {
            const windSpeed = currentWeather.wind.speed;
            const windKmh = Math.round(windSpeed * 3.6); // Convert m/s to km/h
            
            console.log('Wind analysis:', {
                original: windSpeed,
                converted: windKmh,
                unit: 'm/s to km/h'
            });
            
            if (windKmh > 25) {
                alerts.push({
                    type: 'warning',
                    title: 'Wind Alert - Today',
                    message: `Moderate to strong winds: ${windKmh} km/h. Be cautious with outdoor activities.`,
                    duration: 6000,
                    persistent: false
                });
            }
        }
    }
    
    // Check forecast weather conditions for upcoming days from REAL API data
    const checkForecastWeatherConditions = (forecastData, alerts) => {
        if (!forecastData || !Array.isArray(forecastData)) return;
        
        console.log('Analyzing forecast data for alerts:', forecastData);
        
        // Analyze forecast for the next 3-5 days
        const forecastDays = forecastData.slice(0, 5);
        
        forecastDays.forEach((day, index) => {
            const dayName = index === 0 ? 'Tomorrow' : 
                           index === 1 ? 'Day After Tomorrow' : 
                           `In ${index + 1} days`;
            
            console.log(`Analyzing forecast day ${index + 1}:`, day);
            
            // Check for severe weather in forecast - handle both mock and real API data
            const condition = day.condition || day.weather?.[0]?.main || day.weather?.[0]?.description || '';
            const conditionLower = condition.toLowerCase();
            
            if (conditionLower) {
                // Rain forecast alert
                if (conditionLower.includes('rain') || conditionLower.includes('drizzle')) {
                    const severity = conditionLower.includes('heavy') || conditionLower.includes('extreme') ? 'Heavy' : 'Light';
                    alerts.push({
                        type: 'warning',
                        title: `${severity} Rainfall Warning - ${dayName}`,
                        message: `${severity.toUpperCase()} rainfall expected ${dayName.toLowerCase()}. Plan accordingly for outdoor activities.`,
                        duration: 8000,
                        persistent: false
                    });
                }
                
                // Storm forecast alert
                if (conditionLower.includes('storm') || conditionLower.includes('thunderstorm') || conditionLower.includes('thunder')) {
                    alerts.push({
                        type: 'error',
                        title: `Storm Warning - ${dayName}`,
                        message: `Severe storm conditions expected ${dayName.toLowerCase()}. Take necessary precautions.`,
                        duration: 10000,
                        persistent: true
                    });
                }
                
                // Snow forecast alert
                if (conditionLower.includes('snow')) {
                    alerts.push({
                        type: 'warning',
                        title: `Snow Warning - ${dayName}`,
                        message: `Snow conditions expected ${dayName.toLowerCase()}. Prepare for potential travel disruptions.`,
                        duration: 7000,
                        persistent: false
                    });
                }
                
                // Fog/mist forecast alert
                if (conditionLower.includes('fog') || conditionLower.includes('mist') || conditionLower.includes('haze')) {
                    alerts.push({
                        type: 'info',
                        title: `Visibility Warning - ${dayName}`,
                        message: `Reduced visibility expected ${dayName.toLowerCase()}. Drive carefully and plan travel accordingly.`,
                        duration: 6000,
                        persistent: false
                    });
                }
            }
            
            // Check temperature extremes in forecast - handle both mock and real API data
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
                    alerts.push({
                        type: 'warning',
                        title: `Heat Wave Warning - ${dayName}`,
                        message: `High temperatures expected ${dayName.toLowerCase()}: up to ${tempMax}°C. Stay hydrated and limit outdoor activities.`,
                        duration: 8000,
                        persistent: false
                    });
                }
                
                // Cold temperature warning
                if (tempMin < 10) {
                    alerts.push({
                        type: 'warning',
                        title: `Cold Weather Warning - ${dayName}`,
                        message: `Cold temperatures expected ${dayName.toLowerCase()}: down to ${tempMin}°C. Protect sensitive plants and animals.`,
                        duration: 8000,
                        persistent: false
                    });
                }
                
                // Temperature swing warning
                if ((tempMax - tempMin) > 15) {
                    alerts.push({
                        type: 'info',
                        title: `Temperature Swing - ${dayName}`,
                        message: `Large temperature variation expected ${dayName.toLowerCase()}: ${tempMin}°C to ${tempMax}°C. Dress in layers.`,
                        duration: 6000,
                        persistent: false
                    });
                }
            }
        });
        
        // Check for extended periods of adverse weather
        checkExtendedWeatherPatterns(forecastDays, alerts);
    }
    
    // Check for extended periods of adverse weather from REAL API data
    const checkExtendedWeatherPatterns = (forecastDays, alerts) => {
        // Check for extended rain periods - handle both mock and real API data
        const rainyDays = forecastDays.filter(day => {
            const condition = day.condition || day.weather?.[0]?.main || day.weather?.[0]?.description || '';
            return condition.toLowerCase().includes('rain') || condition.toLowerCase().includes('drizzle');
        }).length;
        
        if (rainyDays >= 3) {
            alerts.push({
                type: 'warning',
                title: 'Extended Rainfall Period',
                message: `Rain expected for ${rainyDays} consecutive days. Consider drainage and flood prevention measures.`,
                duration: 10000,
                persistent: false
            });
        }
        
        // Check for extended heat periods - handle both mock and real API data
        const hotDays = forecastDays.filter(day => {
            let tempMax = day.temp_max || day.main?.temp_max || day.temp?.max;
            if (tempMax > 100) tempMax = tempMax - 273.15; // Convert from Kelvin
            return tempMax && tempMax > 30;
        }).length;
        
        if (hotDays >= 3) {
            alerts.push({
                type: 'warning',
                title: 'Extended Heat Wave',
                message: `High temperatures expected for ${hotDays} consecutive days. Ensure adequate irrigation and shade.`,
                duration: 10000,
                persistent: false
            });
        }
        
        // Check for extended cold periods - handle both mock and real API data
        const coldDays = forecastDays.filter(day => {
            let tempMin = day.temp_min || day.main?.temp_min || day.temp?.min;
            if (tempMin > 100) tempMin = tempMin - 273.15; // Convert from Kelvin
            return tempMin && tempMin < 10;
        }).length;
        
        if (coldDays >= 3) {
            alerts.push({
                type: 'warning',
                title: 'Extended Cold Period',
                message: `Cold temperatures expected for ${coldDays} consecutive days. Protect sensitive crops and animals.`,
                duration: 10000,
                persistent: false
            });
        }
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
        
        // Real weather data functions
        fetchRealWeatherAlerts,
        showWeatherAlert,
        showIrrigationAlert,
        showHarvestAlert,
        
        // Real weather alert system
        checkWeatherConditions
    }
}
