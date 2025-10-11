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
  top: 20px;
  right: 20px;
  z-index: 1000;
}

.search-container {
  display: flex;
  align-items: center;
  background: rgba(0, 0, 0, 0.7);
  border-radius: 12px;
  padding: 12px 16px;
  color: white;
  min-width: 300px;
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