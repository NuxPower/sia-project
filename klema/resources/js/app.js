import './bootstrap';
import { createApp } from 'vue';
import WeatherDashboard from './components/WeatherDashboard.vue';

const app = createApp(WeatherDashboard);

function mountApp() {
    const appElement = document.getElementById('app');
    if (appElement) {
        app.mount('#app');
        console.log('Weather Dashboard mounted successfully');
    } else {
        console.error('App element not found');
    }
}

// Mount immediately if DOM is ready, otherwise wait for DOMContentLoaded
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', mountApp);
} else {
    // DOM is already ready
    mountApp();
}