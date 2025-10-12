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
    </transition>
  </div>
</template>

<script setup>
import { ref } from 'vue';

const emit = defineEmits(['toggle-layer']);

const isExpanded = ref(true);

const layers = ref([
  { id: 'clouds', name: 'Clouds', icon: '☁️', active: false },
  { id: 'precipitation', name: 'Precipitation', icon: '🌧️', active: false },
  { id: 'temperature', name: 'Temperature', icon: '🌡️', active: false },
  { id: 'wind', name: 'Wind', icon: '💨', active: false },
  { id: 'pressure', name: 'Pressure', icon: '🌪️', active: false }
]);

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
}

.layer-icon {
  font-size: 18px;
}

.layer-name {
  font-size: 13px;
  color: white;
  font-weight: 500;
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