<template>
  <div class="search-bar">
    <button 
      class="toggle-map-view-button"
      @click="toggleMapOnly"
      :title="isMapOnly ? 'Show all components' : 'Hide components (map only)'"
    >
      <i :class="isMapOnly ? 'fas fa-eye' : 'fas fa-eye-slash'"></i>
    </button>
    <div class="search-wrapper">
      <div class="search-container">
        <i class="fas fa-search search-icon"></i>
        <input
          v-model="localValue"
          type="text"
          class="search-input"
          placeholder="Search location or click map to pin..."
          @keyup.enter="handleSearch"
          @input="handleInput"
          @focus="showSuggestionsDropdown = true"
          @blur="handleBlur"
          :disabled="isLoading"
          ref="searchInputRef"
        />
        <i 
          v-if="isLoadingSuggestions" 
          class="fas fa-spinner fa-spin loading-icon"
        ></i>
      </div>
      <div 
        v-if="showSuggestionsDropdown && suggestions.length > 0" 
        class="suggestions-dropdown"
      >
        <div
          v-for="(suggestion, index) in suggestions"
          :key="index"
          class="suggestion-item"
          @mousedown.prevent="selectSuggestion(suggestion)"
          :class="{ 'selected': selectedIndex === index }"
        >
          <i class="fas fa-map-marker-alt suggestion-icon"></i>
          <div class="suggestion-content">
            <div class="suggestion-name">{{ suggestion.name }}</div>
            <div class="suggestion-details">
              <span v-if="suggestion.state">{{ suggestion.state }}</span>
              <span v-if="suggestion.state && suggestion.country">, </span>
              <span v-if="suggestion.country">{{ suggestion.country }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch, onBeforeUnmount } from 'vue';

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

const suggestions = ref([]);
const isLoadingSuggestions = ref(false);
const showSuggestionsDropdown = ref(false);
const selectedIndex = ref(-1);
const searchInputRef = ref(null);
let debounceTimer = null;
let suggestionController = null;

const toggleMapOnly = () => {
  isMapOnly.value = !isMapOnly.value;
};

const fetchSuggestions = async (query) => {
  if (!query || query.trim().length < 2) {
    suggestions.value = [];
    return;
  }

  // Cancel previous request if any
  if (suggestionController) {
    suggestionController.abort();
  }

  suggestionController = new AbortController();
  isLoadingSuggestions.value = true;

  try {
    const response = await window.axios.get('/api/weather/suggestions', {
      params: { q: query, limit: 5 },
      signal: suggestionController.signal
    });

    if (response.data && Array.isArray(response.data)) {
      suggestions.value = response.data;
      selectedIndex.value = -1;
    } else {
      suggestions.value = [];
    }
  } catch (error) {
    if (error.name !== 'CanceledError') {
      console.error('Error fetching suggestions:', error);
      suggestions.value = [];
    }
  } finally {
    isLoadingSuggestions.value = false;
    suggestionController = null;
  }
};

const handleInput = () => {
  // Clear previous debounce timer
  if (debounceTimer) {
    clearTimeout(debounceTimer);
  }

  const query = localValue.value?.trim() || '';
  
  if (query.length < 2) {
    suggestions.value = [];
    showSuggestionsDropdown.value = false;
    return;
  }

  // Debounce API call
  debounceTimer = setTimeout(() => {
    showSuggestionsDropdown.value = true;
    fetchSuggestions(query);
  }, 300); // 300ms delay
};

const selectSuggestion = (suggestion) => {
  if (suggestion.fullName) {
    localValue.value = suggestion.fullName;
  } else if (suggestion.name) {
    localValue.value = suggestion.name;
  }
  
  suggestions.value = [];
  showSuggestionsDropdown.value = false;
  selectedIndex.value = -1;
  
  // Emit search event with the selected location
  emit('search');
};

const handleSearch = () => {
  if (selectedIndex.value >= 0 && suggestions.value[selectedIndex.value]) {
    selectSuggestion(suggestions.value[selectedIndex.value]);
  } else {
    emit('search');
  }
  showSuggestionsDropdown.value = false;
};

const handleBlur = (event) => {
  // Delay hiding suggestions to allow click events to fire
  setTimeout(() => {
    showSuggestionsDropdown.value = false;
    selectedIndex.value = -1;
  }, 200);
};

// Watch for external changes to modelValue
watch(() => props.modelValue, (newValue) => {
  if (!newValue || newValue.trim().length < 2) {
    suggestions.value = [];
  }
});

// Cleanup on unmount
onBeforeUnmount(() => {
  if (debounceTimer) {
    clearTimeout(debounceTimer);
  }
  if (suggestionController) {
    suggestionController.abort();
  }
});
</script>

<style scoped>
.search-bar {
  position: fixed;
  top: 20px;
  right: 20px;
  left: auto;
  z-index: 1100;
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

.search-wrapper {
  position: relative;
  flex: 1;
  min-width: 300px;
}

.search-container {
  display: flex;
  align-items: center;
  background: rgba(0, 0, 0, 0.7);
  border-radius: 12px;
  padding: 12px 16px;
  color: white;
  width: 100%;
  backdrop-filter: blur(10px);
  position: relative;
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

.loading-icon {
  margin-left: 8px;
  color: #ccc;
  font-size: 12px;
}

.suggestions-dropdown {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  margin-top: 8px;
  background: rgba(0, 0, 0, 0.95);
  border-radius: 12px;
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.2);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
  max-height: 300px;
  overflow-y: auto;
  z-index: 1101;
}

.suggestions-dropdown::-webkit-scrollbar {
  width: 8px;
}

.suggestions-dropdown::-webkit-scrollbar-track {
  background: rgba(255, 255, 255, 0.1);
  border-radius: 4px;
}

.suggestions-dropdown::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.3);
  border-radius: 4px;
}

.suggestions-dropdown::-webkit-scrollbar-thumb:hover {
  background: rgba(255, 255, 255, 0.5);
}

.suggestion-item {
  display: flex;
  align-items: center;
  padding: 12px 16px;
  cursor: pointer;
  transition: all 0.2s ease;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.suggestion-item:last-child {
  border-bottom: none;
}

.suggestion-item:hover,
.suggestion-item.selected {
  background: rgba(59, 130, 246, 0.2);
}

.suggestion-icon {
  margin-right: 12px;
  color: rgba(59, 130, 246, 0.8);
  font-size: 14px;
  flex-shrink: 0;
}

.suggestion-content {
  flex: 1;
  min-width: 0;
}

.suggestion-name {
  color: white;
  font-weight: 500;
  font-size: 14px;
  margin-bottom: 2px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.suggestion-details {
  color: #ccc;
  font-size: 12px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
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

  .search-wrapper {
    min-width: 0;
    flex: 1;
  }

  .search-container {
    padding: 12px 14px;
    min-width: 0;
  }

  .suggestions-dropdown {
    max-height: 250px;
  }

  .suggestion-item {
    padding: 10px 14px;
  }
}
</style>