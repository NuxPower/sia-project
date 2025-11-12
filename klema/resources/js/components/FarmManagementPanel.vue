<template>
  <div :class="['farm-panel', panelClass]">
    <div class="farm-panel__header">
      <h3>Farm Management</h3>
      <button class="farm-panel__refresh" @click="$emit('refresh')" :disabled="loading">
        <i class="fas fa-sync-alt" :class="{ spinning: loading }"></i>
      </button>
    </div>

    <section class="farm-panel__section">
      <label class="farm-panel__label">Select Farm</label>
      <select v-model.number="selectedFarmId" class="farm-panel__select">
        <option v-for="farm in farms" :key="farm.farm_id" :value="farm.farm_id">
          {{ farm.farm_name }}
        </option>
      </select>
      <button class="farm-panel__link" @click="showCreate = !showCreate">
        {{ showCreate ? 'Close new farm form' : 'Create new farm' }}
      </button>
    </section>

    <section v-if="showCreate" class="farm-panel__section farm-panel__section--card">
      <h4>Create Farm</h4>
      <div class="farm-panel__grid">
        <div>
          <label class="farm-panel__label">Name</label>
          <input v-model="createForm.farm_name" type="text" class="farm-panel__input" />
        </div>
        <div>
          <label class="farm-panel__label">Latitude</label>
          <input v-model.number="createForm.latitude" type="number" step="0.000001" class="farm-panel__input" />
        </div>
        <div>
          <label class="farm-panel__label">Longitude</label>
          <input v-model.number="createForm.longitude" type="number" step="0.000001" class="farm-panel__input" />
        </div>
        <div>
          <label class="farm-panel__label">Size (ha)</label>
          <input v-model.number="createForm.size_hectares" type="number" step="0.01" min="0" class="farm-panel__input" />
        </div>
        <div>
          <label class="farm-panel__label">Soil Type</label>
          <select v-model="createForm.soil_type" class="farm-panel__select">
            <option value="">Select soil type</option>
            <option v-for="soil in soilTypeOptions" :key="soil" :value="soil">
              {{ titleCase(soil) }}
            </option>
          </select>
        </div>
      </div>
      <div>
        <label class="farm-panel__label">Description</label>
        <textarea v-model="createForm.description" rows="2" class="farm-panel__textarea" />
      </div>
      <MiniMapPicker v-model="createLocation" height="220px" />
      <button class="farm-panel__button farm-panel__button--primary" @click="handleCreateFarm">
        <i class="fas fa-plus-circle"></i>
        Save Farm
      </button>
    </section>

    <template v-if="selectedFarm">
      <section class="farm-panel__section farm-panel__section--card">
        <h4>Farm Details</h4>
        <div class="farm-panel__grid">
          <div>
            <label class="farm-panel__label">Name</label>
            <input v-model="editForm.farm_name" type="text" class="farm-panel__input" />
          </div>
          <div>
            <label class="farm-panel__label">Size (ha)</label>
            <input v-model.number="editForm.size_hectares" type="number" step="0.01" min="0" class="farm-panel__input" />
          </div>
          <div>
            <label class="farm-panel__label">Soil Type</label>
            <select v-model="editForm.soil_type" class="farm-panel__select">
              <option value="">Select soil type</option>
              <option v-for="soil in soilTypeOptions" :key="soil" :value="soil">
                {{ titleCase(soil) }}
              </option>
            </select>
          </div>
        </div>
        <div class="farm-panel__grid">
          <div>
            <label class="farm-panel__label">Latitude</label>
            <input v-model.number="editForm.latitude" type="number" step="0.000001" class="farm-panel__input" />
          </div>
          <div>
            <label class="farm-panel__label">Longitude</label>
            <input v-model.number="editForm.longitude" type="number" step="0.000001" class="farm-panel__input" />
          </div>
        </div>
        <div>
          <label class="farm-panel__label">Description</label>
          <textarea v-model="editForm.description" rows="2" class="farm-panel__textarea" />
        </div>
        <MiniMapPicker v-model="editLocation" height="220px" />
        <div class="farm-panel__actions">
          <button class="farm-panel__button farm-panel__button--primary" @click="handleSaveFarm">
            <i class="fas fa-save"></i>
            Save details
          </button>
          <button 
            v-if="onLocateFarm && selectedFarm && selectedFarm.latitude != null && selectedFarm.longitude != null"
            class="farm-panel__button farm-panel__button--info" 
            @click="handleLocateOnMap"
            title="Locate this farm on the weather map"
          >
            <i class="fas fa-map-marker-alt"></i>
            Locate on Map
          </button>
        </div>
      </section>

      <section class="farm-panel__section farm-panel__section--card">
        <h4>Boundary</h4>
        <p class="farm-panel__hint" v-if="isDrawing">
          Drawing active: click on the map to add vertices, then finish or cancel.
        </p>
        <div class="farm-panel__actions farm-panel__actions--wrap">
          <button
            class="farm-panel__button"
            @click="handleStartBoundary"
            :disabled="isDrawing"
          >
            <i class="fas fa-draw-polygon"></i>
            Draw boundary
          </button>
          <button
            class="farm-panel__button farm-panel__button--success"
            @click="$emit('finish-boundary')"
            :disabled="!isDrawing"
          >
            <i class="fas fa-check"></i>
            Finish
          </button>
          <button
            class="farm-panel__button farm-panel__button--danger"
            @click="$emit('cancel-boundary')"
            :disabled="!isDrawing"
          >
            <i class="fas fa-times"></i>
            Cancel
          </button>
          <button
            class="farm-panel__button farm-panel__button--warning"
            @click="$emit('clear-boundary', selectedFarmId)"
          >
            <i class="fas fa-eraser"></i>
            Clear boundary
          </button>
        </div>
      </section>

      <section class="farm-panel__section farm-panel__section--card">
        <h4>Points of Interest</h4>
        <div class="farm-panel__grid">
          <div>
            <label class="farm-panel__label">Label</label>
            <input v-model="pointForm.label" type="text" class="farm-panel__input" />
          </div>
          <div>
            <label class="farm-panel__label">Type</label>
            <select v-model="pointForm.point_type" class="farm-panel__select">
              <option value="">General</option>
              <option value="water_source">Water Source</option>
              <option value="storage">Storage</option>
              <option value="equipment">Equipment</option>
              <option value="crop_field">Crop Field</option>
              <option value="shelter">Shelter</option>
            </select>
          </div>
        </div>
        <p class="farm-panel__hint" v-if="isPlacingPoint">
          Click on the map to place the point, or cancel.
        </p>
        <div class="farm-panel__actions farm-panel__actions--wrap">
          <button
            class="farm-panel__button"
            @click="handleStartPoint"
            :disabled="isPlacingPoint || !pointForm.label"
          >
            <i class="fas fa-map-marker-alt"></i>
            Place on map
          </button>
          <button
            class="farm-panel__button farm-panel__button--danger"
            @click="handleCancelPoint"
            :disabled="!isPlacingPoint"
          >
            <i class="fas fa-ban"></i>
            Cancel placement
          </button>
        </div>
      </section>
    </template>
  </div>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue'
