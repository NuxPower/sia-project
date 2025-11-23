const fs = require('fs');
const path = require('path');
require('dotenv').config({ path: path.join(__dirname, '../.env.production') });

// Read the manifest.json to get the correct asset paths
const manifestPath = path.join(__dirname, '../dist/manifest.json');
const manifest = JSON.parse(fs.readFileSync(manifestPath, 'utf8'));

// Get the main entry point
const appEntry = manifest['resources/js/app.js'];
const cssEntry = manifest['resources/sass/app.scss'];

// Generate HTML content
const htmlContent = `<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Security-Policy" content="default-src 'self' 'unsafe-inline' 'unsafe-eval' data: blob: https: http:; img-src 'self' data: https: http: blob:; font-src 'self' data: https:; connect-src 'self' https: http: ws: wss:;">
    <title>KLEMA - Climate-Smart Agriculture Monitoring System</title>
    
    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- CSS -->
    ${appEntry.css.map(css => `<link rel="stylesheet" href="./${css}">`).join('\n    ')}
    ${cssEntry ? `<link rel="stylesheet" href="./${cssEntry.file}">` : ''}
    
    <style>
        body {
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #0f172a, #1e293b);
            transition: background 0.3s ease;
            font-family: 'Nunito', sans-serif;
        }

        body:not(.auth-mode) {
            overflow: hidden;
        }

        body.auth-mode {
            min-height: 100vh;
            overflow-x: hidden;
            overflow-y: auto;
            background: radial-gradient(circle at 15% 20%, rgba(59, 130, 246, 0.45), transparent 55%),
                        radial-gradient(circle at 85% 30%, rgba(14, 165, 233, 0.40), transparent 50%),
                        linear-gradient(135deg, #0f172a 0%, #1e293b 45%, #111827 100%);
        }

        #app {
            width: 100%;
            height: 100vh;
        }

        /* Loading indicator */
        .loading {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            color: white;
            font-size: 18px;
        }
    </style>
</head>
<body>
    <div id="app">
        <div class="loading">Loading KLEMA...</div>
    </div>
    
    <!-- JavaScript -->
    <!-- Set API base URL for Electron before app loads -->
    <script>
        // Detect Electron environment and set API base URL
        // This must run BEFORE any modules load
        (function() {
            const isElectron = typeof process !== 'undefined' && process.versions && process.versions.electron;
            if (isElectron || window.location?.protocol === 'file:') {
                // Get Railway URL from environment variables (injected at build time)
                const railwayUrl = ${JSON.stringify((process.env.APP_URL || process.env.RAILWAY_PUBLIC_DOMAIN || process.env.RAILWAY_STATIC_URL || 'https://klema.up.railway.app').replace(/\/app$/, ''))};
                const apiBaseUrl = railwayUrl;
                window.__ELECTRON_API_BASE_URL__ = apiBaseUrl;
                console.log('Electron detected, API base URL set to:', window.__ELECTRON_API_BASE_URL__);
            }
        })();
        
        // Error handling
        window.addEventListener('error', (event) => {
            console.error('Global error:', event.error);
            const loadingEl = document.querySelector('.loading');
            if (loadingEl) {
                loadingEl.innerHTML = 'Error loading app. Check console (F12).<br>' + (event.error?.message || event.message);
                loadingEl.style.color = '#ff6b6b';
            }
        });
        
        window.addEventListener('unhandledrejection', (event) => {
            console.error('Unhandled promise rejection:', event.reason);
        });
        
        console.log('Starting KLEMA app...');
        console.log('App entry file:', './${appEntry.file}');
    </script>
    <script type="module" src="./${appEntry.file}"></script>
    <script>
        // Fallback: if app doesn't mount after 5 seconds, show error
        setTimeout(() => {
            const appEl = document.getElementById('app');
            const loadingEl = document.querySelector('.loading');
            if (loadingEl && appEl && appEl.children.length === 1) {
                console.warn('App may not have mounted. Checking...');
                loadingEl.innerHTML = 'Still loading... Check DevTools (F12) for errors.';
            }
        }, 5000);
    </script>
</body>
</html>`;

// Write the HTML file
const outputPath = path.join(__dirname, '../dist/index.html');
fs.writeFileSync(outputPath, htmlContent, 'utf8');
console.log('Generated index.html at:', outputPath);

