#!/bin/bash

# Quick script to build and install KLEMA on your Android phone
# Make sure your phone is connected via USB with USB debugging enabled

set -e

echo "🔨 Building frontend..."
npm run build

echo "📱 Syncing with Capacitor..."
npm run cap:sync

echo "🏗️  Building Android APK..."
cd android
chmod +x gradlew
./gradlew assembleDebug

echo "📲 Installing on connected device..."
cd ..
adb install android/app/build/outputs/apk/debug/app-debug.apk

echo "✅ Done! The app should now be installed on your phone."
echo "📦 APK location: android/app/build/outputs/apk/debug/app-debug.apk"