import MiniMapPicker from './MiniMapPicker.vue'

const props = defineProps({
  farms: {
    type: Array,
    default: () => []
  },
  loading: Boolean,
  isDrawing: Boolean,
  isPlacingPoint: Boolean,
  soilTypes: {
    type: Array,
    default: () => []
  },
  variant: {
    type: String,
    default: 'overlay',
    validator: value => ['overlay', 'dashboard'].includes(value)
  },
  onLocateFarm: {
    type: Function,
    default: null
  }
})

const emit = defineEmits([
  'refresh',
  'create-farm',
  'save-farm',
  'clear-boundary',
  'start-boundary',
  'finish-boundary',
  'cancel-boundary',
  'start-point',
  'cancel-point'
])

const selectedFarmId = ref(null)
const showCreate = ref(false)
const editForm = reactive({
  farm_name: '',
  size_hectares: null,
  soil_type: '',
  description: '',
  latitude: null,
  longitude: null
})
const createForm = reactive({
  farm_name: '',
  size_hectares: null,
  soil_type: '',
  description: '',
  latitude: null,
  longitude: null
})
const pointForm = reactive({
  label: '',
  point_type: ''
})
const createLocation = ref({ lat: null, lng: null })
const editLocation = ref({ lat: null, lng: null })

const panelClass = computed(() => props.variant === 'dashboard' ? 'farm-panel--dashboard' : 'farm-panel--overlay')
const soilTypeOptions = computed(() => props.soilTypes ?? [])

const toNumberOrNull = (value) => {
  const num = typeof value === 'string' ? parseFloat(value) : value
  return Number.isFinite(num) ? num : null
}

const normalizeSoilType = (value) => {
  if (!value) {
    return null
  }

  const lower = value.toString().toLowerCase()

  return soilTypeOptions.value.includes(lower) ? lower : null
}

const titleCase = (value) => {
  if (!value) return ''
  return value
    .toString()
    .split(/\s|_/)
    .map(segment => segment.charAt(0).toUpperCase() + segment.slice(1))
    .join(' ')
}

