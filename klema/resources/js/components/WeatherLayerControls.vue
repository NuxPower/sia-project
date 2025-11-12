<template>
  <div class="weather-layer-controls">
    <div class="controls-header" @click="isExpanded = !isExpanded">
      <span class="controls-title">🌤️ Weather Layers</span>
      <button 
        class="toggle-button"
        :class="{ 'expanded': isExpanded }"
      >
        <i :class="isExpanded ? 'fas fa-chevron-up' : 'fas fa-chevron-down'"></i>
      </button>
    </div>
    
    <transition name="slide">
      <div v-if="isExpanded" class="controls-body">
        <div class="layer-section">
          <div class="section-title">Base Maps</div>
          <div 
            v-for="base in baseLayers"
            :key="base.id"
            class="base-option"
            :class="{ active: selectedBaseLayer === base.id }"
            @click="selectBaseLayer(base.id)"
          >
            <div class="layer-info">
              <span class="layer-icon">{{ base.icon }}</span>
              <div class="layer-text">
                <span class="layer-name">{{ base.name }}</span>
                <span v-if="base.description" class="layer-description">{{ base.description }}</span>
              </div>
            </div>
            <div class="base-indicator"></div>
          </div>
        </div>

        <div class="layer-section">
          <div class="section-title">Weather Overlays</div>
          <div 
            v-for="layer in layers"
            :key="layer.id"
            class="layer-toggle"
            @click="toggleLayer(layer.id)"
          >
            <div class="layer-info">
              <span class="layer-icon">{{ layer.icon }}</span>
              <span class="layer-name">{{ layer.name }}</span>
            </div>
            <label class="switch" @click.stop>
              <input 
                type="checkbox" 
                :checked="layer.active"
                @change="toggleLayer(layer.id)"
              >
              <span class="slider"></span>
            </label>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref } from 'vue';

const emit = defineEmits(['toggle-layer', 'change-base-layer']);

const isExpanded = ref(true);

const baseLayers = ref([
  { id: 'street', name: 'Street Map', icon: '🗺️', description: 'OpenStreetMap Standard' },
  { id: 'satellite', name: 'Satellite (ArcGIS)', icon: '🛰️', description: 'Esri World Imagery' },
  { id: 'nasa', name: 'NASA True Color', icon: '🌍', description: 'Daily VIIRS composite' }
]);

const layers = ref([
  { id: 'clouds', name: 'Clouds', icon: '☁️', active: false },
  { id: 'precipitation', name: 'Precipitation', icon: '🌧️', active: false },
  { id: 'temperature', name: 'Temperature', icon: '🌡️', active: false },
  { id: 'wind', name: 'Wind', icon: '💨', active: false },
  { id: 'pressure', name: 'Pressure', icon: '🌪️', active: false }
]);

const selectedBaseLayer = ref(baseLayers.value[0]?.id ?? null);

const selectBaseLayer = (layerId) => {
  selectedBaseLayer.value = layerId;
  emit('change-base-layer', { layerId });
};

const toggleLayer = (layerId) => {
  const layer = layers.value.find(l => l.id === layerId);
  if (layer) {
    layer.active = !layer.active;
    emit('toggle-layer', { layerId, active: layer.active });
  }
};
</script>

<style scoped>
.weather-layer-controls {
  position: fixed;
  top: 150px;
  right: 20px;
  background: rgba(0, 0, 0, 0.85);
  border-radius: 12px;
  border: 1px solid rgba(59, 130, 246, 0.3);
  backdrop-filter: blur(15px);
  z-index: 1000;
  min-width: 220px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4);
}

.controls-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  cursor: pointer;
  user-select: none;
}

.controls-title {
  font-size: 14px;
  font-weight: 600;
  color: white;
}

.toggle-button {
  background: none;
  border: none;
  color: #9ca3af;
  cursor: pointer;
  padding: 4px;
  transition: color 0.2s;
}

.toggle-button:hover {
  color: white;
}

.toggle-button.expanded {
  color: #3b82f6;
}

.controls-body {
  padding: 0 12px 12px 12px;
  border-top: 1px solid rgba(59, 130, 246, 0.2);
  max-height: 320px;
  overflow-y: auto;
  scrollbar-width: thin;
  scrollbar-color: rgba(59, 130, 246, 0.4) transparent;
}

.controls-body::-webkit-scrollbar {
  width: 6px;
}

.controls-body::-webkit-scrollbar-track {
  background: transparent;
}

.controls-body::-webkit-scrollbar-thumb {
  background-color: rgba(59, 130, 246, 0.4);
  border-radius: 9999px;
}

.layer-section {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-top: 12px;
}

.layer-section:first-of-type {
  margin-top: 8px;
}

.section-title {
  font-size: 11px;
  font-weight: 600;
  color: rgba(191, 219, 254, 0.9);
  text-transform: uppercase;
  letter-spacing: 0.08em;
}

.layer-toggle {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 8px;
  cursor: pointer;
  border-radius: 8px;
  transition: background 0.2s;
}

.layer-toggle:hover {
  background: rgba(59, 130, 246, 0.1);
}

.layer-info {
  display: flex;
  align-items: center;
  gap: 10px;
  min-width: 0;
}

.layer-icon {
  font-size: 18px;
}

.layer-name {
  font-size: 13px;
  color: white;
  font-weight: 500;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.layer-text {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
}

.layer-description {
  font-size: 11px;
  color: rgba(226, 232, 240, 0.65);
}

.base-option {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 8px;
  border-radius: 8px;
  cursor: pointer;
  transition: background 0.2s, border 0.2s;
  border: 1px solid transparent;
}

.base-option:hover {
  background: rgba(59, 130, 246, 0.1);
}

.base-option.active {
  background: rgba(59, 130, 246, 0.14);
  border-color: rgba(59, 130, 246, 0.5);
}

.base-indicator {
  width: 18px;
  height: 18px;
  border: 2px solid rgba(148, 163, 184, 0.6);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
  position: relative;
}

.base-indicator::after {
  content: '';
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background-color: #3b82f6;
  opacity: 0;
  transition: opacity 0.2s ease;
}

.base-option.active .base-indicator {
  border-color: #3b82f6;
}

.base-option.active .base-indicator::after {
  opacity: 1;
}

.switch {
  position: relative;
  display: inline-block;
  width: 44px;
  height: 24px;
}

.switch input {
  opacity: 0;
  width: 0;
  height: 0;
}

.slider {
  position: absolute;
  cursor: pointer;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(156, 163, 175, 0.3);
  transition: 0.3s;
  border-radius: 24px;
  border: 1px solid rgba(156, 163, 175, 0.4);
}

.slider:before {
  position: absolute;
  content: "";
  height: 16px;
  width: 16px;
  left: 3px;
  bottom: 3px;
  background-color: white;
  transition: 0.3s;
  border-radius: 50%;
}

input:checked + .slider {
  background-color: rgba(59, 130, 246, 0.6);
  border-color: rgba(59, 130, 246, 0.8);
}

input:checked + .slider:before {
  transform: translateX(20px);
  background-color: #3b82f6;
}

.slide-enter-active,
.slide-leave-active {
  transition: all 0.3s ease;
  max-height: 300px;
  overflow: hidden;
}

.slide-enter-from,
.slide-leave-to {
  max-height: 0;
  opacity: 0;
}
</style>