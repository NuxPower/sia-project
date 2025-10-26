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
            <option value="pest">Pest Alert</option>
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
import { useFarms } from '../composables/useFarms'
import { useAlerts } from '../composables/useAlerts'

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['close', 'alert-created'])

const { farms, fetchFarms } = useFarms()
const { createAlert, loading, error } = useAlerts()

const form = ref({
  farm_id: '',
  alert_type: '',
  message: ''
})

const isFormValid = computed(() => {
  return form.value.farm_id && form.value.alert_type && form.value.message.trim()
})

// Load farms when modal opens
watch(() => props.show, async (newValue) => {
  if (newValue) {
    await fetchFarms()
    resetForm()
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
  if (!isFormValid.value) return

  try {
    const newAlert = await createAlert({
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
  }
}
</script>

<style scoped>
::v-deep(#alert_type option) {
  background-color: #1e293b;
  color: white;
}

::v-deep(#alert_type option[value="weather"]) {
  background-color: #1e3a8a;
}
::v-deep(#alert_type option[value="pest"]) {
  background-color: #7f1d1d;
}
::v-deep(#alert_type option[value="irrigation"]) {
  background-color: #14532d;
}
::v-deep(#alert_type option[value="harvest"]) {
  background-color: #78350f;
}
::v-deep(#alert_type option[value="maintenance"]) {
  background-color: #4c1d95;
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

@media (max-width: 768px) {
  .modal-overlay {
    padding: 10px;
  }
  
  .modal-content {
    max-height: 95vh;
  }
  
  .modal-header, .modal-body {
    padding-left: 20px;
    padding-right: 20px;
  }
  
  .modal-actions {
    flex-direction: column;
  }
  
  .cancel-button, .submit-button {
    width: 100%;
    justify-content: center;
  }
}
</style>
