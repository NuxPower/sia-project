<template>
  <div class="weather-map-container">
    <div ref="mapContainer" class="map"></div>
  </div>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue';
import { initLeafletIcons } from '../utils/leafletConfig';
import { createMapLayers } from '../utils/mapLayers';
import { applyMapStyles } from '../utils/mapStyles';
import { useDisplaySettings } from '../composables/useDisplaySettings';

// Props removed - loading state is now handled by parent component

const emit = defineEmits(['map-click', 'map-ready', 'location-update']);

const mapContainer = ref(null);
const map = ref(null);
const currentMarker = ref(null);
const weatherLayers = ref({});
const baseMapLayers = ref({});
const farmBoundaryLayer = ref(null);
const farmPointLayer = ref(null);
const interactionMode = ref('weather');
const boundaryDrawing = ref(null);
const pointPlacement = ref(null);
const activeBaseLayer = ref(null);
const requestedBaseLayer = ref('street');

const { formatTemperature, formatWindSpeed } = useDisplaySettings();

let L = null;
let leafletLoader = null;

const ensureLeaflet = async () => {
  if (L) {
    return L;
  }

  if (!leafletLoader) {
    leafletLoader = Promise.all([
      import('leaflet'),
      import('leaflet/dist/leaflet.css')
    ]).then(([leafletModule]) => {
      L = leafletModule.default ?? leafletModule;
      initLeafletIcons(L);
      return L;
    });
  }

  return leafletLoader;
};

const baseLayerMap = {
  street: '🗺️ Street Map',
  satellite: '🛰️ Satellite View',
  nasa: '🌍 NASA True Color'
};

const clampLatitude = (value) => {
  if (!Number.isFinite(value)) {
    return null;
  }
  return Math.max(-90, Math.min(90, value));
};

const wrapLongitude = (value) => {
  if (!Number.isFinite(value)) {
    return null;
  }
  const normalized = ((value + 180) % 360 + 360) % 360;
  return normalized - 180;
};

const applyBaseLayer = (layerId) => {
  if (!map.value || !baseMapLayers.value) {
    return;
  }

  const targetKey = baseLayerMap[layerId] ? layerId : 'street';
  const layerName = baseLayerMap[targetKey];
  const targetLayer = baseMapLayers.value[layerName];

  if (!targetLayer) {
    return;
  }

  if (activeBaseLayer.value === targetKey && map.value.hasLayer(targetLayer)) {
    return;
  }

  Object.values(baseMapLayers.value).forEach((layer) => {
    if (map.value.hasLayer(layer)) {
      map.value.removeLayer(layer);
    }
  });

  targetLayer.addTo(map.value);
  activeBaseLayer.value = targetKey;
};

const setBaseLayer = (layerId) => {
  requestedBaseLayer.value = baseLayerMap[layerId] ? layerId : 'street';

  if (!map.value || !Object.keys(baseMapLayers.value).length) {
    return;
  }

  applyBaseLayer(requestedBaseLayer.value);
};

const initMap = async () => {
  await ensureLeaflet();
  await nextTick();
  
  if (!mapContainer.value) return;
  
  mapContainer.value.style.width = '100%';
  mapContainer.value.style.height = '100%';
  mapContainer.value.style.minHeight = '100vh';
  
  map.value = L.map(mapContainer.value, {
    zoomControl: false,
    attributionControl: true
  }).setView([7.5, 124.5], 7);

  L.control.zoom({
    position: 'bottomright'
  }).addTo(map.value);
  
  map.value.on('click', (e) => {
    handleMapClick(e.latlng);
  });
  
  const { baseLayers, overlayLayers } = createMapLayers(L);
  
  baseMapLayers.value = baseLayers;
  // Store overlay layers for programmatic control
  weatherLayers.value = overlayLayers;
  
  applyBaseLayer(requestedBaseLayer.value);
  
  farmBoundaryLayer.value = L.layerGroup().addTo(map.value);
  farmPointLayer.value = L.layerGroup().addTo(map.value);

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
    map.value.setView([lat, lon], zoom, { animate: false });
  }
};

const updateMarker = (lat, lon, weatherData) => {
  if (!map.value) return;
  
  if (!currentMarker.value) {
    currentMarker.value = L.marker([lat, lon], { draggable: true }).addTo(map.value);
    currentMarker.value.on('dragend', () => {
      const { lat: newLat, lng: newLng } = currentMarker.value.getLatLng();
      emit('location-update', { lat: newLat, lon: newLng });
    });
  } else {
    currentMarker.value.setLatLng([lat, lon]);
  }
  
  const popupContent = createPopupContent(lat, lon, weatherData);
  currentMarker.value.bindPopup(popupContent).openPopup();

  emit('location-update', { lat, lon });
};

