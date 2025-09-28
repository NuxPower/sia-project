import './bootstrap';
import { createApp, h, ref, onMounted, nextTick } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

// Fix Leaflet default icons
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon-2x.png',
    iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon.png',
    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png',
});

// Create the weather dashboard without navbars
const app = createApp({
    setup() {
        const mapContainer = ref(null);
        const map = ref(null);
        const currentWeather = ref(null);
        const forecast = ref([]);
        const searchLocation = ref('Maramag, Northern Mindanao');
        const activeView = ref('dashboard');
        const mapLoading = ref(true);
        const currentMarker = ref(null);
        const isLoadingWeather = ref(false);

        // Initialize map with enhanced weather layers
        const initMap = async () => {
            await nextTick();

            let container = mapContainer.value;
            if (!container) {
                container = document.getElementById('map-container');
            }

            if (container) {
                console.log('Initializing map with container:', container);

                container.style.width = '100%';
                container.style.height = '100%';
                container.style.minHeight = '100vh';

                // Initialize map centered on Mindanao, Philippines
                map.value = L.map(container, {
                    zoomControl: true,
                    attributionControl: true
                }).setView([7.5, 124.5], 7);

                console.log('Map instance created:', map.value);

                // Add click event listener for map pinning
                map.value.on('click', onMapClick);

                // Base layers
                const osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors',
                    maxZoom: 19
                }).addTo(map.value);

                const satelliteLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                    attribution: '© Esri',
                    opacity: 0.8,
                    maxZoom: 19
                });

                // Enhanced OpenWeatherMap layers with better visibility
                const cloudsLayer = L.tileLayer(
                    'https://tile.openweathermap.org/map/clouds_new/{z}/{x}/{y}.png?appid=42fd1052b23ee1f0ad1fe09ac2357b41',
                    {
                        attribution: '© OpenWeatherMap - Real Cloud Data',
                        opacity: 0.8,
                        maxZoom: 12,
                        className: 'enhanced-clouds-layer'
                    }
                );

                const precipitationLayer = L.tileLayer(
                    'https://tile.openweathermap.org/map/precipitation_new/{z}/{x}/{y}.png?appid=42fd1052b23ee1f0ad1fe09ac2357b41',
                    {
                        attribution: '© OpenWeatherMap - Real Precipitation Data',
                        opacity: 0.9,
                        maxZoom: 12,
                        className: 'enhanced-precipitation-layer'
                    }
                );

                const temperatureLayer = L.tileLayer(
                    'https://tile.openweathermap.org/map/temp_new/{z}/{x}/{y}.png?appid=42fd1052b23ee1f0ad1fe09ac2357b41',
                    {
                        attribution: '© OpenWeatherMap - Real Temperature Data',
                        opacity: 0.7,
                        maxZoom: 12,
                        className: 'enhanced-temperature-layer'
                    }
                );

                const windLayer = L.tileLayer(
                    'https://tile.openweathermap.org/map/wind_new/{z}/{x}/{y}.png?appid=42fd1052b23ee1f0ad1fe09ac2357b41',
                    {
                        attribution: '© OpenWeatherMap - Real Wind Data',
                        opacity: 0.6,
                        maxZoom: 12,
                        className: 'enhanced-wind-layer'
                    }
                );

                // Pressure layer
                const pressureLayer = L.tileLayer(
                    'https://tile.openweathermap.org/map/pressure_new/{z}/{x}/{y}.png?appid=42fd1052b23ee1f0ad1fe09ac2357b41',
                    {
                        attribution: '© OpenWeatherMap - Pressure Data',
                        opacity: 0.6,
                        maxZoom: 12,
                        className: 'enhanced-pressure-layer'
                    }
                );

                // NASA satellite layers
                const nasaTrueColor = L.tileLayer(
                    'https://gibs.earthdata.nasa.gov/wmts/epsg3857/best/VIIRS_SNPP_CorrectedReflectance_TrueColor/default/default/GoogleMapsCompatible_Level9/{z}/{y}/{x}.jpg',
                    {
                        attribution: 'Imagery © NASA EOSDIS GIBS',
                        maxZoom: 9,
                        opacity: 0.9
                    }
                );

                // Create base layers object
                const baseLayers = {
                    "🗺️ Street Map": osmLayer,
                    "🛰️ Satellite View": satelliteLayer,
                    "🌍 NASA True Color": nasaTrueColor
                };

                // Create overlay layers object with enhanced visibility
                const overlayLayers = {
                    "☁️ Live Clouds": cloudsLayer,
                    "🌧️ Live Precipitation": precipitationLayer,
                    "🌡️ Live Temperature": temperatureLayer,
                    "💨 Live Wind": windLayer,
                    "🌪️ Pressure Systems": pressureLayer
                };

                console.log('Enhanced weather overlay layers created');

                // Add enhanced layer control
                const layerControl = L.control.layers(baseLayers, overlayLayers, {
                    position: 'topright',
                    collapsed: true
                }).addTo(map.value);

                // Style the layer control for better visibility
                setTimeout(() => {
                    const layerControlElement = document.querySelector('.leaflet-control-layers');
                    const layerControlToggle = document.querySelector('.leaflet-control-layers-toggle');
                    const layersForm = document.querySelector('.leaflet-control-layers-list');

                    if (layerControlElement && layerControlToggle) {
                        // Enhanced positioning and styling for the main container
                        layerControlElement.style.position = 'fixed';
                        layerControlElement.style.top = '50%';
                        layerControlElement.style.right = '20px';
                        layerControlElement.style.transform = 'translateY(-50%)';
                        layerControlElement.style.zIndex = '1000';

                        // Enhanced toggle button design
                        layerControlToggle.style.width = '60px';
                        layerControlToggle.style.height = '60px';
                        layerControlToggle.style.borderRadius = '16px';
                        layerControlToggle.style.background = 'rgba(0, 0, 0, 0.9)';
                        layerControlToggle.style.border = '2px solid rgba(59, 130, 246, 0.5)';
                        layerControlToggle.style.display = 'flex';
                        layerControlToggle.style.alignItems = 'center';
                        layerControlToggle.style.justifyContent = 'center';
                        layerControlToggle.style.fontSize = '24px';
                        layerControlToggle.style.color = 'white';
                        layerControlToggle.style.cursor = 'pointer';
                        layerControlToggle.style.transition = 'all 0.3s ease';
                        layerControlToggle.style.boxShadow = '0 8px 32px rgba(0, 0, 0, 0.4)';
                        layerControlToggle.style.backdropFilter = 'blur(15px)';

                        // Enhanced hover effects
                        layerControlToggle.addEventListener('mouseenter', () => {
                            layerControlToggle.style.background = 'rgba(59, 130, 246, 0.3)';
                            layerControlToggle.style.borderColor = '#3b82f6';
                            layerControlToggle.style.transform = 'scale(1.1)';
                            layerControlToggle.style.boxShadow = '0 12px 40px rgba(59, 130, 246, 0.4)';
                        });

                        layerControlToggle.addEventListener('mouseleave', () => {
                            layerControlToggle.style.background = 'rgba(0, 0, 0, 0.9)';
                            layerControlToggle.style.borderColor = 'rgba(59, 130, 246, 0.5)';
                            layerControlToggle.style.transform = 'scale(1)';
                            layerControlToggle.style.boxShadow = '0 8px 32px rgba(0, 0, 0, 0.4)';
                        });

                        layerControlToggle.innerHTML = '🗺️';

                        // Style the expanded layer control with proper dimensions
                        if (layersForm) {
                            layersForm.style.background = 'rgba(0, 0, 0, 0.95)';
                            layersForm.style.borderRadius = '12px';
                            layersForm.style.border = '2px solid rgba(59, 130, 246, 0.3)';
                            layersForm.style.backdropFilter = 'blur(15px)';
                            layersForm.style.padding = '20px';
                            layersForm.style.minWidth = '300px';
                            layersForm.style.maxWidth = 'none';
                            layersForm.style.width = 'auto';
                            layersForm.style.height = 'auto';
                            layersForm.style.maxHeight = 'none';
                            layersForm.style.color = 'white';
                            layersForm.style.fontSize = '14px';
                            layersForm.style.boxShadow = '0 8px 32px rgba(0, 0, 0, 0.4)';
                            layersForm.style.overflow = 'visible';
                            layersForm.style.whiteSpace = 'nowrap';
                        }

                        // Also style the main control container
                        if (layerControlElement) {
                            layerControlElement.style.maxHeight = 'none';
                            layerControlElement.style.height = 'auto';
                            layerControlElement.style.overflow = 'visible';
                        }

                        console.log('Enhanced layer control positioned and styled');
                    }
                }, 200);

                // Add enhanced CSS for better layer control visibility
                const style = document.createElement('style');
                style.textContent = `
    .enhanced-clouds-layer {
        filter: contrast(1.4) brightness(1.2) saturate(1.3);
        mix-blend-mode: overlay;
    }
    
    .enhanced-precipitation-layer {
        filter: contrast(1.6) brightness(1.1) saturate(1.5) hue-rotate(220deg);
        mix-blend-mode: multiply;
    }
    
    .enhanced-temperature-layer {
        filter: contrast(1.3) brightness(1.2) saturate(1.4);
        mix-blend-mode: overlay;
    }
    
    .enhanced-wind-layer {
        filter: contrast(1.5) brightness(1.3) saturate(1.2) hue-rotate(180deg);
        mix-blend-mode: soft-light;
    }
    
    .enhanced-pressure-layer {
        filter: contrast(1.4) brightness(1.1) saturate(1.3) hue-rotate(90deg);
        mix-blend-mode: overlay;
    }
    
    /* Enhanced layer control styling */
    .leaflet-control-layers {
        box-shadow: none !important;
        border: none !important;
        background: transparent !important;
        max-height: none !important;
        height: auto !important;
        overflow: visible !important;
    }
    
    .leaflet-control-layers-expanded {
        padding: 0 !important;
        background: transparent !important;
        max-height: none !important;
        height: auto !important;
        overflow: visible !important;
    }
    
    .leaflet-control-layers-list {
        margin: 0 !important;
        position: relative;
        max-height: none !important;
        height: auto !important;
        overflow: visible !important;
    }
    
    .leaflet-control-layers label {
        color: white !important;
        font-weight: 500 !important;
        padding: 8px 0 !important;
        display: flex !important;
        align-items: center !important;
        white-space: nowrap !important;
        overflow: visible !important;
        text-overflow: clip !important;
        cursor: pointer !important;
        transition: all 0.2s ease !important;
        border-radius: 6px !important;
        margin: 2px 0 !important;
    }
    
    .leaflet-control-layers label:hover {
        background: rgba(59, 130, 246, 0.2) !important;
        padding-left: 4px !important;
    }
    
    .leaflet-control-layers input[type="checkbox"],
    .leaflet-control-layers input[type="radio"] {
        margin-right: 12px !important;
        transform: scale(1.3) !important;
        accent-color: #3b82f6 !important;
        cursor: pointer !important;
    }
    
    .leaflet-control-layers-separator {
        border-top: 1px solid rgba(59, 130, 246, 0.4) !important;
        margin: 15px 0 !important;
        opacity: 0.6 !important;
    }
    
    /* Base layers section */
    .leaflet-control-layers-base label {
        font-size: 14px !important;
        line-height: 1.4 !important;
    }
    
    /* Overlay layers section */
    .leaflet-control-layers-overlays label {
        font-size: 13px !important;
        line-height: 1.4 !important;
    }
    
    /* Fix for text cutting off */
    .leaflet-control-layers-expanded .leaflet-control-layers-list {
        width: auto !important;
        min-width: 300px !important;
        max-width: none !important;
        max-height: none !important;
        height: auto !important;
        overflow: visible !important;
    }
    
    /* Force all layer control elements to be fully visible */
    .leaflet-control-layers * {
        max-height: none !important;
        height: auto !important;
        overflow: visible !important;
    }
    
    /* Ensure proper spacing and visibility */
    .leaflet-control-layers-base,
    .leaflet-control-layers-overlays {
        margin: 0 !important;
        padding: 0 !important;
    }

    /* Map cursor style for clicking */
    .leaflet-container {
        cursor: crosshair !important;
    }

    .leaflet-clickable {
        cursor: pointer !important;
    }

    .leaflet-control {
        cursor: pointer !important;
    }
`;
                document.head.appendChild(style);

                // Force map resize
                setTimeout(() => {
                    if (map.value) {
                        map.value.invalidateSize();
                        console.log('Map invalidated and resized');
                        mapLoading.value = false;
                    }
                }, 100);
            } else {
                console.error('Map container not found!');
            }
        };

        // Handle map click events for pinning
        const onMapClick = async (e) => {
            const { lat, lng } = e.latlng;
            console.log('Map clicked at:', lat, lng);
            
            // Remove existing marker
            if (currentMarker.value) {
                map.value.removeLayer(currentMarker.value);
            }
            
            // Add new marker at clicked location
            currentMarker.value = L.marker([lat, lng]).addTo(map.value);
            
            // Show loading state
            isLoadingWeather.value = true;
            
            // Fetch weather data for the clicked coordinates
            try {
                await fetchWeatherDataByCoordinates(lat, lng);
            } catch (error) {
                console.error('Error fetching weather data for clicked location:', error);
                alert('Failed to fetch weather data for this location. Please try again.');
            } finally {
                isLoadingWeather.value = false;
            }
        };

        // Fetch weather data using coordinates
        const fetchWeatherDataByCoordinates = async (lat, lng) => {
            try {
                console.log('Fetching weather data for coordinates:', lat, lng);
                
                // URLs for current weather and forecast
                const currentUrl = `/api/weather/current?lat=${lat}&lon=${lng}`;
                const forecastUrl = `/api/weather/forecast?lat=${lat}&lon=${lng}&days=7`;
                const historyUrl = `/api/weather/history?lat=${lat}&lon=${lng}&days=3`;
                
                console.log('Current weather URL:', currentUrl);
                console.log('Forecast URL:', forecastUrl);
                console.log('History URL:', historyUrl);
                
                // Fetch current weather using coordinates
                const currentResponse = await fetch(currentUrl);
                console.log('Current response status:', currentResponse.status);
                
                if (!currentResponse.ok) {
                    const errorText = await currentResponse.text();
                    console.error('Current weather API error:', errorText);
                    throw new Error(`Failed to fetch current weather: ${currentResponse.status} - ${errorText}`);
                }
                
                const weatherData = await currentResponse.json();
                console.log('Raw current weather API response:', weatherData);
                
                // Check if we're getting cached/wrong data
                if (weatherData.coord) {
                    console.log('API returned coordinates:', weatherData.coord.lat, weatherData.coord.lon);
                    console.log('Requested coordinates:', lat, lng);
                    
                    // Check if coordinates match (within reasonable tolerance)
                    const latDiff = Math.abs(weatherData.coord.lat - lat);
                    const lonDiff = Math.abs(weatherData.coord.lon - lng);
                    
                    if (latDiff > 0.1 || lonDiff > 0.1) {
                        console.warn('⚠️ API returned different coordinates than requested!');
                        console.warn('Requested:', lat, lng);
                        console.warn('Received:', weatherData.coord.lat, weatherData.coord.lon);
                    }
                }
                
                currentWeather.value = weatherData;
                
                // Fetch forecast and history in parallel
                const [forecastResponse, historyResponse] = await Promise.all([
                    fetch(forecastUrl),
                    fetch(historyUrl).catch(() => null) // History might not be available
                ]);
                
                console.log('Forecast response status:', forecastResponse.status);
                
                if (!forecastResponse.ok) {
                    const errorText = await forecastResponse.text();
                    console.error('Forecast API error:', errorText);
                    throw new Error(`Failed to fetch forecast: ${forecastResponse.status} - ${errorText}`);
                }
                
                const forecastData = await forecastResponse.json();
                console.log('Raw forecast API response:', forecastData);
                
                // Get history data if available
                let historyData = [];
                if (historyResponse && historyResponse.ok) {
                    historyData = await historyResponse.json();
                    console.log('Raw history API response:', historyData);
                }
                
                // Combine history, today, and forecast into a timeline
                const timeline = createWeatherTimeline(historyData, weatherData, forecastData);
                forecast.value = timeline;
                
                // Update search location input with the location name
                if (weatherData.name) {
                    searchLocation.value = `${weatherData.name}, ${weatherData.sys.country}`;
                    console.log('Updated search location to:', searchLocation.value);
                }
                
                // Update marker popup with weather info
                if (currentMarker.value) {
                    const popupContent = `
                        <div style="text-align: center; min-width: 200px; font-family: Arial, sans-serif;">
                            <h3 style="margin: 0 0 10px 0; color: #333;">${weatherData.name}</h3>
                            <div style="font-size: 28px; font-weight: bold; margin: 10px 0; color: #2563eb;">
                                ${Math.round(weatherData.main.temp)}°C
                            </div>
                            <div style="text-transform: capitalize; margin-bottom: 10px; color: #666; font-size: 16px;">
                                ${weatherData.weather[0].description}
                            </div>
                            <div style="font-size: 12px; color: #888; line-height: 1.5;">
                                <div>💧 Humidity: ${weatherData.main.humidity}%</div>
                                <div>💨 Wind: ${weatherData.wind.speed} m/s</div>
                                <div>📊 Pressure: ${weatherData.main.pressure} mb</div>
                            </div>
                            <div style="margin-top: 8px; font-size: 10px; color: #aaa;">
                                📍 Clicked: ${lat.toFixed(4)}, ${lng.toFixed(4)}<br>
                                📍 API: ${weatherData.coord ? `${weatherData.coord.lat.toFixed(4)}, ${weatherData.coord.lon.toFixed(4)}` : 'N/A'}
                            </div>
                        </div>
                    `;
                    currentMarker.value.bindPopup(popupContent).openPopup();
                }
                
            } catch (error) {
                console.error('Error fetching weather data by coordinates:', error);
                
                // Show a more detailed error to help debug
                alert(`Failed to fetch weather data for coordinates ${lat.toFixed(4)}, ${lng.toFixed(4)}:\n\n${error.message}\n\nCheck the browser console for more details.`);
                throw error;
            }
        };

        // Create weather timeline combining history, today, and forecast
        const createWeatherTimeline = (historyData, currentWeather, forecastData) => {
            const timeline = [];
            const today = new Date();
            
            // Add historical data (3 days before today)
            for (let i = 3; i >= 1; i--) {
                const date = new Date(today);
                date.setDate(date.getDate() - i);
                
                // Try to find actual historical data, otherwise use mock
                const historyDay = historyData.find(h => h.date === date.toISOString().split('T')[0]);
                
                timeline.push({
                    date: date.toISOString().split('T')[0],
                    day: date.toLocaleDateString('en-US', { weekday: 'short' }),
                    temp_max: historyDay?.temp_max || Math.floor(currentWeather.main.temp + Math.random() * 4 - 2),
                    temp_min: historyDay?.temp_min || Math.floor(currentWeather.main.temp - 5 + Math.random() * 3),
                    condition: historyDay?.condition || currentWeather.weather[0].main,
                    icon: historyDay?.icon || currentWeather.weather[0].icon,
                    isHistory: true
                });
            }
            
            // Add today (center of timeline)
            timeline.push({
                date: today.toISOString().split('T')[0],
                day: 'Today',
                temp_max: Math.round(currentWeather.main.temp_max || currentWeather.main.temp + 2),
                temp_min: Math.round(currentWeather.main.temp_min || currentWeather.main.temp - 3),
                condition: currentWeather.weather[0].main,
                icon: currentWeather.weather[0].icon,
                isToday: true
            });
            
            // Add forecast data (next 3 days)
            const futureDays = forecastData.slice(1, 4); // Skip today if it's in forecast
            for (let i = 0; i < 3; i++) {
                const date = new Date(today);
                date.setDate(date.getDate() + i + 1);
                
                const forecastDay = futureDays[i];
                
                timeline.push({
                    date: date.toISOString().split('T')[0],
                    day: i === 0 ? 'Tomorrow' : date.toLocaleDateString('en-US', { weekday: 'short' }),
                    temp_max: forecastDay?.temp_max || Math.floor(currentWeather.main.temp + Math.random() * 6 - 3),
                    temp_min: forecastDay?.temp_min || Math.floor(currentWeather.main.temp - 4 + Math.random() * 3),
                    condition: forecastDay?.condition || currentWeather.weather[0].main,
                    icon: forecastDay?.icon || currentWeather.weather[0].icon,
                    isFuture: true
                });
            }
            
            return timeline;
        };

        // Fetch weather data from Laravel API (existing function, enhanced)
        const fetchWeatherData = async (location = 'Maramag,PH') => {
            isLoadingWeather.value = true;
            try {
                const currentResponse = await fetch(`/api/weather/current?location=${encodeURIComponent(location)}`);
                if (!currentResponse.ok) throw new Error('Failed to fetch current weather');
                
                const weatherData = await currentResponse.json();
                currentWeather.value = weatherData;

                const forecastResponse = await fetch(`/api/weather/forecast?location=${encodeURIComponent(location)}&days=7`);
                if (!forecastResponse.ok) throw new Error('Failed to fetch forecast');
                
                const forecastData = await forecastResponse.json();
                
                // Try to get history data
                const historyResponse = await fetch(`/api/weather/history?location=${encodeURIComponent(location)}&days=3`).catch(() => null);
                let historyData = [];
                if (historyResponse && historyResponse.ok) {
                    historyData = await historyResponse.json();
                }
                
                // Create the same timeline as coordinate-based search
                const timeline = createWeatherTimeline(historyData, weatherData, forecastData);
                forecast.value = timeline;

                if (map.value && weatherData.coord) {
                    map.value.setView([weatherData.coord.lat, weatherData.coord.lon], 10);
                    
                    // Remove existing marker
                    if (currentMarker.value) {
                        map.value.removeLayer(currentMarker.value);
                    }
                    
                    // Add new marker at the searched location
                    currentMarker.value = L.marker([weatherData.coord.lat, weatherData.coord.lon]).addTo(map.value);
                    
                    // Add popup to marker
                    const popupContent = `
                        <div style="text-align: center; min-width: 200px; font-family: Arial, sans-serif;">
                            <h3 style="margin: 0 0 10px 0; color: #333;">${weatherData.name}</h3>
                            <div style="font-size: 28px; font-weight: bold; margin: 10px 0; color: #2563eb;">
                                ${Math.round(weatherData.main.temp)}°C
                            </div>
                            <div style="text-transform: capitalize; margin-bottom: 10px; color: #666; font-size: 16px;">
                                ${weatherData.weather[0].description}
                            </div>
                            <div style="font-size: 12px; color: #888; line-height: 1.5;">
                                <div>💧 Humidity: ${weatherData.main.humidity}%</div>
                                <div>💨 Wind: ${weatherData.wind.speed} m/s</div>
                                <div>📊 Pressure: ${weatherData.main.pressure} mb</div>
                            </div>
                        </div>
                    `;
                    currentMarker.value.bindPopup(popupContent);
                }
            } catch (error) {
                console.error('Error fetching weather data:', error);
                alert('Failed to fetch weather data. Please check the location and try again.');
            } finally {
                isLoadingWeather.value = false;
            }
        };

        // Search for weather by location
        const searchWeather = () => {
            if (searchLocation.value.trim()) {
                fetchWeatherData(searchLocation.value);
            }
        };

        // Get formatted day label for forecast
        const getDayLabel = (day) => {
            if (!day.date) return day.day || 'Unknown';
            
            const today = new Date();
            const tomorrow = new Date(today);
            tomorrow.setDate(tomorrow.getDate() + 1);
            
            const dayDate = new Date(day.date);
            const todayStr = today.toISOString().split('T')[0];
            const tomorrowStr = tomorrow.toISOString().split('T')[0];
            const dayStr = day.date;
            
            if (dayStr === todayStr) {
                return 'Today';
            } else if (dayStr === tomorrowStr) {
                return 'Tomorrow';
            } else {
                // Show abbreviated day name
                const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                return dayNames[dayDate.getDay()];
            }
        };
        // Get weather icon for condition
        const getWeatherIcon = (condition) => {
            const iconMap = {
                'Clear': 'fas fa-sun',
                'Clouds': 'fas fa-cloud',
                'Rain': 'fas fa-cloud-rain',
                'Drizzle': 'fas fa-cloud-drizzle',
                'Thunderstorm': 'fas fa-bolt',
                'Snow': 'fas fa-snowflake',
                'Mist': 'fas fa-smog',
                'Fog': 'fas fa-smog',
                'Haze': 'fas fa-smog',
                'Dust': 'fas fa-smog',
                'Sand': 'fas fa-smog',
                'Ash': 'fas fa-smog',
                'Squall': 'fas fa-wind',
                'Tornado': 'fas fa-tornado'
            };
            return iconMap[condition] || 'fas fa-cloud';
        };

        // Set active view
        const setActiveView = (view) => {
            activeView.value = view;
        };

        onMounted(async () => {
            console.log('WeatherDashboard mounted');
            try {
                await new Promise(resolve => setTimeout(resolve, 500));

                let attempts = 0;
                const maxAttempts = 5;

                const tryInitMap = async () => {
                    attempts++;
                    console.log(`Map initialization attempt ${attempts}/${maxAttempts}`);

                    await initMap();

                    if (!map.value && attempts < maxAttempts) {
                        console.log('Map not initialized, retrying...');
                        await new Promise(resolve => setTimeout(resolve, 1000));
                        await tryInitMap();
                    } else if (map.value) {
                        console.log('Map successfully initialized!');
                        await fetchWeatherData();
                        console.log('Weather data fetched');
                    } else {
                        console.error('Failed to initialize map after all attempts');
                    }
                };

                await tryInitMap();
            } catch (error) {
                console.error('Error in onMounted:', error);
            }
        });

        // Expose methods to global scope for navbar integration
        window.vueApp = {
            setActiveView,
            searchWeather,
            searchLocation
        };

        return {
            mapContainer,
            currentWeather,
            forecast,
            searchLocation,
            activeView,
            mapLoading,
            currentMarker,
            isLoadingWeather,
            initMap,
            fetchWeatherData,
            fetchWeatherDataByCoordinates,
            createWeatherTimeline,
            searchWeather,
            getWeatherIcon,
            getDayLabel,
            setActiveView,
            onMapClick
        };
    },
    render() {
        return h('div', {
            style: {
                position: 'relative',
                width: '100vw',
                height: '100vh',
                background: 'linear-gradient(135deg, #0f172a, #1e293b)',
                overflow: 'hidden'
            }
        }, [
            // Search Bar
            h('div', {
                style: {
                    position: 'fixed',
                    top: '20px',
                    right: '20px',
                    zIndex: 1000
                }
            }, [
                h('div', {
                    style: {
                        display: 'flex',
                        alignItems: 'center',
                        background: 'rgba(0, 0, 0, 0.7)',
                        borderRadius: '12px',
                        padding: '12px 16px',
                        color: 'white',
                        minWidth: '300px'
                    }
                }, [
                    h('i', {
                        class: 'fas fa-search',
                        style: { marginRight: '10px', color: '#ccc' }
                    }),
                    h('input', {
                        style: {
                            background: 'none',
                            border: 'none',
                            color: 'white',
                            outline: 'none',
                            flex: 1,
                            fontSize: '14px'
                        },
                        placeholder: 'Search location or click map to pin...',
                        value: this.searchLocation,
                        onInput: (e) => this.searchLocation = e.target.value,
                        onKeyup: (e) => e.key === 'Enter' && this.searchWeather()
                    })
                ])
            ]),

            // Click instruction tooltip
            h('div', {
                style: {
                    position: 'fixed',
                    top: '80px',
                    right: '20px',
                    background: 'rgba(59, 130, 246, 0.9)',
                    color: 'white',
                    padding: '8px 12px',
                    borderRadius: '8px',
                    fontSize: '12px',
                    zIndex: 1000,
                    backdropFilter: 'blur(10px)',
                    border: '1px solid rgba(255, 255, 255, 0.2)',
                    animation: 'pulse 2s infinite'
                }
            }, '📍 Click anywhere on the map to pin weather data'),

            // Main Map Area (full screen, no navbars)
            h('div', {
                style: {
                    position: 'relative',
                    width: '100vw',
                    height: '100vh',
                    overflow: 'hidden'
                }
            }, [
                h('div', {
                    ref: this.mapContainer,
                    id: 'map-container',
                    'data-map-container': true,
                    style: {
                        width: '100%',
                        height: '100%',
                        minHeight: '100vh',
                        position: 'absolute',
                        top: 0,
                        left: 0,
                        zIndex: 1,
                        background: '#2c3e50'
                    }
                }),

                // Loading indicator for map
                this.mapLoading ? h('div', {
                    style: {
                        position: 'absolute',
                        top: '50%',
                        left: '50%',
                        transform: 'translate(-50%, -50%)',
                        color: 'white',
                        fontSize: '20px',
                        zIndex: 1000,
                        background: 'rgba(0, 0, 0, 0.9)',
                        padding: '30px 40px',
                        borderRadius: '16px',
                        border: '2px solid rgba(59, 130, 246, 0.3)',
                        backdropFilter: 'blur(15px)',
                        textAlign: 'center',
                        boxShadow: '0 8px 32px rgba(0, 0, 0, 0.4)'
                    }
                }, [
                    h('div', { style: { fontSize: '48px', marginBottom: '15px' } }, '🗺️'),
                    h('div', 'Loading Interactive Weather Map...'),
                    h('div', {
                        style: {
                            fontSize: '14px',
                            color: '#9ca3af',
                            marginTop: '10px'
                        }
                    }, 'Enhanced weather layers loading...')
                ]) : null,

                // Weather loading indicator
                this.isLoadingWeather ? h('div', {
                    style: {
                        position: 'absolute',
                        top: '50%',
                        left: '50%',
                        transform: 'translate(-50%, -50%)',
                        background: 'rgba(0, 0, 0, 0.9)',
                        color: 'white',
                        padding: '20px 30px',
                        borderRadius: '12px',
                        border: '2px solid rgba(59, 130, 246, 0.5)',
                        backdropFilter: 'blur(15px)',
                        textAlign: 'center',
                        zIndex: 2000,
                        boxShadow: '0 8px 32px rgba(0, 0, 0, 0.4)'
                    }
                }, [
                    h('div', { style: { fontSize: '24px', marginBottom: '10px' } }, '🌤️'),
                    h('div', { style: { fontSize: '16px' } }, 'Fetching weather data...'),
                    h('div', { 
                        style: { 
                            fontSize: '12px', 
                            color: '#9ca3af', 
                            marginTop: '8px' 
                        } 
                    }, 'Loading forecast for pinned location')
                ]) : null
            ]),

            // Enhanced 7-Day Timeline Bar - History + Today + Forecast
            h('div', {
                style: {
                    position: 'fixed',
                    bottom: '15px',
                    left: '50%',
                    transform: 'translateX(-50%)',
                    display: 'flex',
                    background: 'rgba(0, 0, 0, 0.9)',
                    borderRadius: '15px',
                    padding: '12px 16px',
                    gap: '12px',
                    zIndex: 1000,
                    backdropFilter: 'blur(15px)',
                    border: '2px solid rgba(59, 130, 246, 0.3)',
                    boxShadow: '0 6px 24px rgba(0, 0, 0, 0.4)',
                    maxWidth: '95vw',
                    overflowX: 'auto'
                }
            }, this.forecast.map((day, index) => {
                // Determine card type and styling
                const isHistory = day.isHistory;
                const isToday = day.isToday;
                const isFuture = day.isFuture;
                
                let cardStyle = {
                    display: 'flex',
                    flexDirection: 'column',
                    alignItems: 'center',
                    color: 'white',
                    minWidth: '75px',
                    padding: '10px 8px',
                    borderRadius: '10px',
                    transition: 'all 0.3s ease',
                    cursor: 'pointer',
                    position: 'relative'
                };
                
                // Apply different styles based on timeline position
                if (isHistory) {
                    cardStyle.background = 'rgba(156, 163, 175, 0.2)'; // Gray for history
                    cardStyle.border = '1px solid rgba(156, 163, 175, 0.3)';
                    cardStyle.opacity = '0.7';
                } else if (isToday) {
                    cardStyle.background = 'rgba(59, 130, 246, 0.3)'; // Blue for today
                    cardStyle.border = '2px solid rgba(59, 130, 246, 0.6)';
                    cardStyle.transform = 'scale(1.05)';
                    cardStyle.boxShadow = '0 4px 16px rgba(59, 130, 246, 0.3)';
                } else if (isFuture) {
                    cardStyle.background = 'rgba(34, 197, 94, 0.2)'; // Green for future
                    cardStyle.border = '1px solid rgba(34, 197, 94, 0.3)';
                }
                
                return h('div', {
                    key: day.date || index,
                    style: cardStyle,
                    onMouseenter: (e) => {
                        if (!isToday) {
                            e.target.style.transform = 'scale(1.02)';
                            e.target.style.background = isHistory ? 'rgba(156, 163, 175, 0.3)' : 
                                                       isFuture ? 'rgba(34, 197, 94, 0.3)' : 
                                                       e.target.style.background;
                        }
                    },
                    onMouseleave: (e) => {
                        if (!isToday) {
                            e.target.style.transform = 'scale(1)';
                            e.target.style.background = isHistory ? 'rgba(156, 163, 175, 0.2)' : 
                                                       isFuture ? 'rgba(34, 197, 94, 0.2)' : 
                                                       e.target.style.background;
                        }
                    }
                }, [
                    // Timeline indicator
                    isHistory ? h('div', {
                        style: {
                            position: 'absolute',
                            top: '-8px',
                            left: '50%',
                            transform: 'translateX(-50%)',
                            fontSize: '10px',
                            color: '#9ca3af',
                            background: 'rgba(0,0,0,0.8)',
                            padding: '2px 6px',
                            borderRadius: '4px'
                        }
                    }, '📊 Past') : isToday ? h('div', {
                        style: {
                            position: 'absolute',
                            top: '-8px',
                            left: '50%',
                            transform: 'translateX(-50%)',
                            fontSize: '10px',
                            color: '#60a5fa',
                            background: 'rgba(59, 130, 246, 0.2)',
                            padding: '2px 6px',
                            borderRadius: '4px',
                            border: '1px solid rgba(59, 130, 246, 0.4)'
                        }
                    }, '📍 Now') : h('div', {
                        style: {
                            position: 'absolute',
                            top: '-8px',
                            left: '50%',
                            transform: 'translateX(-50%)',
                            fontSize: '10px',
                            color: '#22c55e',
                            background: 'rgba(34, 197, 94, 0.2)',
                            padding: '2px 6px',
                            borderRadius: '4px'
                        }
                    }, '🔮 Future'),
                    
                    // Day label
                    h('div', {
                        style: {
                            fontSize: '12px',
                            marginBottom: '6px',
                            marginTop: '8px',
                            fontWeight: 'bold',
                            color: isHistory ? '#9ca3af' : 
                                   isToday ? '#60a5fa' : 
                                   isFuture ? '#22c55e' : '#9ca3af',
                            textAlign: 'center'
                        }
                    }, this.getDayLabel(day)),
                    
                    // Date
                    h('div', {
                        style: {
                            fontSize: '9px',
                            marginBottom: '8px',
                            color: '#6b7280',
                            textAlign: 'center'
                        }
                    }, day.date ? new Date(day.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) : ''),
                    
                    // Weather icon
                    h('div', {
                        style: {
                            fontSize: isToday ? '24px' : '20px',
                            marginBottom: '8px',
                            filter: `drop-shadow(0 2px 4px rgba(0,0,0,0.5)) ${isHistory ? 'grayscale(0.5)' : ''}`
                        }
                    }, [
                        h('i', { class: this.getWeatherIcon(day.condition) })
                    ]),
                    
                    // Temperature
                    h('div', {
                        style: {
                            display: 'flex',
                            flexDirection: 'column',
                            alignItems: 'center',
                            fontSize: isToday ? '12px' : '11px'
                        }
                    }, [
                        h('span', {
                            style: {
                                fontWeight: 'bold',
                                color: '#ffffff',
                                marginBottom: '2px'
                            }
                        }, `H: ${day.temp_max}°`),
                        h('span', {
                            style: { 
                                color: '#9ca3af',
                                fontSize: isToday ? '11px' : '10px'
                            }
                        }, `L: ${day.temp_min}°`)
                    ]),
                    
                    // Condition text
                    h('div', {
                        style: {
                            fontSize: '8px',
                            marginTop: '4px',
                            color: isHistory ? '#6b7280' : 
                                   isToday ? '#60a5fa' : 
                                   '#6b7280',
                            textAlign: 'center',
                            maxWidth: '60px',
                            overflow: 'hidden',
                            textOverflow: 'ellipsis',
                            whiteSpace: 'nowrap'
                        }
                    }, day.condition || '')
                ])
            })),

            // Timeline legend
            h('div', {
                style: {
                    position: 'fixed',
                    bottom: '100px',
                    left: '50%',
                    transform: 'translateX(-50%)',
                    display: 'flex',
                    background: 'rgba(0, 0, 0, 0.8)',
                    borderRadius: '8px',
                    padding: '6px 12px',
                    gap: '16px',
                    zIndex: 999,
                    fontSize: '10px',
                    color: '#9ca3af',
                    border: '1px solid rgba(59, 130, 246, 0.2)'
                }
            }, [
                h('div', { style: { display: 'flex', alignItems: 'center', gap: '4px' } }, [
                    h('div', { style: { width: '8px', height: '8px', background: 'rgba(156, 163, 175, 0.5)', borderRadius: '50%' } }),
                    h('span', 'Historical')
                ]),
                h('div', { style: { display: 'flex', alignItems: 'center', gap: '4px' } }, [
                    h('div', { style: { width: '8px', height: '8px', background: 'rgba(59, 130, 246, 0.8)', borderRadius: '50%' } }),
                    h('span', 'Today')
                ]),
                h('div', { style: { display: 'flex', alignItems: 'center', gap: '4px' } }, [
                    h('div', { style: { width: '8px', height: '8px', background: 'rgba(34, 197, 94, 0.5)', borderRadius: '50%' } }),
                    h('span', 'Forecast')
                ])
            ])
        ]);
    }
});

// Mount the app when DOM is ready
document.addEventListener('DOMContentLoaded', function () {
    console.log('DOM Content Loaded');
    const appElement = document.getElementById('app');
    console.log('App element:', appElement);

    if (appElement) {
        console.log('Mounting Vue app...');
        try {
            app.mount('#app');
            console.log('Vue app mounted successfully');
        } catch (error) {
            console.error('Error mounting Vue app:', error);
        }
    } else {
        console.error('App element not found');
    }
});