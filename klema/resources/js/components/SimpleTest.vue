<template>
  <div class="simple-test" style="background: #1a1a1a !important; color: white !important; padding: 20px !important; min-height: 100vh !important; position: relative !important; z-index: 9999 !important; display: block !important; visibility: visible !important;">
    <h1 style="font-size: 2rem; margin-bottom: 20px;">🌱 KLEMA Weather Dashboard</h1>
    <p style="font-size: 1.2rem; margin-bottom: 20px;">Vue.js is working! 🎉</p>
    <div style="background: rgba(0,0,0,0.7); padding: 20px; border-radius: 10px; margin: 20px 0;">
      <h3>Current Status:</h3>
      <p>✅ Vue.js loaded successfully</p>
      <p>✅ Component mounted</p>
      <p>✅ Dark theme applied</p>
      <p>✅ Component is rendering</p>
    </div>
    <button @click="testClick" style="background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px;">
      Test Button (Click me!)
    </button>
    <p v-if="clicked" style="color: #28a745; font-weight: bold; margin-top: 10px;">🎉 Button clicked! Vue reactivity is working!</p>
    
    <!-- Debug info -->
    <div style="position: fixed; top: 10px; right: 10px; background: red; color: white; padding: 10px; border-radius: 5px;">
      Vue Component Active!
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue';

export default {
  name: 'SimpleTest',
  setup() {
    const clicked = ref(false);
    
    const testClick = () => {
      clicked.value = true;
      console.log('Button clicked!');
    };
    
    onMounted(() => {
      console.log('SimpleTest component mounted!');
      console.log('Component element:', document.querySelector('.simple-test'));
    });
    
    return {
      clicked,
      testClick
    };
  }
};
</script>
