import { ref, reactive } from 'vue'
import axios from 'axios'

const API_BASE_URL = '/api'

export function useAlerts() {
    const alerts = ref([])
    const forecastWarnings = ref([])
    const loading = ref(false)
    const error = ref(null)

    // Fetch all alerts for the authenticated user
    const fetchAlerts = async () => {
        loading.value = true
        error.value = null
        
        try {
            const response = await axios.get(`${API_BASE_URL}/alerts`)
            alerts.value = response.data.alerts || []
        } catch (err) {
            error.value = err.response?.data?.message || 'Failed to fetch alerts'
            console.error('Error fetching alerts:', err)
        } finally {
            loading.value = false
        }
    }

    // Fetch only active (unresolved) alerts
    const fetchActiveAlerts = async () => {
        loading.value = true
        error.value = null
        
        try {
            const response = await axios.get(`${API_BASE_URL}/alerts/active`)
            alerts.value = response.data.alerts || []
        } catch (err) {
            error.value = err.response?.data?.message || 'Failed to fetch active alerts'
            console.error('Error fetching active alerts:', err)
        } finally {
            loading.value = false
        }
    }

    // Create a new alert
    const createAlert = async (alertData) => {
        loading.value = true
        error.value = null
        
        try {
            const response = await axios.post(`${API_BASE_URL}/alerts`, alertData)
            
            if (response.data.success) {
                // Add the new alert to the beginning of the list
                alerts.value.unshift(response.data.alert)
                return response.data.alert
            }
        } catch (err) {
            error.value = err.response?.data?.message || 'Failed to create alert'
            console.error('Error creating alert:', err)
            throw err
        } finally {
            loading.value = false
        }
    }

    // Resolve an alert
    const resolveAlert = async (alertId) => {
        loading.value = true
        error.value = null
        
        try {
            const response = await axios.patch(`${API_BASE_URL}/alerts/${alertId}/resolve`)
            
            if (response.data.success) {
                // Remove the alert from the list
                alerts.value = alerts.value.filter(alert => alert.alert_id !== alertId)
            }
        } catch (err) {
            error.value = err.response?.data?.message || 'Failed to resolve alert'
            console.error('Error resolving alert:', err)
            throw err
        } finally {
            loading.value = false
        }
    }

    // Delete an alert
    const deleteAlert = async (alertId) => {
        loading.value = true
        error.value = null
        
        try {
            const response = await axios.delete(`${API_BASE_URL}/alerts/${alertId}`)
            
            if (response.data.success) {
                // Remove the alert from the list
                alerts.value = alerts.value.filter(alert => alert.alert_id !== alertId)
            }
        } catch (err) {
            error.value = err.response?.data?.message || 'Failed to delete alert'
            console.error('Error deleting alert:', err)
            throw err
        } finally {
            loading.value = false
        }
    }

    // Fetch forecast warnings
    const fetchForecastWarnings = async () => {
        loading.value = true
        error.value = null
        
        try {
            const response = await axios.get(`${API_BASE_URL}/alerts/forecast-warnings`)
            forecastWarnings.value = response.data.warnings || []
        } catch (err) {
            error.value = err.response?.data?.message || 'Failed to fetch forecast warnings'
            console.error('Error fetching forecast warnings:', err)
        } finally {
            loading.value = false
        }
    }

    // Get alert type display information
    const getAlertTypeInfo = (alertType) => {
        const typeMap = {
            'weather': {
                icon: 'fas fa-cloud-rain',
                color: 'warning',
                label: 'Weather Alert'
            },
            'pest': {
                icon: 'fas fa-bug',
                color: 'danger',
                label: 'Pest Alert'
            },
            'irrigation': {
                icon: 'fas fa-tint',
                color: 'info',
                label: 'Irrigation Alert'
            },
            'harvest': {
                icon: 'fas fa-cut',
                color: 'success',
                label: 'Harvest Alert'
            },
            'maintenance': {
                icon: 'fas fa-wrench',
                color: 'secondary',
                label: 'Maintenance Alert'
            }
        }

        return typeMap[alertType] || {
            icon: 'fas fa-exclamation-triangle',
            color: 'warning',
            label: 'Alert'
        }
    }

    // Format alert time for display
    const formatAlertTime = (issuedAt) => {
        const now = new Date()
        const alertTime = new Date(issuedAt)
        const diffInHours = Math.floor((now - alertTime) / (1000 * 60 * 60))
        
        if (diffInHours < 1) {
            return 'Just now'
        } else if (diffInHours < 24) {
            return `${diffInHours} hour${diffInHours > 1 ? 's' : ''} ago`
        } else {
            const diffInDays = Math.floor(diffInHours / 24)
            return `${diffInDays} day${diffInDays > 1 ? 's' : ''} ago`
        }
    }

    return {
        // State
        alerts,
        forecastWarnings,
        loading,
        error,
        
        // Methods
        fetchAlerts,
        fetchActiveAlerts,
        createAlert,
        resolveAlert,
        deleteAlert,
        fetchForecastWarnings,
        getAlertTypeInfo,
        formatAlertTime
    }
}
