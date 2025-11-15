# Quick Fix: Mobile App Connection Error

## The Problem
Your app shows "Web page not available" because it's trying to connect to a placeholder URL.

## Quick Fix Steps

### 1. Find Your Computer's IP Address

Run this on your computer:
```bash
hostname -I
```

Or:
```bash
ip addr show | grep "inet " | grep -v 127.0.0.1
```

You'll get something like `192.168.1.100` or `10.0.0.5`

### 2. Update Capacitor Config

Edit `capacitor.config.ts` and replace the IP in the server URL:

```typescript
server: {
  url: 'http://YOUR_IP_HERE:8000',  // Replace YOUR_IP_HERE with your actual IP
  cleartext: true
}
```

For example, if your IP is `192.168.1.100`:
```typescript
server: {
  url: 'http://192.168.1.100:8000',
  cleartext: true
}
```

### 3. Start Laravel Server on Network

Make sure your Laravel server is accessible from your network:

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

**Important**: Use `--host=0.0.0.0` so it's accessible from other devices!

### 4. Rebuild and Reinstall App

```bash
npm run build:mobile
cd android
./gradlew assembleDebug
adb install app/build/outputs/apk/debug/app-debug.apk
```

Or use the all-in-one command:
```bash
npm run android:build:install
```

### 5. Make Sure Phone and Computer Are on Same WiFi

Both devices must be on the same network!

## Alternative: Use Production URL

If you have a deployed server, update the config to:

```typescript
server: {
  url: 'https://your-actual-domain.com',
  cleartext: false
}
```

## Troubleshooting

**Still not working?**
1. Check firewall: Make sure port 8000 is open
2. Verify IP: Make sure you're using the correct IP address
3. Test in browser: Try `http://YOUR_IP:8000` in your phone's browser
4. Check network: Ensure phone and computer are on same WiFi












