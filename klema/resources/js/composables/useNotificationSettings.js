import { ref, reactive } from 'vue'
import axios from 'axios'

const API_BASE_URL = '/api'

export const DEFAULT_NOTIFICATION_SETTINGS = Object.freeze({
    heavyRainAlerts: true,
    strongWindWarnings: true,
    temperatureExtremes: false,
    stormAlerts: true,
    irrigationAlerts: false,
    harvestAlerts: true,
    maintenanceAlerts: false
})

export const SUPPORTED_NOTIFICATION_SETTING_KEYS = Object.freeze(
    Object.keys(DEFAULT_NOTIFICATION_SETTINGS)
)

export function useNotificationSettings() {
    const settings = reactive({ ...DEFAULT_NOTIFICATION_SETTINGS })
    
    const loading = ref(false)
    const error = ref(null)

    // Load notification settings from API
    const loadSettings = async () => {
        loading.value = true
        error.value = null
        
        try {
            // For now, we'll use localStorage since we don't have a backend endpoint yet
            const savedSettings = localStorage.getItem('notificationSettings')
            if (savedSettings) {
                const parsedSettings = JSON.parse(savedSettings)

                SUPPORTED_NOTIFICATION_SETTING_KEYS.forEach((key) => {
                    if (Object.prototype.hasOwnProperty.call(parsedSettings, key)) {
                        settings[key] = !!parsedSettings[key]
                    } else {
                        settings[key] = DEFAULT_NOTIFICATION_SETTINGS[key]
                    }
                })
            }
            
            // In the future, this would be an API call:
            // const response = await axios.get(`${API_BASE_URL}/notification-settings`)
            // Object.assign(settings, response.data.settings)
        } catch (err) {
            error.value = err.response?.data?.message || 'Failed to load notification settings'
            console.error('Error loading notification settings:', err)
        } finally {
            loading.value = false
        }
    }

    // Save notification settings
    const saveSettings = async () => {
        loading.value = true
        error.value = null
        
        try {
            // Save to localStorage for now
            const payload = SUPPORTED_NOTIFICATION_SETTING_KEYS.reduce((acc, key) => {
                acc[key] = !!settings[key]
                return acc
            }, {})

            localStorage.setItem('notificationSettings', JSON.stringify(payload))
            
            // In the future, this would be an API call:
            // await axios.put(`${API_BASE_URL}/notification-settings`, { settings })
            
            return true
        } catch (err) {
            error.value = err.response?.data?.message || 'Failed to save notification settings'
            console.error('Error saving notification settings:', err)
            return false
        } finally {
            loading.value = false
        }
    }

    // Update a specific setting
    const updateSetting = async (settingName, value) => {
        if (!SUPPORTED_NOTIFICATION_SETTING_KEYS.includes(settingName)) {
            return
        }

        settings[settingName] = !!value
        await saveSettings()
    }

    // Get setting display information
    const getSettingInfo = (settingName) => {
        const settingMap = {
            heavyRainAlerts: {
                icon: 'fas fa-cloud-rain',
                label: 'Heavy Rain Alerts',
                description: 'Get notified when heavy rainfall is expected'
            },
            strongWindWarnings: {
                icon: 'fas fa-wind',
                label: 'Strong Wind Warnings',
                description: 'Receive alerts for high wind speeds'
            },
            temperatureExtremes: {
                icon: 'fas fa-temperature-high',
                label: 'Temperature Extremes',
                description: 'Alerts for unusually hot or cold temperatures'
            },
            stormAlerts: {
                icon: 'fas fa-bolt',
                label: 'Storm Alerts',
                description: 'Notifications for storms and severe weather'
            },
            irrigationAlerts: {
                icon: 'fas fa-tint',
                label: 'Irrigation Alerts',
                description: 'Get nudged when prolonged dry spells call for irrigation'
            },
            harvestAlerts: {
                icon: 'fas fa-cut',
                label: 'Harvest Alerts',
                description: 'Spot favorable harvest windows based on weather outlook'
            },
            maintenanceAlerts: {
                icon: 'fas fa-wrench',
                label: 'Maintenance Alerts',
                description: 'Prepare equipment ahead of severe weather events'
            }
        }

        return settingMap[settingName] || {
            icon: 'fas fa-bell',
            label: 'Notification',
            description: 'General notification setting'
        }
    }

    return {
        // State
        settings,
        loading,
        error,
        
        // Methods
        loadSettings,
        saveSettings,
        updateSetting,
        getSettingInfo
    }
}
