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

                // Replace the layer control styling section in your initMap function with this:

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

        // Fetch weather data from Laravel API
        const fetchWeatherData = async (location = 'Maramag,PH') => {
            try {
                const currentResponse = await fetch(`/api/weather/current?location=${encodeURIComponent(location)}`);
                currentWeather.value = await currentResponse.json();

                const forecastResponse = await fetch(`/api/weather/forecast?location=${encodeURIComponent(location)}&days=7`);
                forecast.value = await forecastResponse.json();

                if (map.value && currentWeather.value.coord) {
                    map.value.setView([currentWeather.value.coord.lat, currentWeather.value.coord.lon], 10);
                }
            } catch (error) {
                console.error('Error fetching weather data:', error);
            }
        };

        // Search for weather by location
        const searchWeather = () => {
            if (searchLocation.value.trim()) {
                fetchWeatherData(searchLocation.value);
            }
        };

        // Get weather icon class
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
            initMap,
            fetchWeatherData,
            searchWeather,
            getWeatherIcon,
            setActiveView
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
                        placeholder: 'Search location...',
                        value: this.searchLocation,
                        onInput: (e) => this.searchLocation = e.target.value,
                        onKeyup: (e) => e.key === 'Enter' && this.searchWeather()
                    })
                ])
            ]),

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

                // Loading indicator
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
                ]) : null
            ]),

            // Enhanced 7-Day Forecast Bar - Compact Version
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
                    gap: '18px',
                    zIndex: 1000,
                    backdropFilter: 'blur(15px)',
                    border: '2px solid rgba(59, 130, 246, 0.3)',
                    boxShadow: '0 6px 24px rgba(0, 0, 0, 0.4)'
                }
            }, this.forecast.map(day =>
                h('div', {
                    style: {
                        display: 'flex',
                        flexDirection: 'column',
                        alignItems: 'center',
                        color: 'white',
                        minWidth: '65px',
                        padding: '8px',
                        borderRadius: '10px',
                        background: 'rgba(255, 255, 255, 0.05)',
                        transition: 'all 0.3s ease'
                    }
                }, [
                    h('div', {
                        style: {
                            fontSize: '11px',
                            marginBottom: '6px',
                            fontWeight: 'bold',
                            color: '#9ca3af'
                        }
                    }, day.day),
                    h('div', {
                        style: {
                            fontSize: '18px',
                            marginBottom: '6px',
                            filter: 'drop-shadow(0 2px 4px rgba(0,0,0,0.5))'
                        }
                    }, [
                        h('i', { class: this.getWeatherIcon(day.condition) })
                    ]),
                    h('div', {
                        style: {
                            display: 'flex',
                            flexDirection: 'column',
                            alignItems: 'center',
                            fontSize: '10px'
                        }
                    }, [
                        h('span', {
                            style: {
                                fontWeight: 'bold',
                                color: '#ffffff'
                            }
                        }, `H: ${day.temp_max}°`),
                        h('span', {
                            style: { color: '#9ca3af' }
                        }, `L: ${day.temp_min}°`)
                    ])
                ])
            ))
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