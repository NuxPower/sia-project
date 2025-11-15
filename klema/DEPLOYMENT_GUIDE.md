# KLEMA Deployment Guide

This guide covers deploying KLEMA to **Web**, **Desktop**, and **Mobile** platforms.

## Table of Contents
1. [Web Deployment](#web-deployment)
2. [Mobile Deployment (Android/iOS)](#mobile-deployment)
3. [Desktop Deployment (Windows/macOS/Linux)](#desktop-deployment)
4. [Environment Configuration](#environment-configuration)

---

## Web Deployment

### Prerequisites
- PHP 8.2+ with required extensions
- Composer
- Node.js 18+ and npm
- PostgreSQL database
- Web server (Nginx/Apache) or use Laravel's built-in server for development

### Steps

#### 1. Build Frontend Assets
```bash
cd klema
npm install
npm run build
```

This creates optimized production assets in `public/build/`.

#### 2. Configure Environment
```bash
cp .env.example .env  # If .env doesn't exist
php artisan key:generate
```

Edit `.env` with your production settings:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=pgsql
DB_HOST=your-db-host
DB_PORT=5432
DB_DATABASE=klema
DB_USERNAME=your-username
DB_PASSWORD=your-password

# Weather API
WEATHER_API_KEY=your-openweather-api-key

# CORS (if frontend is on different domain)
SANCTUM_STATEFUL_DOMAINS=your-frontend-domain.com
SESSION_DOMAIN=.your-domain.com
```

#### 3. Install Dependencies & Optimize
```bash
composer install --optimize-autoloader --no-dev
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

#### 4. Run Migrations
```bash
php artisan migrate --force
```

#### 5. Set Permissions (Linux)
```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

#### 6. Deploy to Server

**Option A: Traditional Web Server (Nginx/Apache)**

**Nginx Configuration** (`/etc/nginx/sites-available/klema`):
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/klema/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

**Option B: Laravel Forge / Vapor / Heroku**
- Follow platform-specific Laravel deployment guides
- Ensure `public/` is set as web root

#### 7. Queue Workers (if using queues)
```bash
# Using Supervisor (recommended for production)
# Create /etc/supervisor/conf.d/klema-worker.conf
```

---

## Mobile Deployment

### Prerequisites
- Node.js 18+
- Capacitor CLI: `npm install -g @capacitor/cli`
- Android Studio (for Android)
- Xcode (for iOS, macOS only)

### Setup Capacitor

#### 1. Install Capacitor
```bash
cd klema
npm install @capacitor/core @capacitor/cli
npm install @capacitor/android @capacitor/ios
```

#### 2. Initialize Capacitor (if not already done)
```bash
npx cap init
# App name: Klema
# App ID: com.klema.weather
# Web dir: dist
```

#### 3. Build Frontend for Mobile
```bash
npm run build
```

#### 4. Update Capacitor Configuration
Edit `capacitor.config.ts` (or `.json`):
```typescript
import { CapacitorConfig } from '@capacitor/cli';

const config: CapacitorConfig = {
  appId: 'com.klema.weather',
  appName: 'Klema',
  webDir: 'dist',
  server: {
    // For development: point to your local server
    // url: 'http://localhost:8000',
    // cleartext: true
    
    // For production: point to your deployed API
    url: 'https://your-api-domain.com',
    cleartext: false
  },
  android: {
    allowMixedContent: true,
    buildOptions: {
      keystorePath: undefined,
      keystoreAlias: undefined,
    }
  },
  ios: {
    scheme: 'klema'
  }
};

export default config;
```

#### 5. Sync with Native Projects
```bash
npx cap sync
```

### Android Deployment

#### 1. Open in Android Studio
```bash
npx cap open android
```

#### 2. Configure Build
- In Android Studio, go to `Build > Generate Signed Bundle / APK`
- Create or use existing keystore
- Build APK or AAB (Android App Bundle)

#### 3. Build from Command Line
```bash
cd android
./gradlew assembleRelease  # For APK
./gradlew bundleRelease    # For AAB (Google Play)
```

Output: `android/app/build/outputs/apk/release/app-release.apk`

#### 4. Publish to Google Play
1. Create app in Google Play Console
2. Upload AAB file
3. Complete store listing
4. Submit for review

### iOS Deployment

#### 1. Open in Xcode
```bash
npx cap open ios
```

#### 2. Configure Signing
- In Xcode, select project > Signing & Capabilities
- Select your development team
- Configure bundle identifier

#### 3. Build & Archive
- Select "Any iOS Device" as target
- Product > Archive
- Distribute via App Store or Ad Hoc

#### 4. Publish to App Store
1. Upload via Xcode or Transporter
2. Complete App Store Connect listing
3. Submit for review

---

## Desktop Deployment

### Prerequisites
- Node.js 18+
- Electron Builder

### Setup Electron

#### 1. Install Electron
```bash
cd klema
npm install --save-dev electron electron-builder
npm install --save electron-updater  # Optional: for auto-updates
```

#### 2. Create Electron Main Process
Create `electron/main.js`:
```javascript
const { app, BrowserWindow } = require('electron');
const path = require('path');
const isDev = process.env.NODE_ENV === 'development';

function createWindow() {
  const win = new BrowserWindow({
    width: 1200,
    height: 800,
    webPreferences: {
      nodeIntegration: false,
      contextIsolation: true,
      preload: path.join(__dirname, 'preload.js')
    }
  });

  if (isDev) {
    win.loadURL('http://localhost:8000');
    win.webContents.openDevTools();
  } else {
    win.loadFile(path.join(__dirname, '../dist/index.html'));
  }
}

app.whenReady().then(createWindow);

app.on('window-all-closed', () => {
  if (process.platform !== 'darwin') {
    app.quit();
  }
});

app.on('activate', () => {
  if (BrowserWindow.getAllWindows().length === 0) {
    createWindow();
  }
});
```

#### 3. Create Preload Script
Create `electron/preload.js`:
```javascript
const { contextBridge } = require('electron');

contextBridge.exposeInMainWorld('electron', {
  // Expose safe APIs here if needed
});
```

#### 4. Update package.json
Add Electron scripts and build configuration:
```json
{
  "main": "electron/main.js",
  "scripts": {
    "electron": "electron .",
    "electron:dev": "NODE_ENV=development electron .",
    "electron:build": "npm run build && electron-builder",
    "electron:build:win": "npm run build && electron-builder --win",
    "electron:build:mac": "npm run build && electron-builder --mac",
    "electron:build:linux": "npm run build && electron-builder --linux"
  }
}
```

#### 5. Configure Electron Builder
Add to `package.json`:
```json
{
  "build": {
    "appId": "com.klema.weather",
    "productName": "Klema",
    "directories": {
      "output": "release"
    },
    "files": [
      "dist/**/*",
      "electron/**/*",
      "public/**/*"
    ],
    "win": {
      "target": ["nsis", "portable"],
      "icon": "assets/icon.ico"
    },
    "mac": {
      "target": ["dmg", "zip"],
      "icon": "assets/icon.icns",
      "category": "public.app-category.utilities"
    },
    "linux": {
      "target": ["AppImage", "deb"],
      "icon": "assets/icon.png",
      "category": "Utility"
    }
  }
}
```

#### 6. Build Desktop Apps
```bash
# Build for current platform
npm run electron:build

# Build for specific platform
npm run electron:build:win
npm run electron:build:mac
npm run electron:build:linux
```

Outputs will be in `release/` directory.

---

## Environment Configuration

### API Endpoints

For mobile and desktop apps, configure API endpoints:

**Development:**
```env
API_URL=http://localhost:8000
```

**Production:**
```env
API_URL=https://your-api-domain.com
```

### CORS Configuration

In Laravel `config/cors.php`:
```php
'paths' => ['api/*', 'sanctum/csrf-cookie'],
'allowed_origins' => [
    'http://localhost:8000',
    'https://your-web-domain.com',
    'capacitor://localhost',  // For Capacitor
    'ionic://localhost',      // For Ionic
],
```

### Sanctum Configuration

In `config/sanctum.php`:
```php
'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
    '%s%s',
    'localhost,localhost:3000,localhost:8000,127.0.0.1,127.0.0.1:8000,::1',
    env('APP_URL') ? ','.parse_url(env('APP_URL'), PHP_URL_HOST) : ''
))),
```

---

## Quick Deployment Checklist

### Web
- [ ] Build frontend assets (`npm run build`)
- [ ] Configure `.env` for production
- [ ] Run `composer install --no-dev`
- [ ] Cache config, routes, views
- [ ] Run migrations
- [ ] Set proper file permissions
- [ ] Configure web server
- [ ] Set up SSL certificate
- [ ] Configure queue workers (if needed)

### Mobile
- [ ] Install Capacitor dependencies
- [ ] Build frontend (`npm run build`)
- [ ] Configure `capacitor.config.ts`
- [ ] Run `npx cap sync`
- [ ] Open in Android Studio / Xcode
- [ ] Configure signing certificates
- [ ] Build release version
- [ ] Test on devices
- [ ] Submit to app stores

### Desktop
- [ ] Install Electron dependencies
- [ ] Create Electron main process
- [ ] Configure electron-builder
- [ ] Build frontend (`npm run build`)
- [ ] Build Electron app
- [ ] Test on target platforms
- [ ] Code sign (for distribution)
- [ ] Distribute via website or auto-updater

---

## Troubleshooting

### Mobile: API Connection Issues
- Ensure `server.url` in Capacitor config points to correct API
- Check CORS settings in Laravel
- Verify SSL certificate is valid
- For development, use `cleartext: true` only on local network

### Desktop: Blank Screen
- Check that `dist/index.html` exists after build
- Verify API endpoints are accessible
- Check browser console in DevTools

### Web: Assets Not Loading
- Run `php artisan storage:link` if using storage
- Clear cache: `php artisan cache:clear`
- Verify `public/build/` contains built assets
- Check file permissions

---

## Additional Resources

- [Laravel Deployment](https://laravel.com/docs/deployment)
- [Capacitor Documentation](https://capacitorjs.com/docs)
- [Electron Documentation](https://www.electronjs.org/docs)
- [Vite Build Guide](https://vitejs.dev/guide/build.html)




