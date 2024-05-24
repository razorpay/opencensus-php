const BASE_PATH = './playwright/.auth';
const { default: getEnv } = require('../utils/env');

const getStorageStatePath = () => ({
  // NOTE: Please use ACTIVATED_RZP_MERCHANT for all the new tests unless you need to test a specific scenario which requires a different user.
  ACTIVATED_RZP_MERCHANT: `${BASE_PATH}/activated-rzp-merchant.json`,
  SETTLEMENTS_LOGIN_STATE: `${BASE_PATH}/desktop-settlement-login.json`,
  ACTIVATED_NOT_IE_STATE: `${BASE_PATH}/activated-not-ie-login.json`,
  CAPITAL_RESELLER_PARTNER_TEST_LOGIN_STATE: `${BASE_PATH}/capital-reseller-partner-desktop-test-mode-login.json`,
  RESELLER_PARTNER_TEST_LOGIN_STATE: `${BASE_PATH}/reseller-partner-desktop-test-mode-login.json`,
  RESELLER_PARTNER_POS_TEST_LOGIN_STATE: `${BASE_PATH}/reseller-partner-pos-desktop-test-mode-login.json`,
  RESELLER_PARTNER_AGENT_TEST_LOGIN_STATE: `${BASE_PATH}/reseller-partner-agent-desktop-test-mode-login.json`,
  AGGREGATOR_PARTNER_TEST_LOGIN_STATE: `${BASE_PATH}/aggregator-partner-desktop-test-mode-login.json`,
  PLATFORM_PARTNER_TEST_LOGIN_STATE: `${BASE_PATH}/platform-partner-desktop-test-mode-login.json`,
  POS_LOGIN_STATE: `${BASE_PATH}/pos-login.json`,
  POS_ORDER_DETAILS_LOGIN_STATE: `${BASE_PATH}/pos-login-order-details.json`,
  // POS_KYC_STATUS_NC: `${BASE_PATH}/pos-kyc-status-nc.json`,
  OPTIMIZER_V1_LOGIN_STATE: `${BASE_PATH}/desktop-optimizer-v1-login.json`,
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
      type: 'activated-rzp-merchant.json',
      username: ENV.ACTIVATED_RZP_MERCHANT_USERNAME,
      password: ENV.ACTIVATED_RZP_MERCHANT_PASSWORD,
      storagePath: StorageStatePath.ACTIVATED_RZP_MERCHANT,
    },
    {
      type: 'desktop-settlement-login.json',
      username: ENV.EMAIL_SETTLEMENT_USERNAME,
      password: ENV.EMAIL_SETTLEMENT_PASSWORD,
      storagePath: StorageStatePath.SETTLEMENTS_LOGIN_STATE,
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
      type: 'desktop-optimizer-v1-login.json',
      username: ENV.EMAIL_OPTIMIZER_V1_USERNAME,
      password: ENV.EMAIL_OPTIMIZER_V1_PASSWORD,
      storagePath: StorageStatePath.OPTIMIZER_V1_LOGIN_STATE,
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

const getMobileCredentials = () => {
  const ENV = getEnv();
  const StorageStatePath = getStorageStatePath(BASE_PATH);
  return [
    {
      type: 'pos-kyc-status-nc.json',
      mobile: ENV.MOBILE_TEST_MODE_POS_KYC_STATUS_NC,
      storagePath: StorageStatePath.POS_KYC_STATUS_NC,
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
  getPosCredentials,
  getCurlecCredentials,
  getStorageStatePath,
};
