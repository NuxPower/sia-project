import { CapacitorConfig } from '@capacitor/cli';

const env = (globalThis as unknown as { process?: { env?: Record<string, string | undefined> } }).process?.env ?? {};
const serverUrl = env.CAPACITOR_SERVER_URL || 'http://192.168.1.18:8000';
const isHttps = serverUrl.startsWith('https://');

const config: CapacitorConfig = {
  appId: 'com.klema.weather',
  appName: 'Klema',
  webDir: 'dist',
  server: {
    // For local development, use your computer's IP address:
    // Find it with: hostname -I or ip addr show
    // Example: url: 'http://192.168.1.100:8000',
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


