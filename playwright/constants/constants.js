const BASE_PATH = './playwright/storageState';
const { default: getEnv } = require('../utils/env');

const getStorageStatePath = () => ({
  EMAIL_TEST_LOGIN_STATE: `${BASE_PATH}/desktop-test-mode-login.json`,
  EMAIL_LIVE_LOGIN_STATE: `${BASE_PATH}/desktop-live-mode-login.json`,
  TRANSACTIONS_LOGIN_STATE: `${BASE_PATH}/desktop-transactions-login.json`,
  SETTLEMENTS_LOGIN_STATE: `${BASE_PATH}/desktop-settlement-login.json`,
  INTERNATIONAL_LOGIN_STATE: `${BASE_PATH}/desktop-international-login.json`,
  ACTIVATED_NOT_IE_STATE: `${BASE_PATH}/activated-not-ie-login.json`,
  CAPITAL_RESELLER_PARTNER_TEST_LOGIN_STATE: `${BASE_PATH}/capital-reseller-partner-desktop-test-mode-login.json`,
  MAGIC_CHECKOUT_STATE: `${BASE_PATH}/magic-checkout.json`,
  RESELLER_PARTNER_TEST_LOGIN_STATE: `${BASE_PATH}/reseller-partner-desktop-test-mode-login.json`,
  RESELLER_PARTNER_POS_TEST_LOGIN_STATE: `${BASE_PATH}/reseller-partner-pos-desktop-test-mode-login.json`,
  RESELLER_PARTNER_AGENT_TEST_LOGIN_STATE: `${BASE_PATH}/reseller-partner-agent-desktop-test-mode-login.json`,
  AGGREGATOR_PARTNER_TEST_LOGIN_STATE: `${BASE_PATH}/aggregator-partner-desktop-test-mode-login.json`,
  PLATFORM_PARTNER_TEST_LOGIN_STATE: `${BASE_PATH}/platform-partner-desktop-test-mode-login.json`,
  OPTIMIZER_LOGIN_STATE: `${BASE_PATH}/desktop-optimizer-login.json`,
  POS_LOGIN_STATE: `${BASE_PATH}/pos-login.json`,
  POS_ORDER_DETAILS_LOGIN_STATE: `${BASE_PATH}/pos-login-order-details.json`,
  OPTIMIZER_V1_LOGIN_STATE: `${BASE_PATH}/desktop-optimizer-v1-login.json`,
  INTERNATIONAL_ACTIVATION_STATE: `${BASE_PATH}/international-activation.json`,
  WALLET_REPORTS_LOGIN_STATE: `${BASE_PATH}/mobile-wallet-reports-login.json`,
  MOBILE_TEST_GCMS_STATE: `${BASE_PATH}/mobile-test-mode-gcms.json`,
  CURLEC_TEST_LOGIN_STATE: `${BASE_PATH}/curlec-test-mode-login.json`,
});

const routes = {
  SIGN_IN_PATH: '/?screen=sign_in&isTestEnv=true',
  DASHBOARD: '/app/dashboard',
};

