<template>
  <div class="weather-map-container">
    <div ref="mapContainer" class="map"></div>
    <LoadingIndicator
      v-if="isLoading"
      message="Loading Interactive Weather Map..."
      subtitle="Enhanced weather layers loading..."
      icon="🗺️"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import LoadingIndicator from './LoadingIndicator.vue';
import { initLeafletIcons } from '../utils/leafletConfig';
import { createMapLayers } from '../utils/mapLayers';
import { applyMapStyles } from '../utils/mapStyles';

const props = defineProps({
  isLoading: Boolean
});

const emit = defineEmits(['map-click', 'map-ready']);

const mapContainer = ref(null);
const map = ref(null);
const currentMarker = ref(null);
const weatherLayers = ref({});
const layerControl = ref(null);

const initMap = async () => {
  await nextTick();
  
  if (!mapContainer.value) return;
  
  mapContainer.value.style.width = '100%';
  mapContainer.value.style.height = '100%';
  mapContainer.value.style.minHeight = '100vh';
  
  map.value = L.map(mapContainer.value, {
    zoomControl: true,
    attributionControl: true
  }).setView([7.5, 124.5], 7);
  
  map.value.on('click', (e) => {
    emit('map-click', { lat: e.latlng.lat, lng: e.latlng.lng });
  });
  
  const { baseLayers, overlayLayers } = createMapLayers();
  
  // Store overlay layers for programmatic control
  weatherLayers.value = overlayLayers;
  
  // Add default base layer
  baseLayers['🗺️ Street Map'].addTo(map.value);
  
  // Add layer control (now hidden since we have custom controls)
  layerControl.value = L.control.layers(baseLayers, overlayLayers, {
    position: 'topright',
    collapsed: true
  }).addTo(map.value);
  
  // Apply custom styles
  applyMapStyles();
  
  setTimeout(() => {
    if (map.value) {
      map.value.invalidateSize();
      emit('map-ready');
    }
  }, 100);
};

const toggleWeatherLayer = (layerId, active) => {
  if (!map.value) return;
  
  const layerMap = {
    'clouds': '☁️ Live Clouds',
    'precipitation': '🌧️ Live Precipitation',
    'temperature': '🌡️ Live Temperature',
    'wind': '💨 Live Wind',
    'pressure': '🌪️ Pressure Systems'
  };
  
  const layerName = layerMap[layerId];
  const layer = weatherLayers.value[layerName];
  
  if (layer) {
    if (active) {
      map.value.addLayer(layer);
    } else {
      map.value.removeLayer(layer);
    }
  }
};

const moveToLocation = (lat, lon, zoom = 10) => {
  if (map.value) {
    map.value.setView([lat, lon], zoom);
  }
};

const updateMarker = (lat, lon, weatherData) => {
  if (!map.value) return;
  
  if (currentMarker.value) {
    map.value.removeLayer(currentMarker.value);
  }
  
  currentMarker.value = L.marker([lat, lon]).addTo(map.value);
  
  const popupContent = createPopupContent(lat, lon, weatherData);
  currentMarker.value.bindPopup(popupContent).openPopup();
};

const createPopupContent = (lat, lon, weatherData) => {
  return `
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
};

onMounted(async () => {
  initLeafletIcons();
  await new Promise(resolve => setTimeout(resolve, 500));
  await initMap();
});

defineExpose({
  moveToLocation,
  updateMarker,
  toggleWeatherLayer
});
</script>

<style scoped>
.weather-map-container {
  position: relative;
  width: 100vw;
  height: 100vh;
  overflow: hidden;
}

.map {
  width: 100%;
  height: 100%;
  min-height: 100vh;
  position: absolute;
  top: 0;
  left: 0;
  z-index: 1;
  background: #2c3e50;
}
</style>