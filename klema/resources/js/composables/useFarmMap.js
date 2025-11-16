import { ref } from 'vue'
import axios from 'axios'

export function useFarmMap() {
  const farmFeatures = ref([])
  const pointFeatures = ref([])
  const rawFarms = ref([])
  const soilTypes = ref([])
  const loading = ref(false)
  const error = ref(null)

  const loadMapData = async () => {
    loading.value = true
    error.value = null

    try {
      const { data } = await axios.get('/api/map/farms')
      rawFarms.value = data?.farms ?? []
      soilTypes.value = data?.soil_types ?? []
      farmFeatures.value = data?.farm_features?.features ?? []
      pointFeatures.value = data?.point_features?.features ?? []
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to load farm map data'
      console.error('Error loading farm map data:', err)
    } finally {
      loading.value = false
    }
  }

  return {
    farmFeatures,
    pointFeatures,
    rawFarms,
    soilTypes,
    loading,
    error,
    loadMapData
  }
}


