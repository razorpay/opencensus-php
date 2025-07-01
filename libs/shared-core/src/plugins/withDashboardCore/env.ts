import { DASHBOARD_BUILD_MODE } from '@src/constants';

const { STAGE, VERSION, BUILD_MODE = DASHBOARD_BUILD_MODE.LEGACY  } = process.env || {};

export const CONSUMER_APP_CONSTANTS = {
  APP_VERSION: VERSION,
  STAGE,
  isProd: ["production", "canary"].includes(STAGE as string),
  isDev: STAGE === 'development',
  // VERSION is passed via jobs in CI, keeping this the source of truth
  isCI: Boolean(VERSION),
  isModuleFederationV2: true,
  isWebpackWithSwc: true,
};

export const envConfig = {
  isLegacyMode: BUILD_MODE === DASHBOARD_BUILD_MODE.LEGACY,
  buildMode: BUILD_MODE,
};
