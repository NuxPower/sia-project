<template>
  <div v-if="show" class="modal-overlay" @click="closeModal">
    <div class="modal-content" @click.stop>
      <div class="modal-header">
        <h3>
          <i class="fas fa-bell"></i>
          Create New Alert
        </h3>
        <button class="close-button" @click="closeModal">
          <i class="fas fa-times"></i>
        </button>
      </div>

      <form @submit.prevent="submitAlert" class="modal-body">
        <!-- Farm Selection -->
        <div class="form-group">
          <label for="farm_id">
            <i class="fas fa-map-marker-alt"></i>
            Farm
          </label>
          <select 
            id="farm_id" 
            v-model="form.farm_id" 
            required
            class="form-control"
          >
            <option value="">Select a farm</option>
            <option 
              v-for="farm in farms" 
              :key="farm.farm_id" 
              :value="farm.farm_id"
            >
              {{ farm.farm_name }}
            </option>
          </select>
        </div>

        <!-- Alert Type -->
        <div class="form-group">
          <label for="alert_type">
            <i class="fas fa-exclamation-triangle"></i>
            Alert Type
          </label>
          <select 
            id="alert_type" 
            v-model="form.alert_type" 
            required
            class="form-control"
          >
            <option value="">Select alert type</option>
            <option value="weather">Weather Alert</option>
            <option value="irrigation">Irrigation Alert</option>
            <option value="harvest">Harvest Alert</option>
            <option value="maintenance">Maintenance Alert</option>
          </select>
        </div>

        <!-- Alert Message -->
        <div class="form-group">
          <label for="message">
            <i class="fas fa-comment"></i>
            Alert Message
          </label>
          <textarea 
            id="message" 
            v-model="form.message" 
            required
            placeholder="Describe the alert in detail..."
            class="form-control"
            rows="4"
            maxlength="500"
          ></textarea>
          <div class="character-count">{{ form.message.length }}/500</div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="loading-state">
          <i class="fas fa-spinner fa-spin"></i>
          Creating alert...
        </div>

        <!-- Error State -->
        <div v-if="error" class="error-state">
          <i class="fas fa-exclamation-triangle"></i>
          {{ error }}
        </div>

        <!-- Form Actions -->
        <div class="modal-actions">
          <button type="button" @click="closeModal" class="cancel-button">
            Cancel
          </button>
          <button type="submit" :disabled="loading || !isFormValid" class="submit-button">
            <i class="fas fa-plus"></i>
            Create Alert
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  farms: {
    type: Array,
    default: () => []
  },
  createAlert: {
    type: Function,
    required: true
  }
})

const emit = defineEmits(['close', 'alert-created'])

const form = ref({
  farm_id: '',
  alert_type: '',
  message: ''
})

const loading = ref(false)
const error = ref(null)

const isFormValid = computed(() => {
  return form.value.farm_id && form.value.alert_type && form.value.message.trim()
})

// Reset form when modal opens
watch(() => props.show, (newValue) => {
  if (newValue) {
    resetForm()
    error.value = null
  }
})

const resetForm = () => {
  form.value = {
    farm_id: '',
    alert_type: '',
    message: ''
  }
}

const closeModal = () => {
  emit('close')
}

