# Quick Deployment Reference

## Prerequisites Installation

```bash
# Install Node.js dependencies
npm install

# Install PHP dependencies (if not already done)
composer install

# Install Capacitor CLI globally (optional, but recommended)
npm install -g @capacitor/cli
```

## Web Deployment (Quick Steps)

```bash
# 1. Build frontend
npm run build

# 2. Configure environment
cp .env.example .env
php artisan key:generate
# Edit .env with your production settings

# 3. Optimize Laravel
composer install --optimize-autoloader --no-dev
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 4. Run migrations
php artisan migrate --force

# 5. Set permissions (Linux)
chmod -R 775 storage bootstrap/cache
```

## Mobile Deployment (Quick Steps)

```bash
# 1. Install Capacitor (if not already installed)
npm install @capacitor/core @capacitor/cli @capacitor/android @capacitor/ios

# 2. Initialize Capacitor (first time only)
npx cap init

# 3. Build frontend
npm run build

# 4. Update capacitor.config.ts with your API URL

# 5. Sync with native projects
npm run cap:sync

# 6. Open in IDE
npm run cap:open:android  # For Android
npm run cap:open:ios      # For iOS
```

## Desktop Deployment (Quick Steps)

```bash
# 1. Install Electron (if not already installed)
npm install --save-dev electron electron-builder

# 2. Build frontend
npm run build

# 3. Update electron/main.js with your API URL

# 4. Build for your platform
npm run electron:build        # Current platform
npm run electron:build:win    # Windows
npm run electron:build:mac    # macOS
npm run electron:build:linux  # Linux
```

## Environment Variables

### For Web (.env)
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
```

### For Mobile (capacitor.config.ts)
```typescript
server: {
  url: 'https://your-api-domain.com',
  cleartext: false
}
```

### For Desktop (electron/main.js or environment)
```bash
export ELECTRON_APP_URL=https://your-api-domain.com
```

## Common Commands

```bash
# Development
npm run dev                    # Start Vite dev server
composer dev                   # Start Laravel + Vite + Queue

# Building
npm run build                  # Build frontend for web
npm run build:web              # Same as above
npm run build:mobile           # Build + sync Capacitor
npm run build:desktop          # Build + package Electron

# Mobile
npm run cap:sync               # Sync web assets to native
npm run cap:open:android       # Open Android Studio
npm run cap:open:ios           # Open Xcode

# Desktop
npm run electron               # Run Electron (production mode)
npm run electron:dev           # Run Electron (dev mode)
npm run electron:build         # Build Electron app
```

## Troubleshooting Quick Fixes

**Assets not loading?**
```bash
php artisan cache:clear
php artisan config:clear
npm run build
```

**Mobile app can't connect to API?**
- Check `capacitor.config.ts` server URL
- Verify CORS settings in Laravel
- For development, set `cleartext: true`

**Electron shows blank screen?**
- Verify API URL in `electron/main.js`
- Check that API is accessible
- Open DevTools to see errors: `mainWindow.webContents.openDevTools()`
