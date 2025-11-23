// Preload script to set axios baseURL before any modules load
const { contextBridge } = require('electron');
require('dotenv').config({ path: require('path').join(__dirname, '../.env.production') });

// Get Railway URL from environment variables
const railwayUrl = process.env.APP_URL || process.env.RAILWAY_PUBLIC_DOMAIN || process.env.RAILWAY_STATIC_URL || 'https://klema.up.railway.app';
const apiBaseUrl = railwayUrl.replace(/\/app$/, ''); // Remove /app suffix if present

// This runs in the renderer process before any page scripts
// We can't directly modify axios here, but we can set a flag
contextBridge.exposeInMainWorld('__ELECTRON_PRELOAD__', {
  apiBaseUrl: apiBaseUrl,
  isElectron: true
});

