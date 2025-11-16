import { CapacitorConfig } from '@capacitor/cli';
import path from 'node:path';
import fs from 'node:fs';
import dotenv from 'dotenv';

const envCandidates = [
  process.env.KLEMA_ENV_FILE,
  process.env.NODE_ENV === 'development' ? '.env' : '.env.production',
  '.env.production',
  '.env',
].filter(Boolean) as string[];

const envPath = envCandidates
  .map((file) => path.resolve(process.cwd(), file))
  .find((candidate) => fs.existsSync(candidate));

if (envPath) {
  dotenv.config({ path: envPath });
}

const defaultServerUrl = 'http://192.168.1.18:8000/app';
const serverUrl = process.env.CAPACITOR_SERVER_URL || defaultServerUrl;
const isHttps = serverUrl.startsWith('https://');

const config: CapacitorConfig = {
  appId: 'com.klema.weather',
  appName: 'Klema',
  webDir: 'dist',
  server: {
    // For local development, use your computer's IP address:
    // Find it with: hostname -I or ip addr show
    // Example: url: 'http://192.168.1.100:8000/app',
    url: serverUrl,
    cleartext: !isHttps  // Required for HTTP (not HTTPS)
    
    // For production, use your deployed API:
    // url: 'https://your-actual-domain.com',
    // cleartext: false
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
  },
  plugins: {
    SplashScreen: {
      launchShowDuration: 2000,
      launchAutoHide: true,
      backgroundColor: "#ffffff",
      androidSplashResourceName: "splash",
      androidScaleType: "CENTER_CROP",
      showSpinner: true,
      androidSpinnerStyle: "large",
      iosSpinnerStyle: "small",
      spinnerColor: "#999999",
      splashFullScreen: true,
      splashImmersive: true,
      layoutName: "launch_screen",
      useDialog: true,
    },
  },
};

export default config;