const submitAlert = async () => {
  if (!isFormValid.value || loading.value) return

  loading.value = true
  error.value = null

  try {
    const newAlert = await props.createAlert({
      farm_id: form.value.farm_id,
      alert_type: form.value.alert_type,
      message: form.value.message.trim()
    })

    if (newAlert) {
      emit('alert-created', newAlert)
      closeModal()
    }
  } catch (err) {
    console.error('Failed to create alert:', err)
    error.value = err?.response?.data?.message || 'Failed to create alert'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
::v-deep(#farm_id option),
::v-deep(#alert_type option) {
  background-color: #f8fafc;
  color: #0f172a;
}

::v-deep(#alert_type option[value="weather"]) {
  background-color: #dbeafe;
}
::v-deep(#alert_type option[value="irrigation"]) {
  background-color: #dcfce7;
}
::v-deep(#alert_type option[value="harvest"]) {
  background-color: #ffedd5;
}
::v-deep(#alert_type option[value="maintenance"]) {
  background-color: #ede9fe;
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.8);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 20px;
}

.modal-content {
  background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
  border-radius: 16px;
  width: 100%;
  max-width: 500px;
  max-height: 90vh;
  overflow-y: auto;
  border: 1px solid rgba(255, 255, 255, 0.1);
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 25px 25px 0;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  margin-bottom: 25px;
}

.modal-header h3 {
  color: white;
  font-size: 20px;
  display: flex;
  align-items: center;
  gap: 10px;
  margin: 0;
}

.modal-header h3 i {
  color: #f59e0b;
}

.close-button {
  background: rgba(239, 68, 68, 0.2);
  border: 1px solid rgba(239, 68, 68, 0.3);
  color: white;
  width: 36px;
  height: 36px;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.close-button:hover {
  background: rgba(239, 68, 68, 0.4);
  transform: scale(1.1);
}

.modal-body {
  padding: 0 25px 25px;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  color: white;
  font-size: 14px;
  font-weight: 600;
  margin-bottom: 8px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.form-group label i {
  color: #60a5fa;
  width: 16px;
}

.form-control {
  width: 100%;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  color: white;
  padding: 12px 16px;
  border-radius: 8px;
  font-size: 14px;
  transition: all 0.3s ease;
}

.form-control:focus {
  outline: none;
  border-color: #3b82f6;
  background: rgba(255, 255, 255, 0.08);
}

.form-control::placeholder {
  color: #9ca3af;
}

textarea.form-control {
  resize: vertical;
  min-height: 100px;
}

.character-count {
  text-align: right;
  color: #9ca3af;
  font-size: 12px;
  margin-top: 5px;
}

.loading-state, .error-state {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 16px;
  border-radius: 8px;
  margin-bottom: 20px;
  font-size: 14px;
}

.loading-state {
  background: rgba(59, 130, 246, 0.2);
  border: 1px solid rgba(59, 130, 246, 0.3);
  color: #60a5fa;
}

.error-state {
  background: rgba(239, 68, 68, 0.2);
  border: 1px solid rgba(239, 68, 68, 0.3);
  color: #fca5a5;
}

.modal-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  margin-top: 25px;
  padding-top: 20px;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.cancel-button, .submit-button {
  padding: 12px 24px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 8px;
}

.cancel-button {
  background: rgba(156, 163, 175, 0.2);
  border: 1px solid rgba(156, 163, 175, 0.3);
  color: white;
}

.cancel-button:hover {
  background: rgba(156, 163, 175, 0.4);
}

.submit-button {
  background: rgba(59, 130, 246, 0.2);
  border: 1px solid rgba(59, 130, 246, 0.4);
  color: white;
}

.submit-button:hover:not(:disabled) {
  background: rgba(59, 130, 246, 0.4);
  transform: translateY(-2px);
}

.submit-button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Responsive Design - Mobile First Approach */

/* Extra Small Devices (phones, up to 480px) */
@media (max-width: 480px) {
  .modal-overlay {
    padding: 0.5rem;
    align-items: flex-end;
  }
  
  .modal-content {
    max-height: 95vh;
    width: 100%;
    border-radius: 1rem 1rem 0 0;
    max-width: 100%;
  }
  
  .modal-header, .modal-body {
    padding-left: 1rem;
    padding-right: 1rem;
  }

  .modal-header {
    padding-top: 1rem;
    padding-bottom: 0.75rem;
  }

  .modal-header h3 {
    font-size: 1.125rem;
  }
  
  .modal-body {
    padding-top: 0.75rem;
    padding-bottom: 1rem;
  }

  .form-group label {
    font-size: 0.8125rem;
  }

  .form-control, .form-select {
    padding: 0.75rem;
    font-size: 0.8125rem;
  }
  
  .modal-actions {
    flex-direction: column;
    gap: 0.5rem;
  }
  
  .cancel-button, .submit-button {
    width: 100%;
    justify-content: center;
    padding: 0.875rem 1rem;
    font-size: 0.875rem;
  }
}

/* Small Devices (landscape phones, 481px to 640px) */
@media (min-width: 481px) and (max-width: 640px) {
  .modal-overlay {
    padding: 0.75rem;
  }
  
  .modal-content {
    max-height: 90vh;
    width: 95%;
  }

  .modal-header, .modal-body {
    padding-left: 1.25rem;
    padding-right: 1.25rem;
  }
}

/* Medium Devices (tablets, 641px to 768px) */
@media (min-width: 641px) and (max-width: 768px) {
  .modal-overlay {
    padding: 1rem;
  }
  
  .modal-content {
    max-height: 90vh;
    width: 90%;
  }
}

/* Standard Mobile (up to 768px) */
@media (max-width: 768px) {
  .modal-overlay {
    padding: 0.625rem;
  }
  
  .modal-content {
    max-height: 95vh;
  }
  
  .modal-header, .modal-body {
    padding-left: 1.25rem;
    padding-right: 1.25rem;
  }
  
  .modal-actions {
    flex-direction: column;
  }
  
  .cancel-button, .submit-button {
    width: 100%;
    justify-content: center;
  }
}

/* Large Devices (desktops, 1024px and up) */
@media (min-width: 1024px) {
  .modal-content {
    max-width: 600px;
  }
}

/* Extra Large Devices (large desktops, 1440px and up) */
@media (min-width: 1440px) {
  .modal-content {
    max-width: 700px;
  }
}

/* Zoom Support - Ensure proper scaling */
@media (min-resolution: 192dpi) {
  .modal-content {
    border-width: 1px;
  }
}
</style>
