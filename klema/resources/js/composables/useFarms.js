import { ref } from 'vue'
import axios from 'axios'

const API_BASE_URL = '/api'

export function useFarms() {
    const farms = ref([])
    const loading = ref(false)
    const error = ref(null)

    // Fetch all farms for the authenticated user
    const fetchFarms = async () => {
        loading.value = true
        error.value = null
        
        try {
            const response = await axios.get(`${API_BASE_URL}/farms`)
            farms.value = response.data.farms || []
        } catch (err) {
            error.value = err.response?.data?.message || 'Failed to fetch farms'
            console.error('Error fetching farms:', err)
        } finally {
            loading.value = false
        }
    }

    return {
        // State
        farms,
        loading,
        error,
        
        // Methods
        fetchFarms
    }
}
