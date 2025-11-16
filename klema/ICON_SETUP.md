# App Icon Setup Guide

This guide explains how to change the app icon for **Mobile (Android)** and **Desktop (Electron)** platforms.

## 📱 Mobile (Android)

### Location
Android icons are stored in: `android/app/src/main/res/mipmap-*/`

### Required Icons

You need to replace icons in the following density folders:
- `mipmap-mdpi/` - 108x108 dp
- `mipmap-hdpi/` - 162x162 dp  
- `mipmap-xhdpi/` - 216x216 dp
- `mipmap-xxhdpi/` - 324x324 dp
- `mipmap-xxxhdpi/` - 432x432 dp

### Files to Replace

Each density folder contains:
- `ic_launcher.png` - Square icon
- `ic_launcher_round.png` - Round icon  
- `ic_launcher_foreground.png` - Foreground layer (for adaptive icons)

### How to Replace

1. **Prepare your icon image** (1024x1024px PNG with transparent background recommended)
2. **Generate sizes** using one of these tools:
   - **Online**: [Android Asset Studio](https://romannurik.github.io/AndroidAssetStudio/icons-launcher.html)
   - **CLI**: `@capacitor/assets` plugin: `npx @capacitor/assets generate --iconPath path/to/your-icon.png`
   - **Manual**: Resize to each density size using image editing software

3. **Replace the files** in each `mipmap-*/` folder:
   ```
   android/app/src/main/res/
     ├── mipmap-mdpi/
     │   ├── ic_launcher.png
     │   ├── ic_launcher_round.png
     │   └── ic_launcher_foreground.png
     ├── mipmap-hdpi/
     │   └── ...
     └── ...
   ```

4. **Rebuild the app**:
   ```bash
   npm run build:mobile
   # or
   npm run cap:sync
   ```

### Quick Method with @capacitor/assets

If you install `@capacitor/assets`:
```bash
npm install --save-dev @capacitor/assets
npx @capacitor/assets generate --iconPath path/to/your-icon.png --splashPath path/to/your-splash.png
```

This will automatically generate all required sizes and replace them in the correct locations.

---

## 🖥️ Desktop (Electron)

### Location
Desktop icons should be in: `assets/` directory

### Required Icons

- **Windows**: `assets/icon.ico` (256x256px recommended)
- **macOS**: `assets/icon.icns` (512x512px recommended)
- **Linux**: `assets/icon.png` (512x512px recommended)

### How to Replace

1. **Prepare your icon** (1024x1024px PNG recommended as base)

2. **Generate platform-specific formats**:

   **Option A: Using electron-icon-maker (Recommended)**
   ```bash
   npm install --save-dev electron-icon-maker
   npx electron-icon-maker --input=path/to/your-icon.png --output=./assets
   ```

   **Option B: Using electron-builder-icon-set**
   ```bash
   npm install --save-dev electron-builder-icon-set
   npx electron-builder-icon-set path/to/your-icon.png
   ```

   **Option C: Manual conversion**
   - **Windows (.ico)**: Use [IconConverter](https://www.icoconverter.com/) or ImageMagick:
     ```bash
     convert icon.png -define icon:auto-resize=256,128,64,48,32,16 icon.ico
     ```
   - **macOS (.icns)**: Use [iconutil](https://developer.apple.com/library/archive/documentation/GraphicsAnimation/Conceptual/HighResolutionOSX/Optimizing/Optimizing.html) or [icnsify](https://www.npmjs.com/package/icnsify):
     ```bash
     # Create iconset folder structure first
     mkdir icon.iconset
     # Add various sizes (16x16, 32x32, 128x128, 256x256, 512x512, 1024x1024)
     # Then convert:
     iconutil -c icns icon.iconset
     ```
   - **Linux (.png)**: Just use a 512x512px PNG

3. **Place the icons** in `assets/` folder:
   ```
   assets/
     ├── icon.ico    (Windows)
     ├── icon.icns   (macOS)
     └── icon.png    (Linux)
   ```

4. **Configuration** is already set in `package.json`:
   ```json
   "build": {
     "win": { "icon": "assets/icon.ico" },
     "mac": { "icon": "assets/icon.icns" },
     "linux": { "icon": "assets/icon.png" }
   }
   ```

5. **Rebuild the desktop app**:
   ```bash
   npm run build:desktop
   # or
   npm run electron:build
   ```

### Note
The `electron/main.cjs` also references `assets/icon.png` for the window icon during development. Make sure this exists or update the path if needed.

---

## 🚀 Quick Setup (All Platforms)

For the fastest setup, create a single 1024x1024px PNG icon and use automated tools:

```bash
# Install tools
npm install --save-dev @capacitor/assets electron-icon-maker

# Generate Android icons
npx @capacitor/assets generate --iconPath your-icon.png

# Generate Electron icons  
npx electron-icon-maker --input=your-icon.png --output=./assets
```

This will automatically generate all required sizes and formats for both mobile and desktop platforms.

---

## ✅ Verification

After replacing icons:

**Android:**
```bash
npm run build:mobile
npm run cap:open:android
# Check the app icon in Android Studio or on device
```

**Desktop:**
```bash
npm run build:desktop
# Check the built app icon in release/ folder
```

