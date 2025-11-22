<template>
  <div class="search-bar">
    <button 
      class="toggle-map-view-button"
      @click="toggleMapOnly"
      :title="isMapOnly ? 'Show all components' : 'Hide components (map only)'"
    >
      <i :class="isMapOnly ? 'fas fa-eye' : 'fas fa-eye-slash'"></i>
    </button>
    <div class="search-container">
      <i class="fas fa-search search-icon"></i>
      <input
        v-model="localValue"
        type="text"
        class="search-input"
        placeholder="Search location or click map to pin..."
        @keyup.enter="$emit('search')"
        :disabled="isLoading"
      />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  modelValue: String,
  isLoading: Boolean,
  mapOnly: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['update:modelValue', 'search', 'update:mapOnly']);

const isMapOnly = computed({
  get: () => props.mapOnly,
  set: (value) => emit('update:mapOnly', value)
});

const localValue = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
});

const toggleMapOnly = () => {
  isMapOnly.value = !isMapOnly.value;
};
</script>

<style scoped>
.search-bar {
  position: fixed;
  top: 20px;
  right: 20px;
  left: auto;
  z-index: 1000;
  max-width: 420px;
  display: flex;
  align-items: center;
  gap: 10px;
}

.toggle-map-view-button {
  background: rgba(0, 0, 0, 0.7);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 12px;
  padding: 12px 14px;
  color: white;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
  min-width: 44px;
  height: 44px;
  backdrop-filter: blur(10px);
}

.toggle-map-view-button:hover {
  background: rgba(0, 0, 0, 0.85);
  border-color: rgba(59, 130, 246, 0.5);
  transform: translateY(-1px);
}

.toggle-map-view-button:active {
  transform: translateY(0);
}

.toggle-map-view-button i {
  font-size: 16px;
}

.search-container {
  display: flex;
  align-items: center;
  background: rgba(0, 0, 0, 0.7);
  border-radius: 12px;
  padding: 12px 16px;
  color: white;
  min-width: 300px;
  width: 100%;
  backdrop-filter: blur(10px);
}

.search-icon {
  margin-right: 10px;
  color: #ccc;
}

.search-input {
  background: none;
  border: none;
  color: white;
  outline: none;
  flex: 1;
  font-size: 14px;
}

.search-input::placeholder {
  color: #999;
}

.search-input:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

@media (max-width: 640px) {
  .search-bar {
    top: 16px;
    left: 16px;
    right: 16px;
    max-width: none;
    flex-wrap: wrap;
  }

  .toggle-map-view-button {
    min-width: 40px;
    height: 40px;
    padding: 10px 12px;
  }

  .search-container {
    padding: 12px 14px;
    min-width: 0;
    flex: 1;
  }
}
</style>