const getEmailCredentials = () => {
  const ENV = getEnv();
  const StorageStatePath = getStorageStatePath(BASE_PATH);
  return [
    {
      type: 'desktop-test-mode-login.json',
      username: ENV.EMAIL_TEST_MODE_USERNAME,
      password: ENV.EMAIL_TEST_MODE_PASSWORD,
      storagePath: StorageStatePath.EMAIL_TEST_LOGIN_STATE,
    },
    {
      type: 'desktop-live-mode-login.json',
      username: ENV.EMAIL_LIVE_MODE_USERNAME,
      password: ENV.EMAIL_LIVE_MODE_PASSWORD,
      storagePath: StorageStatePath.EMAIL_LIVE_LOGIN_STATE,
    },
    {
      type: 'desktop-transactions-login.json',
      username: ENV.EMAIL_TRANSACTIONS_USERNAME,
      password: ENV.EMAIL_TRANSACTIONS_PASSWORD,
      storagePath: StorageStatePath.TRANSACTIONS_LOGIN_STATE,
    },
    {
      type: 'desktop-settlement-login.json',
      username: ENV.EMAIL_SETTLEMENT_USERNAME,
      password: ENV.EMAIL_SETTLEMENT_PASSWORD,
      storagePath: StorageStatePath.SETTLEMENTS_LOGIN_STATE,
    },
    {
      type: 'desktop-international-login.json',
      username: ENV.EMAIL_INTERNATIONAL_USERNAME,
      password: ENV.EMAIL_INTERNATIONAL_PASSWORD,
      storagePath: StorageStatePath.INTERNATIONAL_LOGIN_STATE,
    },
    {
      type: 'capital-reseller-partner-desktop-test-mode-login.json',
      username: ENV.CAPITAL_RESELLER_PARTNER_TEST_MODE_USERNAME,
      password: ENV.CAPITAL_RESELLER_PARTNER_TEST_MODE_PASSWORD,
      storagePath: StorageStatePath.CAPITAL_RESELLER_PARTNER_TEST_LOGIN_STATE,
    },
    {
      type: 'reseller-partner-desktop-test-mode-login.json',
      username: ENV.RESELLER_PARTNER_TEST_MODE_USERNAME,
      password: ENV.RESELLER_PARTNER_TEST_MODE_PASSWORD,
      storagePath: StorageStatePath.RESELLER_PARTNER_TEST_LOGIN_STATE,
    },
    {
      type: 'reseller-partner-pos-desktop-test-mode-login.json',
      username: ENV.RESELLER_PARTNER_POS_TEST_MODE_USERNAME,
      password: ENV.RESELLER_PARTNER_POS_TEST_MODE_PASSWORD,
      storagePath: StorageStatePath.RESELLER_PARTNER_POS_TEST_LOGIN_STATE,
    },
    {
      type: 'reseller-partner-agent-desktop-test-mode-login.json',
      username: ENV.RESELLER_PARTNER_AGENT_TEST_MODE_USERNAME,
      password: ENV.RESELLER_PARTNER_AGENT_TEST_MODE_PASSWORD,
      storagePath: StorageStatePath.RESELLER_PARTNER_AGENT_TEST_LOGIN_STATE,
    },
    {
      type: 'aggregator-partner-desktop-test-mode-login.json',
      username: ENV.AGGREGATOR_PARTNER_TEST_MODE_USERNAME,
      password: ENV.AGGREGATOR_PARTNER_TEST_MODE_PASSWORD,
      storagePath: StorageStatePath.AGGREGATOR_PARTNER_TEST_LOGIN_STATE,
    },
    {
      type: 'platform-partner-desktop-test-mode-login.json',
      username: ENV.PLATFORM_PARTNER_TEST_MODE_USERNAME,
      password: ENV.PLATFORM_PARTNER_TEST_MODE_PASSWORD,
      storagePath: StorageStatePath.PLATFORM_PARTNER_TEST_LOGIN_STATE,
    },
    {
      type: 'desktop-optimizer-login.json',
      username: ENV.EMAIL_OPTIMIZER_USERNAME,
      password: ENV.EMAIL_OPTIMIZER_PASSWORD,
      storagePath: StorageStatePath.OPTIMIZER_LOGIN_STATE,
    },
    {
      type: 'desktop-optimizer-v1-login.json',
      username: ENV.EMAIL_OPTIMIZER_V1_USERNAME,
      password: ENV.EMAIL_OPTIMIZER_V1_PASSWORD,
      storagePath: StorageStatePath.OPTIMIZER_V1_LOGIN_STATE,
    },
    {
      type: 'international-activation.json',
      username: ENV.INTERNATIONAL_ACTIVATION_USERNAME,
      password: ENV.INTERNATIONAL_ACTIVATION_PASSWORD,
      storagePath: StorageStatePath.INTERNATIONAL_ACTIVATION_STATE,
    },
  ];
};

const getMobileCredentials = () => {
  const ENV = getEnv();
  const StorageStatePath = getStorageStatePath(BASE_PATH);
  return [
    {
      type: 'mobile-wallet-reports-login.json',
      mobile: ENV.MOBILE_WALLET_REPORTS_MOBILE,
      storagePath: StorageStatePath.WALLET_REPORTS_LOGIN_STATE,
    },
    {
      type: 'mobile-test-mode-gcms.json',
      username: ENV.MOBILE_TEST_MODE_GCMS_USERNAME,
      mobile: ENV.MOBILE_TEST_MODE_GCMS_MOBILE,
      storagePath: StorageStatePath.MOBILE_TEST_GCMS_STATE,
    },
  ];
};

const getActivatedNotIECredentials = () => {
  const ENV = getEnv();
  const StorageStatePath = getStorageStatePath(BASE_PATH);
  return [
    {
      type: 'activated-not-ie-login.json',
      username: ENV.ACTIVATED_NOT_IE_USERNAME,
      password: ENV.ACTIVATED_NOT_IE_PASSWORD,
      storagePath: StorageStatePath.ACTIVATED_NOT_IE_STATE,
    },
  ];
};

const getMagicCheckoutCredentials = () => {
  const ENV = getEnv();
  const StorageStatePath = getStorageStatePath(BASE_PATH);
  return [
    {
      type: 'magic-checkout.json',
      username: ENV.MAGIC_CHECKOUT_USERNAME,
      password: ENV.MAGIC_CHECKOUT_PASSWORD,
      storagePath: StorageStatePath.MAGIC_CHECKOUT_STATE,
    },
  ];
};

const getPosCredentials = () => {
  const ENV = getEnv();
  const StorageStatePath = getStorageStatePath(BASE_PATH);
  return [
    {
      type: 'pos-login.json',
      username: ENV.POS_USERNAME,
      password: ENV.POS_PASSWORD,
      storagePath: StorageStatePath.POS_LOGIN_STATE,
    },
    {
      type: 'pos-login-order-details.json',
      username: ENV.POS_ORDER_DETAILS_USERNAME,
      password: ENV.POS_ORDER_DETAILS_PASSWORD,
      storagePath: StorageStatePath.POS_ORDER_DETAILS_LOGIN_STATE,
    },
  ];
};

const getCurlecCredentials = () => {
  const StorageStatePath = getStorageStatePath(BASE_PATH);
  return [
    {
      type: 'curlec-merchant-desktop-test-mode-login.json',
      username: process.env.CURLEC_TEST_MODE_USERNAME,
      password: process.env.CURLEC_TEST_MODE_PASSWORD,
      storagePath: StorageStatePath.CURLEC_TEST_LOGIN_STATE,
    },
  ];
};

module.exports = {
  routes,
  getEmailCredentials,
  getMobileCredentials,
  getActivatedNotIECredentials,
  getMagicCheckoutCredentials,
  getPosCredentials,
  getCurlecCredentials,
  getStorageStatePath,
};
