<template>
  <div class="mini-map-picker">
    <div class="mini-map-picker__controls">
      <input
        v-model="searchQuery"
        type="text"
        class="mini-map-picker__input"
        placeholder="Search location"
      />
      <button class="mini-map-picker__button" @click="performSearch" :disabled="isSearching">
        <i class="fas fa-search"></i>
        <span>Search</span>
      </button>
    </div>
    <div ref="mapContainer" class="mini-map-picker__map" :style="{ height }"></div>
  </div>
</template>

<script setup>
import { onMounted, onBeforeUnmount, ref, watch } from 'vue'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({ lat: null, lng: null })
  },
  height: {
    type: String,
    default: '220px'
  }
})

const emit = defineEmits(['update:modelValue'])

const mapContainer = ref(null)
const map = ref(null)
const marker = ref(null)
const searchQuery = ref('')
const isSearching = ref(false)
const defaultCenter = [7.5, 124.5]

const initializeMap = () => {
  if (!mapContainer.value) return

  map.value = L.map(mapContainer.value, {
    center: props.modelValue?.lat && props.modelValue?.lng
      ? [props.modelValue.lat, props.modelValue.lng]
      : defaultCenter,
    zoom: props.modelValue?.lat && props.modelValue?.lng ? 13 : 7,
    zoomControl: true,
    attributionControl: true
  })

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
  }).addTo(map.value)

  map.value.on('click', (event) => {
    setMarker(event.latlng.lat, event.latlng.lng)
  })

  if (props.modelValue?.lat && props.modelValue?.lng) {
    setMarker(props.modelValue.lat, props.modelValue.lng, false)
  }
}

const setMarker = (lat, lng, shouldEmit = true) => {
  if (!map.value) return

  const latLng = [lat, lng]

  if (!marker.value) {
    marker.value = L.marker(latLng, { draggable: true }).addTo(map.value)
    marker.value.on('dragend', () => {
      const { lat: dLat, lng: dLng } = marker.value.getLatLng()
      emit('update:modelValue', { lat: dLat, lng: dLng })
    })
  } else {
    marker.value.setLatLng(latLng)
  }

  map.value.setView(latLng, Math.max(map.value.getZoom(), 13))

  if (shouldEmit) {
    emit('update:modelValue', { lat, lng })
  }
}

const performSearch = async () => {
  if (!searchQuery.value.trim()) return

  isSearching.value = true

  try {
    const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(searchQuery.value.trim())}`, {
      headers: {
        'Accept': 'application/json',
        'User-Agent': 'KLEMA/1.0 (https://example.com)'
      }
    })

    const results = await response.json()

    if (Array.isArray(results) && results.length > 0) {
      const { lat, lon } = results[0]
      setMarker(parseFloat(lat), parseFloat(lon))
    } else {
      alert('No results found for that location.')
    }
  } catch (error) {
    console.error('Location search failed:', error)
    alert('Unable to search location right now. Please try again later.')
  } finally {
    isSearching.value = false
  }
}

watch(
  () => props.modelValue,
  (value) => {
    if (!map.value) return

    if (value && value.lat != null && value.lng != null) {
      const current = marker.value?.getLatLng()
      if (!current || current.lat !== value.lat || current.lng !== value.lng) {
        setMarker(value.lat, value.lng, false)
      }
    }
  },
  { deep: true }
)

onMounted(() => {
  initializeMap()
})

onBeforeUnmount(() => {
  if (!map.value) {
    return
  }

  // Stop any running animations (like wheel zoom inertia) before tearing down
  map.value.stop?.()

  // Clean up listeners to avoid callbacks firing after unmount
  map.value.off()
  marker.value?.off?.()

  // Remove marker explicitly to detach from the map before it disappears
  if (marker.value) {
    marker.value.remove()
    marker.value = null
  }

  map.value.remove()
  map.value = null
})
</script>

<style scoped>
.mini-map-picker {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.mini-map-picker__controls {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.mini-map-picker__input {
  flex: 1 1 220px;
  padding: 8px 10px;
  border-radius: 8px;
  border: 1px solid rgba(59, 130, 246, 0.45);
  background: rgba(15, 23, 42, 0.7);
  color: #e2e8f0;
  min-width: 0;
}

.mini-map-picker__button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 8px 14px;
  border-radius: 8px;
  border: 1px solid rgba(59, 130, 246, 0.5);
  background: linear-gradient(135deg, rgba(37, 99, 235, 0.65), rgba(59, 130, 246, 0.55));
  color: #e0edff;
  font-weight: 600;
  letter-spacing: 0.01em;
  cursor: pointer;
  flex: 0 0 auto;
}

.mini-map-picker__button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.mini-map-picker__map {
  width: 100%;
  border-radius: 12px;
  overflow: hidden;
  border: 1px solid rgba(59, 130, 246, 0.35);
}

@media (max-width: 640px) {
  .mini-map-picker__button {
    flex: 1 1 140px;
    justify-content: center;
  }
}
</style>




