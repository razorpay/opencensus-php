import { PlaywrightTestConfig } from '@playwright/test';
import universePlaywrightConfig from '@razorpay/universe-cli/e2e.web';

const basePlaywrightConfig = universePlaywrightConfig as PlaywrightTestConfig;

const config: PlaywrightTestConfig = {
  ...basePlaywrightConfig,
};

export default config;
