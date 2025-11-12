<template>
  <div class="search-bar">
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
  isLoading: Boolean
});

const emit = defineEmits(['update:modelValue', 'search']);

const localValue = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
});
</script>

<style scoped>
.search-bar {
  position: fixed;
  top: 24px;
  right: 24px;
  z-index: 1000;
}

.search-container {
  display: flex;
  align-items: center;
  background: rgba(0, 0, 0, 0.7);
  border-radius: 12px;
  padding: 14px 18px;
  color: white;
  min-width: 320px;
}

@media (max-width: 1024px) {
  .search-bar {
    top: 15px;
    right: 15px;
  }
  
  .search-container {
    min-width: 280px;
    padding: 10px 14px;
  }
}

@media (max-width: 768px) {
  .search-bar {
    top: 15px;
    right: 15px;
    left: 15px;
    width: auto;
  }
  
  .search-container {
    min-width: auto;
    width: 100%;
    padding: 10px 12px;
  }
  
  .search-input {
    font-size: 13px;
  }
}

@media (max-width: 480px) {
  .search-bar {
    top: 10px;
    right: 10px;
    left: 10px;
  }
  
  .search-container {
    padding: 8px 10px;
    border-radius: 10px;
  }
  
  .search-icon {
    font-size: 14px;
    margin-right: 8px;
  }
  
  .search-input {
    font-size: 12px;
  }
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
</style>