const createPopupContent = (lat, lon, weatherData) => {
  const temp = formatTemperature(weatherData.main.temp, { decimals: 0 });
  const wind = formatWindSpeed(weatherData.wind.speed, { decimals: 1 });
  
  return `
    <div style="text-align: center; min-width: 200px; font-family: Arial, sans-serif;">
      <h3 style="margin: 0 0 10px 0; color: #333;">${weatherData.name}</h3>
      <div style="font-size: 28px; font-weight: bold; margin: 10px 0; color: #2563eb;">
        ${temp}
      </div>
      <div style="text-transform: capitalize; margin-bottom: 10px; color: #666; font-size: 16px;">
        ${weatherData.weather[0].description}
      </div>
      <div style="font-size: 12px; color: #888; line-height: 1.5;">
        <div>💧 Humidity: ${weatherData.main.humidity}%</div>
        <div>💨 Wind: ${wind}</div>
        <div>📊 Pressure: ${weatherData.main.pressure} mb</div>
      </div>
    </div>
  `;
};

const handleMapClick = ({ lat, lng }) => {
  if (boundaryDrawing.value) {
    addBoundaryVertex(lat, lng);
    return;
  }

  if (pointPlacement.value) {
    finalizePointPlacement(lat, lng);
    return;
  }

  const normalizedLat = clampLatitude(lat);
  const normalizedLng = wrapLongitude(lng);

  if (normalizedLat === null || normalizedLng === null) {
    console.warn('Ignoring map click: invalid coordinates', { lat, lng });
    return;
  }

  emit('map-click', { lat: normalizedLat, lng: normalizedLng });
};

const renderFarmOverlays = ({ farmFeatures = [], pointFeatures = [] }) => {
  if (!map.value) return;

  farmBoundaryLayer.value.clearLayers();
  farmPointLayer.value.clearLayers();

  if (farmFeatures.length) {
    L.geoJSON({
      type: 'FeatureCollection',
      features: farmFeatures
    }, {
      style: feature => ({
        color: getFarmColor(feature.properties?.farm_id),
        weight: feature.properties?.type === 'centroid' ? 0 : 2,
        opacity: 0.8,
        fillOpacity: 0.15
      }),
      pointToLayer: (feature, latlng) => {
        return L.circleMarker(latlng, {
          radius: 6,
          color: getFarmColor(feature.properties?.farm_id),
          fillOpacity: 0.9
        });
      },
      onEachFeature: (feature, layer) => {
        if (!feature?.properties) {
          return;
        }

        const { farm_name, size_hectares, soil_type, description } = feature.properties;
        const popup = `
          <div style="min-width: 220px;">
            <h3 style="margin: 0 0 8px 0; color: #1e40af;">${farm_name ?? 'Unnamed Farm'}</h3>
            ${size_hectares ? `<div><strong>Size:</strong> ${Number(size_hectares).toFixed(2)} ha</div>` : ''}
            ${soil_type ? `<div><strong>Soil:</strong> ${soil_type}</div>` : ''}
            ${description ? `<div style="margin-top: 6px;">${description}</div>` : ''}
          </div>
        `;
        layer.bindPopup(popup);
      }
    }).addTo(farmBoundaryLayer.value);
  }

  if (pointFeatures.length) {
    L.geoJSON({
      type: 'FeatureCollection',
      features: pointFeatures
    }, {
      pointToLayer: (feature, latlng) => {
        return L.marker(latlng, {
          icon: L.divIcon({
            className: 'farm-point-icon',
            html: `<div class="farm-point-marker">${feature.properties?.label?.[0] ?? 'P'}</div>`
          })
        });
      },
      onEachFeature: (feature, layer) => {
        const { label, point_type } = feature.properties ?? {};
        const popup = `
          <div style="min-width:180px;">
            <strong>${label ?? 'Point'}</strong>
            ${point_type ? `<div style="margin-top:4px;">Type: ${point_type}</div>` : ''}
          </div>
        `;
        layer.bindPopup(popup);
      }
    }).addTo(farmPointLayer.value);
  }
};

const startBoundaryDrawing = ({ initialCoordinates = [], onComplete, onCancel }) => {
  if (!map.value) return null;

  interactionMode.value = 'boundary';
  clearBoundaryDrawing();

  const normalized = initialCoordinates.map(point => [point.lat, point.lng]);
  const polygonLayer = L.polygon(normalized, {
    color: '#2563eb',
    weight: 2,
    opacity: 0.9,
    fillOpacity: 0.15
  });

  boundaryDrawing.value = {
    points: [...normalized],
    polygonLayer,
    onComplete,
    onCancel
  };

  if (normalized.length) {
    polygonLayer.addTo(map.value);
  }

  return {
    finish: finishBoundaryDrawing,
    cancel: cancelBoundaryDrawing,
    reset: () => resetBoundaryDrawing(initialCoordinates)
  };
};

