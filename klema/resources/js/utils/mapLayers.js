const OPENWEATHER_API_KEY = import.meta.env.VITE_OPENWEATHER_API_KEY;

if (!OPENWEATHER_API_KEY) {
  console.error('OpenWeather API key not configured. Add VITE_OPENWEATHER_API_KEY to .env file.');
}

const getNasaTrueColorDate = (daysAgo = 2) => {
  const date = new Date();
  date.setUTCDate(date.getUTCDate() - Math.max(0, daysAgo));
  return date.toISOString().split('T')[0];
};

export function createMapLayers(L) {
  if (!L) {
    throw new Error('Leaflet instance is required before creating map layers.');
  }

  const nasaTrueColorDate = getNasaTrueColorDate(2);

  const baseLayers = {
    "🗺️ Street Map": L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '© OpenStreetMap contributors',
      maxZoom: 19
    }),
    "🛰️ Satellite View": L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
      attribution: '© Esri',
      opacity: 0.8,
      maxZoom: 19
    }),
    "🌍 NASA True Color": L.tileLayer(
      `https://gibs.earthdata.nasa.gov/wmts/epsg3857/best/VIIRS_SNPP_CorrectedReflectance_TrueColor/default/${nasaTrueColorDate}/GoogleMapsCompatible_Level9/{z}/{y}/{x}.jpg`,
      {
        attribution: 'Imagery © NASA EOSDIS GIBS',
        maxZoom: 9,
        minZoom: 2,
        opacity: 0.9,
        noWrap: true,
        bounds: [
          [-85.05112878, -180],
          [85.05112878, 180]
        ]
      }
    )
  };
  
  const overlayLayers = {
    "☁️ Live Clouds": L.tileLayer(
      `https://tile.openweathermap.org/map/clouds_new/{z}/{x}/{y}.png?appid=${OPENWEATHER_API_KEY}`,
      {
        attribution: '© OpenWeatherMap',
        opacity: 0.8,
        maxZoom: 12,
        className: 'enhanced-clouds-layer'
      }
    ),
    "🌧️ Live Precipitation": L.tileLayer(
      `https://tile.openweathermap.org/map/precipitation_new/{z}/{x}/{y}.png?appid=${OPENWEATHER_API_KEY}`,
      {
        attribution: '© OpenWeatherMap',
        opacity: 0.85,
        maxZoom: 12,
        className: 'enhanced-precipitation-layer'
      }
    ),
    "🌡️ Live Temperature": L.tileLayer(
      `https://tile.openweathermap.org/map/temp_new/{z}/{x}/{y}.png?appid=${OPENWEATHER_API_KEY}`,
      {
        attribution: '© OpenWeatherMap',
        opacity: 0.8,
        maxZoom: 12,
        className: 'enhanced-temperature-layer'
      }
    ),
    "💨 Live Wind": L.tileLayer(
      `https://tile.openweathermap.org/map/wind_new/{z}/{x}/{y}.png?appid=${OPENWEATHER_API_KEY}`,
      {
        attribution: '© OpenWeatherMap',
        opacity: 0.8,
        maxZoom: 12,
        className: 'enhanced-wind-layer'
      }
    ),
    "🌪️ Pressure Systems": L.tileLayer(
      `https://tile.openweathermap.org/map/pressure_new/{z}/{x}/{y}.png?appid=${OPENWEATHER_API_KEY}`,
      {
        attribution: '© OpenWeatherMap',
        opacity: 0.8,
        maxZoom: 12,
        className: 'enhanced-pressure-layer'
      }
    )
  };
  
  return { baseLayers, overlayLayers };
}