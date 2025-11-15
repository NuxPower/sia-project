# Configuring API URL for Mobile App

## The Problem
The app is trying to connect to `https://your-laravel-app.com/` which is a placeholder URL that doesn't exist.

## Solution Options

### Option 1: Connect to Local Development Server (For Testing)

If you want to test with your local Laravel server:

1. **Find your computer's IP address:**
   ```bash
   hostname -I
   # Or
   ip addr show | grep "inet " | grep -v 127.0.0.1
   ```

2. **Start Laravel server on your network:**
   ```bash
   php artisan serve --host=0.0.0.0 --port=8000
   ```
   This makes the server accessible from other devices on your network.

3. **Update `capacitor.config.ts`:**
   ```typescript
   server: {
     url: 'http://YOUR_IP_ADDRESS:8000',  // Replace with your IP
     cleartext: true  // Required for HTTP (not HTTPS)
   }
   ```

4. **Rebuild and reinstall:**
   ```bash
   npm run build:mobile
   cd android
   ./gradlew assembleDebug
   adb install app/build/outputs/apk/debug/app-debug.apk
   ```

### Option 2: Connect to Deployed Production Server

If you have a deployed Laravel application:

1. **Update `capacitor.config.ts`:**
   ```typescript
   server: {
     url: 'https://your-actual-domain.com',  // Your real domain
     cleartext: false  // HTTPS doesn't need cleartext
   }
   ```

2. **Rebuild and reinstall** (same as above)

### Option 3: Bundle Assets Locally (No Server)

If you want the app to work offline with bundled assets:

1. **Update `capacitor.config.ts`:**
   ```typescript
   // Remove or comment out the server section
   // server: {
   //   url: '...',
   //   cleartext: true
   // }
   ```

2. **Make sure your Vue app can work as a standalone SPA** (may require API endpoint configuration in your Vue code)

3. **Rebuild and reinstall**

## Important Notes

- **For local testing**: Use your computer's local IP address (e.g., `192.168.1.100:8000`)
- **Make sure your phone and computer are on the same WiFi network**
- **For HTTP (not HTTPS)**: Set `cleartext: true`
- **For HTTPS**: Set `cleartext: false` or omit it
- **After changing config**: Always rebuild and reinstall the app

## Quick Setup for Local Testing

1. Get your IP: `hostname -I | awk '{print $1}'`
2. Start server: `php artisan serve --host=0.0.0.0 --port=8000`
3. Update config with your IP
4. Rebuild: `npm run build:mobile && cd android && ./gradlew assembleDebug && adb install app/build/outputs/apk/debug/app-debug.apk`






