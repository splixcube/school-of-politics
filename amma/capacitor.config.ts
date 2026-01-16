import type { CapacitorConfig } from '@capacitor/cli';

const config: CapacitorConfig = {
  appId: 'io.ionic.starter',
  appName: 'amma',
  webDir: 'www',
  server: {
     url: 'https://amma-749f8.web.app',
      cleartext: true,
    },
};

export default config;
