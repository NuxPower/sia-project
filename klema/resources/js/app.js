import './bootstrap';
import { createApp } from 'vue';
import WeatherDashboard from './components/WeatherDashboard.vue';

const app = createApp(WeatherDashboard);

document.addEventListener('DOMContentLoaded', () => {
    const appElement = document.getElementById('app');
    if (appElement) {
        app.mount('#app');
        console.log('Weather Dashboard mounted successfully');
    }
});