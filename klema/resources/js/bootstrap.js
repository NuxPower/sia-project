import 'bootstrap';

/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

import axios from 'axios';
import { getApiToken, revokeApiToken } from './services/auth';

const isMobileContainer = () => {
    if (typeof window === 'undefined') {
        return false;
    }
    return window.location?.protocol === 'capacitor:' || window.location?.protocol === 'ionic:';
};

const isElectron = () => {
    if (typeof window === 'undefined' || typeof process === 'undefined') {
        return false;
    }
    // Check for Electron environment
    return window.navigator?.userAgent?.includes('Electron') || 
           (typeof process !== 'undefined' && process.versions?.electron);
};

const hasSpaRoot = () => {
    if (typeof document === 'undefined') {
        return false;
    }
    return Boolean(document.getElementById('app'));
};
window.axios = axios;

// Set API base URL for Electron - MUST be set before any axios calls
// Check for Electron environment
const isElectronEnv = isElectron() || window.__ELECTRON_API_BASE_URL__ || 
                      (typeof window !== 'undefined' && window.location?.protocol === 'file:');

if (isElectronEnv) {
    // Use the production API URL for Electron (from env vars or fallback)
    const apiBaseUrl = window.__ELECTRON_API_BASE_URL__ || 
                      import.meta.env.VITE_APP_URL?.replace(/\/app$/, '') ||
                      import.meta.env.VITE_RAILWAY_PUBLIC_DOMAIN ||
                      'https://klema.up.railway.app';
    console.log('Setting API base URL for Electron:', apiBaseUrl);
    
    // Set on both window.axios and the axios module itself (since modules import axios directly)
    window.axios.defaults.baseURL = apiBaseUrl;
    axios.defaults.baseURL = apiBaseUrl;
    
    // Also set for any future axios imports
    if (typeof window !== 'undefined') {
        window.__AXIOS_BASE_URL__ = apiBaseUrl;
    }
}

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.withCredentials = true;
window.axios.defaults.xsrfHeaderName = 'X-XSRF-TOKEN';
window.axios.defaults.xsrfCookieName = 'XSRF-TOKEN';

window.axios.interceptors.request.use((config) => {
    const token = getApiToken();

    if (token && !config.headers?.Authorization) {
        config.headers = {
            ...config.headers,
            Authorization: `Bearer ${token}`,
        };
    }

    config.withCredentials = true;

    return config;
});

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

// import Echo from 'laravel-echo';

// import Pusher from 'pusher-js';
// window.Pusher = Pusher;

// window.Echo = new Echo({
//     broadcaster: 'pusher',
//     key: import.meta.env.VITE_PUSHER_APP_KEY,
//     cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1',
//     wsHost: import.meta.env.VITE_PUSHER_HOST ?? `ws-${import.meta.env.VITE_PUSHER_APP_CLUSTER}.pusher.com`,
//     wsPort: import.meta.env.VITE_PUSHER_PORT ?? 80,
//     wssPort: import.meta.env.VITE_PUSHER_PORT ?? 443,
//     forceTLS: (import.meta.env.VITE_PUSHER_SCHEME ?? 'https') === 'https',
//     enabledTransports: ['ws', 'wss'],
// });

window.axios.interceptors.response.use(
    response => response,
    error => {
        if (error?.response?.status === 423) {
            window.location.href = '/email/verify';
        }

        if (error?.response?.status === 401) {
            revokeApiToken();

            if (isMobileContainer() || hasSpaRoot()) {
                window.dispatchEvent(new CustomEvent('auth:required', {
                    detail: {
                        reason: 'unauthorized'
                    }
                }));
            } else {
                window.location.href = '/app';
            }
        }

        return Promise.reject(error);
    }
);
