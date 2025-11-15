# Fix Android Build - Missing Gradle Wrapper

## Problem
The Android project is missing the Gradle wrapper (`gradlew`), which is needed to build the APK.

## Solution

### Option 1: Re-add Android Platform (Recommended)

1. **Remove the incomplete Android project:**
   ```bash
   cd klema
   rm -rf android
   ```

2. **Build your frontend:**
   ```bash
   npm run build
   ```

3. **Add Android platform:**
   ```bash
   npx @capacitor/cli add android
   ```

4. **Sync Capacitor:**
   ```bash
   npm run cap:sync
   ```

5. **Now you should have gradlew:**
   ```bash
   cd android
   ls -la gradlew
   chmod +x gradlew
   ```

6. **Build the APK:**
   ```bash
   ./gradlew assembleDebug
   ```

### Option 2: Use System Gradle (If Available)

If you have Gradle installed system-wide:

```bash
cd android
gradle assembleDebug
```

Install Gradle on Arch Linux:
```bash
sudo pacman -S gradle
```

### Option 3: Manual Gradle Wrapper Setup

If the above doesn't work, you can manually create the Gradle wrapper:

1. **Install Gradle** (if not installed):
   ```bash
   sudo pacman -S gradle
   ```

2. **Navigate to android directory:**
   ```bash
   cd android
   ```

3. **Create Gradle wrapper:**
   ```bash
   gradle wrapper --gradle-version 8.2
   ```

4. **Make it executable:**
   ```bash
   chmod +x gradlew
   ```

5. **Build:**
   ```bash
   ./gradlew assembleDebug
   ```

## Verify Setup

After fixing, verify the setup:

```bash
cd android
./gradlew --version
```

You should see Gradle version information.

## Build and Install on Phone

Once gradlew is working:

```bash
# From klema directory
npm run android:build:install
```

Or manually:
```bash
npm run build
npm run cap:sync
cd android
./gradlew assembleDebug
adb install app/build/outputs/apk/debug/app-debug.apk
```