const selectedFarm = computed(() =>
  props.farms.find(farm => farm.farm_id === selectedFarmId.value) ?? null
)

watch(
  () => props.farms,
  (farms) => {
    if (!farms.length) {
      selectedFarmId.value = null
      resetEditForm()
      return
    }

    if (!selectedFarmId.value || !farms.some(f => f.farm_id === selectedFarmId.value)) {
      selectedFarmId.value = farms[0].farm_id
    }

    syncEditForm()
  },
  { immediate: true }
)

watch(selectedFarmId, syncEditForm)

watch(() => props.isPlacingPoint, (placing) => {
  if (!placing) {
    pointForm.label = ''
    pointForm.point_type = ''
  }
})

function syncEditForm () {
  const farm = selectedFarm.value
  if (!farm) {
    resetEditForm()
    return
  }

  editForm.farm_name = farm.farm_name ?? ''
  editForm.size_hectares = farm.size_hectares ?? null
  editForm.soil_type = farm.soil_type ?? ''
  editForm.description = farm.description ?? ''
  editForm.latitude = farm.latitude ?? null
  editForm.longitude = farm.longitude ?? null
  editLocation.value = {
    lat: toNumberOrNull(farm.latitude),
    lng: toNumberOrNull(farm.longitude)
  }
}

function resetEditForm () {
  editForm.farm_name = ''
  editForm.size_hectares = null
  editForm.soil_type = ''
  editForm.description = ''
  editForm.latitude = null
  editForm.longitude = null
  editLocation.value = { lat: null, lng: null }
}

function handleCreateFarm () {
  if (!createForm.farm_name || createForm.latitude === null || createForm.longitude === null) {
    alert('Farm name and coordinates are required.');
    return;
  }

  emit('create-farm', {
    farm_name: createForm.farm_name,
    latitude: createForm.latitude,
    longitude: createForm.longitude,
    size_hectares: createForm.size_hectares,
    soil_type: normalizeSoilType(createForm.soil_type),
    description: createForm.description
  });

  Object.assign(createForm, {
    farm_name: '',
    size_hectares: null,
    soil_type: '',
    description: '',
    latitude: null,
    longitude: null
  });
  createLocation.value = { lat: null, lng: null }

  showCreate.value = false;
}

function handleSaveFarm () {
  if (!selectedFarm.value) return

  emit('save-farm', selectedFarmId.value, {
    farm_name: editForm.farm_name,
    size_hectares: editForm.size_hectares,
    soil_type: normalizeSoilType(editForm.soil_type),
    description: editForm.description || null,
    latitude: editForm.latitude,
    longitude: editForm.longitude
  })
}

function handleStartBoundary () {
  if (!selectedFarm.value) return
  emit('start-boundary', selectedFarmId.value)
}

function handleStartPoint () {
  if (!selectedFarm.value || !pointForm.label) return

  emit('start-point', selectedFarmId.value, {
    label: pointForm.label,
    point_type: pointForm.point_type || null
  })
}

function handleCancelPoint () {
  emit('cancel-point')
  pointForm.label = ''
  pointForm.point_type = ''
}

function handleLocateOnMap () {
  if (props.onLocateFarm && selectedFarm.value) {
    props.onLocateFarm(selectedFarm.value.farm_id)
  }
}

watch(createLocation, (value) => {
  const lat = toNumberOrNull(value?.lat)
  const lng = toNumberOrNull(value?.lng)

  if (lat !== createForm.latitude) {
    createForm.latitude = lat
  }

  if (lng !== createForm.longitude) {
    createForm.longitude = lng
  }
}, { deep: true })

watch(() => [createForm.latitude, createForm.longitude], ([lat, lng]) => {
  const latNum = toNumberOrNull(lat)
  const lngNum = toNumberOrNull(lng)
  const current = createLocation.value

  if (latNum === null || lngNum === null) {
    if (current.lat !== null || current.lng !== null) {
      createLocation.value = { lat: null, lng: null }
    }
    return
  }

  if (current.lat !== latNum || current.lng !== lngNum) {
    createLocation.value = { lat: latNum, lng: lngNum }
  }
})

watch(editLocation, (value) => {
  const lat = toNumberOrNull(value?.lat)
  const lng = toNumberOrNull(value?.lng)

  if (lat !== editForm.latitude) {
    editForm.latitude = lat
  }

  if (lng !== editForm.longitude) {
    editForm.longitude = lng
  }
}, { deep: true })

