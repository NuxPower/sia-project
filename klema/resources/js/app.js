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

// Create the full weather dashboard
const app = createApp({
    setup() {
        const mapContainer = ref(null);
        const map = ref(null);
        const currentWeather = ref(null);
        const forecast = ref([]);
        const searchLocation = ref('Maramag, Northern Mindanao');
        const activeView = ref('dashboard');

        // Initialize map
        const initMap = async () => {
            await nextTick();
            
            // Try to find the map container by ID first
            let container = mapContainer.value;
            if (!container) {
                container = document.getElementById('map-container');
            }
            
            if (container) {
                console.log('Initializing map with container:', container);
                
                // Ensure the container has proper dimensions
                container.style.width = '100%';
                container.style.height = '100%';
                container.style.minHeight = '100vh';
                
                // Initialize map centered on Mindanao, Philippines
                map.value = L.map(container, {
                    zoomControl: true,
                    attributionControl: true
                }).setView([7.5, 124.5], 7);
                
                console.log('Map instance created:', map.value);
                
                // Add OpenStreetMap tiles
                const osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors',
                    maxZoom: 19
                }).addTo(map.value);
                
                console.log('OSM layer added:', osmLayer);

                // Add satellite overlay for weather visualization
                const satelliteLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                    attribution: '© Esri',
                    opacity: 0.3,
                    maxZoom: 19
                }).addTo(map.value);
                
                console.log('Satellite layer added:', satelliteLayer);
                
                // Force map to resize after initialization
                setTimeout(() => {
                    if (map.value) {
                        map.value.invalidateSize();
                        console.log('Map invalidated and resized');
                    }
                }, 100);
            } else {
                console.error('Map container not found! Trying alternative approach...');
                // Try alternative approach with direct DOM manipulation
                setTimeout(() => {
                    const altContainer = document.querySelector('[data-map-container]');
                    if (altContainer) {
                        console.log('Found alternative container:', altContainer);
                        initMap();
                    }
                }, 500);
            }
        };

        // Fetch weather data from Laravel API
        const fetchWeatherData = async (location = 'Maramag,PH') => {
            try {
                // Fetch current weather from Laravel API
                const currentResponse = await fetch(`/api/weather/current?location=${encodeURIComponent(location)}`);
                currentWeather.value = await currentResponse.json();
                
                // Fetch forecast from Laravel API
                const forecastResponse = await fetch(`/api/weather/forecast?location=${encodeURIComponent(location)}&days=7`);
                forecast.value = await forecastResponse.json();
                
                // Update map center if location changed
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
                // Wait for DOM to be fully ready
                await new Promise(resolve => setTimeout(resolve, 500));
                
                // Try multiple times to initialize map
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

        return {
            mapContainer,
            currentWeather,
            forecast,
            searchLocation,
            activeView,
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
                background: '#1a1a1a',
                overflow: 'hidden'
            }
        }, [
            // Sidebar Navigation
            h('div', {
                style: {
                    position: 'fixed',
                    left: '20px',
                    top: '50%',
                    transform: 'translateY(-50%)',
                    zIndex: 1000
                }
            }, [
                h('div', {
                    style: {
                        display: 'flex',
                        flexDirection: 'column',
                        gap: '15px'
                    }
                }, [
                    ['dashboard', 'fas fa-th'],
                    ['map', 'fas fa-map'],
                    ['calendar', 'fas fa-calendar'],
                    ['alerts', 'fas fa-bell'],
                    ['settings', 'fas fa-cog']
                ].map(([view, icon]) => 
                    h('div', {
                        style: {
                            width: '50px',
                            height: '50px',
                            background: 'rgba(0, 0, 0, 0.7)',
                            borderRadius: '12px',
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                            cursor: 'pointer',
                            transition: 'all 0.3s ease',
                            color: 'white',
                            fontSize: '18px'
                        },
                        onClick: () => this.setActiveView(view)
                    }, [
                        h('i', { class: icon })
                    ])
                ))
            ]),

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

            // Main Map Area
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
                h('div', {
                    style: {
                        position: 'absolute',
                        top: '50%',
                        left: '50%',
                        transform: 'translate(-50%, -50%)',
                        color: 'white',
                        fontSize: '18px',
                        zIndex: 1000,
                        background: 'rgba(0, 0, 0, 0.7)',
                        padding: '20px',
                        borderRadius: '10px'
                    }
                }, '🗺️ Loading Interactive Map...'),
                
                // Weather Overlay
                this.currentWeather ? h('div', {
                    style: {
                        position: 'absolute',
                        top: '50%',
                        left: '50%',
                        transform: 'translate(-50%, -50%)',
                        background: 'rgba(0, 0, 0, 0.8)',
                        borderRadius: '16px',
                        padding: '20px',
                        color: 'white',
                        textAlign: 'center',
                        zIndex: 1000,
                        backdropFilter: 'blur(10px)'
                    }
                }, [
                    h('div', {
                        style: { fontSize: '18px', fontWeight: 'bold', marginBottom: '10px' }
                    }, `${this.currentWeather.name}, ${this.currentWeather.sys.country}`),
                    h('div', {
                        style: { fontSize: '48px', fontWeight: 'bold', marginBottom: '10px' }
                    }, `${Math.round(this.currentWeather.main.temp)}°C`),
                    h('div', {
                        style: { fontSize: '16px', marginBottom: '15px', textTransform: 'capitalize' }
                    }, this.currentWeather.weather[0].description),
                    h('div', {
                        style: {
                            display: 'flex',
                            flexDirection: 'column',
                            gap: '5px',
                            fontSize: '14px',
                            color: '#ccc'
                        }
                    }, [
                        h('span', `Humidity: ${this.currentWeather.main.humidity}%`),
                        h('span', `Wind: ${this.currentWeather.wind.speed} m/s`),
                        h('span', `Pressure: ${this.currentWeather.main.pressure} mb`)
                    ])
                ]) : null
            ]),

            // 7-Day Forecast
            h('div', {
                style: {
                    position: 'fixed',
                    bottom: '20px',
                    left: '50%',
                    transform: 'translateX(-50%)',
                    display: 'flex',
                    background: 'rgba(0, 0, 0, 0.8)',
                    borderRadius: '16px',
                    padding: '16px',
                    gap: '20px',
                    zIndex: 1000,
                    backdropFilter: 'blur(10px)'
                }
            }, this.forecast.map(day => 
                h('div', {
                    style: {
                        display: 'flex',
                        flexDirection: 'column',
                        alignItems: 'center',
                        color: 'white',
                        minWidth: '80px'
                    }
                }, [
                    h('div', {
                        style: { fontSize: '12px', marginBottom: '8px', fontWeight: 'bold' }
                    }, day.day),
                    h('div', {
                        style: { fontSize: '20px', marginBottom: '8px' }
                    }, [
                        h('i', { class: this.getWeatherIcon(day.condition) })
                    ]),
                    h('div', {
                        style: {
                            display: 'flex',
                            flexDirection: 'column',
                            alignItems: 'center',
                            fontSize: '11px'
                        }
                    }, [
                        h('span', {
                            style: { fontWeight: 'bold' }
                        }, `H: ${day.temp_max}°`),
                        h('span', {
                            style: { color: '#ccc' }
                        }, `L: ${day.temp_min}°`)
                    ])
                ])
            ))
        ]);
    }
});

// Mount the app when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM Content Loaded');
    const appElement = document.getElementById('app');
    console.log('App element:', appElement);
    
    if (appElement) {
        console.log('Mounting Vue app...');
        try {
            app.mount('#app');
            console.log('Vue app mounted successfully');
            console.log('App element innerHTML after mount:', appElement.innerHTML);
        } catch (error) {
            console.error('Error mounting Vue app:', error);
        }
    } else {
        console.error('App element not found');
    }
});