const addBoundaryVertex = (lat, lng) => {
  if (!boundaryDrawing.value) return;

  boundaryDrawing.value.points.push([lat, lng]);

  if (!map.value) return;

  if (!map.value.hasLayer(boundaryDrawing.value.polygonLayer)) {
    boundaryDrawing.value.polygonLayer.addTo(map.value);
  }

  boundaryDrawing.value.polygonLayer.setLatLngs([boundaryDrawing.value.points]);
};

const deleteLastBoundaryVertex = () => {
  if (!boundaryDrawing.value || !boundaryDrawing.value.points.length) return;

  boundaryDrawing.value.points.pop();

  if (!map.value) return;

  if (boundaryDrawing.value.points.length < 3) {
    // Remove polygon if less than 3 points (minimum for a polygon)
    if (map.value.hasLayer(boundaryDrawing.value.polygonLayer)) {
      map.value.removeLayer(boundaryDrawing.value.polygonLayer);
    }
  } else {
    boundaryDrawing.value.polygonLayer.setLatLngs([boundaryDrawing.value.points]);
    if (!map.value.hasLayer(boundaryDrawing.value.polygonLayer)) {
      boundaryDrawing.value.polygonLayer.addTo(map.value);
    }
  }
};

const getBoundaryPointCount = () => {
  return boundaryDrawing.value?.points?.length ?? 0;
};

const finishBoundaryDrawing = () => {
  if (!boundaryDrawing.value) return;

  const { points, onComplete } = boundaryDrawing.value;

  if (points.length < 3) {
    alert('A boundary requires at least three points.');
    return;
  }

  const formatted = points.map(([lat, lng]) => ({ lat, lng }));

  if (onComplete) {
    onComplete(formatted);
  }

  clearBoundaryDrawing();
  interactionMode.value = 'weather';
};

const cancelBoundaryDrawing = () => {
  if (boundaryDrawing.value?.onCancel) {
    boundaryDrawing.value.onCancel();
  }

  clearBoundaryDrawing();
  interactionMode.value = 'weather';
};

const resetBoundaryDrawing = (initialCoordinates = []) => {
  if (!boundaryDrawing.value) {
    return;
  }

  const normalized = initialCoordinates.map(point => [point.lat, point.lng]);
  boundaryDrawing.value.points = [...normalized];

  boundaryDrawing.value.polygonLayer.setLatLngs([normalized]);

  if (!normalized.length) {
    boundaryDrawing.value.polygonLayer.removeFrom(map.value);
  } else if (!map.value.hasLayer(boundaryDrawing.value.polygonLayer)) {
    boundaryDrawing.value.polygonLayer.addTo(map.value);
  }
};

const clearBoundaryDrawing = () => {
  if (boundaryDrawing.value?.polygonLayer && map.value?.hasLayer(boundaryDrawing.value.polygonLayer)) {
    map.value.removeLayer(boundaryDrawing.value.polygonLayer);
  }

  boundaryDrawing.value = null;
};

const startPointPlacement = ({ onPlace }) => {
  interactionMode.value = 'point';
  pointPlacement.value = {
    onPlace
  };
};

const finalizePointPlacement = (lat, lng) => {
  if (!pointPlacement.value) {
    return;
  }

  const { onPlace } = pointPlacement.value;
  interactionMode.value = 'weather';
  pointPlacement.value = null;

  if (onPlace) {
    onPlace({ lat, lng });
  }
};

const cancelPointPlacement = () => {
  interactionMode.value = 'weather';
  pointPlacement.value = null;
};

const getFarmColor = (farmId) => {
  const colors = ['#2563eb', '#16a34a', '#dc2626', '#7c3aed', '#d97706', '#0891b2'];
  const index = Math.abs(parseInt(farmId ?? 0, 10)) % colors.length;
  return colors[index];
};

onMounted(async () => {
  await ensureLeaflet();
  await new Promise(resolve => setTimeout(resolve, 500));
  await initMap();
});

defineExpose({
  moveToLocation,
  updateMarker,
  toggleWeatherLayer,
  setBaseLayer,
  renderFarmOverlays,
  startBoundaryDrawing,
  finishBoundaryDrawing,
  cancelBoundaryDrawing,
  resetBoundaryDrawing,
  deleteLastBoundaryVertex,
  getBoundaryPointCount,
  startPointPlacement,
  cancelPointPlacement
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

<style>
.farm-point-icon {
  display: flex;
  align-items: center;
  justify-content: center;
}

.farm-point-marker {
  background: #1f2937;
  color: #fff;
  font-size: 12px;
  width: 22px;
  height: 22px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px solid #fff;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

.leaflet-bottom.leaflet-right {
  margin-bottom: 16px;
  margin-right: 16px;
}
</style>