watch(() => [editForm.latitude, editForm.longitude], ([lat, lng]) => {
  const latNum = toNumberOrNull(lat)
  const lngNum = toNumberOrNull(lng)
  const current = editLocation.value

  if (latNum === null || lngNum === null) {
    if (current.lat !== null || current.lng !== null) {
      editLocation.value = { lat: null, lng: null }
    }
    return
  }

  if (current.lat !== latNum || current.lng !== lngNum) {
    editLocation.value = { lat: latNum, lng: lngNum }
  }
})
</script>

<style scoped>
.farm-panel {
  border-radius: 16px;
  color: #e2e8f0;
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.farm-panel--overlay {
  width: 320px;
  max-height: calc(100vh - 40px);
  overflow-y: auto;
  padding: 16px 18px;
  background: rgba(15, 23, 42, 0.85);
  border: 1px solid rgba(255, 255, 255, 0.08);
  backdrop-filter: blur(16px);
  box-shadow: 0 20px 45px rgba(15, 23, 42, 0.45);
}

.farm-panel--dashboard {
  width: 100%;
  padding: 24px;
  background: rgba(15, 23, 42, 0.55);
  border: 1px solid rgba(59, 130, 246, 0.2);
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.04);
  backdrop-filter: blur(10px);
}

.farm-panel--dashboard .farm-panel__grid {
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
}

.farm-panel--dashboard .farm-panel__section--card {
  background: rgba(15, 23, 42, 0.68);
}

.farm-panel h3, .farm-panel h4 {
  margin: 0;
}

.farm-panel h4 {
  font-size: 15px;
  font-weight: 600;
  color: #bfdbfe;
}

.farm-panel__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 12px;
}

.farm-panel__refresh {
  background: transparent;
  border: none;
  color: #bfdbfe;
  font-size: 16px;
  cursor: pointer;
}

.farm-panel__refresh .spinning {
  animation: spin 1s linear infinite;
}

.farm-panel__section {
  margin-bottom: 14px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.farm-panel__section--card {
  padding: 12px;
  border: 1px solid rgba(59, 130, 246, 0.25);
  border-radius: 12px;
  background: rgba(15, 23, 42, 0.65);
}

.farm-panel__label {
  display: block;
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: #94a3b8;
  margin-bottom: 4px;
}

.farm-panel__select,
.farm-panel__input,
.farm-panel__textarea {
  width: 100%;
  padding: 8px 10px;
  border-radius: 8px;
  border: 1px solid rgba(148, 163, 184, 0.4);
  background: rgba(15, 23, 42, 0.8);
  color: #e2e8f0;
  font-size: 14px;
}

.farm-panel__textarea {
  resize: vertical;
}

.farm-panel__link {
  background: none;
  border: none;
  color: #60a5fa;
  font-size: 12px;
  text-align: left;
  padding: 0;
  cursor: pointer;
}

.farm-panel__grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 10px;
}

.farm-panel__actions {
  display: flex;
  gap: 8px;
  margin-top: 8px;
}

.farm-panel__actions--wrap {
  flex-wrap: wrap;
}

.farm-panel__button {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 12px;
  border-radius: 8px;
  border: 1px solid rgba(59, 130, 246, 0.3);
  background: rgba(59, 130, 246, 0.12);
  color: #bfdbfe;
  font-size: 13px;
  cursor: pointer;
  transition: background 0.2s ease, border-color 0.2s ease;
}

.farm-panel__button:hover {
  background: rgba(59, 130, 246, 0.2);
}

.farm-panel__button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.farm-panel__button--primary {
  background: rgba(37, 99, 235, 0.5);
  border-color: rgba(37, 99, 235, 0.6);
  color: #eff6ff;
}

.farm-panel__button--success {
  background: rgba(22, 163, 74, 0.4);
  border-color: rgba(22, 163, 74, 0.5);
  color: #dcfce7;
}

.farm-panel__button--danger {
  background: rgba(220, 38, 38, 0.4);
  border-color: rgba(220, 38, 38, 0.5);
  color: #fee2e2;
}

.farm-panel__button--warning {
  background: rgba(217, 119, 6, 0.4);
  border-color: rgba(217, 119, 6, 0.5);
  color: #ffedd5;
}

.farm-panel__button--info {
  background: rgba(59, 130, 246, 0.4);
  border-color: rgba(59, 130, 246, 0.5);
  color: #dbeafe;
}

.farm-panel__hint {
  margin: 0;
  font-size: 12px;
  color: #cbd5f5;
}

.farm-panel__section--card .mini-map-picker {
  margin-top: 8px;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
</style>

