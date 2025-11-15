# Deploying to Your Phone (Without Android Studio)

You can build and install the app directly on your Android phone without Android Studio!

## Option 1: Build APK and Install via USB (Recommended)

### Prerequisites

1. **Install Android SDK Platform Tools (ADB)**
   
   On Arch Linux:
   ```bash
   sudo pacman -S android-tools
   ```
   
   On Ubuntu/Debian:
   ```bash
   sudo apt install android-tools-adb android-tools-fastboot
   ```
   
   On macOS:
   ```bash
   brew install android-platform-tools
   ```

2. **Enable USB Debugging on Your Phone**
   - Go to Settings → About Phone
   - Tap "Build Number" 7 times to enable Developer Options
   - Go back to Settings → Developer Options
   - Enable "USB Debugging"
   - Connect phone via USB

3. **Verify Connection**
   ```bash
   adb devices
   ```
   You should see your device listed. If not, you may need to authorize the computer on your phone.

### Build and Install Steps

1. **Build the frontend:**
   ```bash
   npm run build
   ```

2. **Sync with Capacitor:**
   ```bash
   npm run cap:sync
   ```

3. **Build the APK using Gradle:**
   ```bash
   cd android
   ./gradlew assembleDebug
   ```
   
   Or if you don't have execute permissions:
   ```bash
   cd android
   chmod +x gradlew
   ./gradlew assembleDebug
   ```

4. **Find the APK:**
   The APK will be at:
   ```
   android/app/build/outputs/apk/debug/app-debug.apk
   ```

5. **Install on your phone:**
   ```bash
   adb install android/app/build/outputs/apk/debug/app-debug.apk
   ```
   
   Or manually:
   - Copy `app-debug.apk` to your phone
   - Open it on your phone
   - Allow installation from unknown sources if prompted

### Build Release APK (for distribution)

For a release build (smaller, optimized):
```bash
cd android
./gradlew assembleRelease
```

The APK will be at:
```
android/app/build/outputs/apk/release/app-release.apk
```

**Note:** Release builds require signing. For testing, use debug builds.

---

## Option 2: Use Capacitor Live Reload (Development)

If you want to develop and see changes live on your phone:

1. **Make sure your phone and computer are on the same WiFi network**

2. **Find your computer's local IP address:**
   ```bash
   # Linux/macOS
   ip addr show | grep "inet " | grep -v 127.0.0.1
   
   # Or
   hostname -I
   ```

3. **Update `capacitor.config.ts`:**
   ```typescript
   server: {
     url: 'http://YOUR_IP_ADDRESS:8000',  // Replace with your IP
     cleartext: true
   }
   ```

4. **Start Laravel dev server (accessible on network):**
   ```bash
   php artisan serve --host=0.0.0.0 --port=8000
   ```

5. **Sync Capacitor:**
   ```bash
   npm run cap:sync
   ```

6. **Build and install the app once:**
   ```bash
   npm run build
   npm run cap:sync
   cd android
   ./gradlew assembleDebug
   adb install app/build/outputs/apk/debug/app-debug.apk
   ```

7. **For live reload, use Vite dev server:**
   ```bash
   # Terminal 1: Laravel API
   php artisan serve --host=0.0.0.0 --port=8000
   
   # Terminal 2: Vite dev server
   npm run dev -- --host YOUR_IP_ADDRESS
   ```

8. **Update capacitor.config.ts to point to Vite:**
   ```typescript
   server: {
     url: 'http://YOUR_IP_ADDRESS:5173',  // Vite default port
     cleartext: true
   }
   ```

9. **Sync again:**
   ```bash
   npm run cap:sync
   ```

Now when you make changes to your Vue.js code, they'll reload on your phone!

---

## Option 3: Build APK and Transfer Manually

1. **Build the APK** (follow steps 1-3 from Option 1)

2. **Transfer APK to phone:**
   - Email it to yourself
   - Use cloud storage (Google Drive, Dropbox, etc.)
   - Use USB file transfer
   - Use `adb push`:
     ```bash
     adb push android/app/build/outputs/apk/debug/app-debug.apk /sdcard/Download/
     ```

3. **Install on phone:**
   - Open file manager on phone
   - Navigate to Downloads
   - Tap the APK file
   - Allow installation from unknown sources if prompted
   - Install

---

## Troubleshooting

### ADB not detecting device
- Make sure USB debugging is enabled
- Try different USB cable/port
- On Linux, you may need udev rules:
  ```bash
  # Create /etc/udev/rules.d/51-android.rules
  SUBSYSTEM=="usb", ATTR{idVendor}=="YOUR_VENDOR_ID", MODE="0666", GROUP="plugdev"
  ```
- Restart ADB: `adb kill-server && adb start-server`

### Gradle build fails
- Make sure you have Java JDK installed:
  ```bash
  java -version
  ```
- Install JDK if needed:
  ```bash
  # Arch
  sudo pacman -S jdk-openjdk
  # Ubuntu
  sudo apt install openjdk-17-jdk
  ```

### App crashes on phone
- Check logs: `adb logcat | grep -i error`
- Make sure API URL in `capacitor.config.ts` is accessible from your phone
- For development, use your computer's IP address, not `localhost`

### Can't install APK
- Enable "Install from unknown sources" in phone settings
- On newer Android: Settings → Apps → Special access → Install unknown apps

---

## Quick Commands Reference

```bash
# Build frontend
npm run build

# Sync Capacitor
npm run cap:sync

# Build debug APK
cd android && ./gradlew assembleDebug

# Install on connected device
adb install android/app/build/outputs/apk/debug/app-debug.apk

# Check connected devices
adb devices

# View logs
adb logcat

# Uninstall app
adb uninstall com.klema.weather
```






