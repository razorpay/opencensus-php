import path from 'path';
import { DASHBOARD_ROOT } from '@libs/shared-core';

const authCredsCacheDir = path.resolve(
  DASHBOARD_ROOT,
  'node_modules/.dashboard-core/cache/playwright/.auth',
);

const BASE_PATH = path.resolve(process.cwd(), authCredsCacheDir);

export const playwrightEnvs = {
  ACTIVATED_RZP_MERCHANT_USERNAME: process.env.ACTIVATED_RZP_MERCHANT_USERNAME,
  ACTIVATED_RZP_MERCHANT_PASSWORD: process.env.ACTIVATED_RZP_MERCHANT_PASSWORD,
  EMAIL_SETTLEMENT_USERNAME: process.env.EMAIL_SETTLEMENT_USERNAME,
  EMAIL_SETTLEMENT_PASSWORD: process.env.EMAIL_SETTLEMENT_PASSWORD,
  ACTIVATED_NOT_IE_USERNAME: process.env.ACTIVATED_NOT_IE_USERNAME,
  ACTIVATED_NOT_IE_PASSWORD: process.env.ACTIVATED_NOT_IE_PASSWORD,
  EASY_ONBOARDING_USERNAME: process.env.EASY_ONBOARDING_USERNAME,
  EASY_ONBOARDING_PASSWORD: process.env.EASY_ONBOARDING_PASSWORD,
  EASY_ONBOARDING_MERCHANT_ID: process.env.EASY_ONBOARDING_MERCHANT_ID,
  EASY_ONBOARDING_RZP_USER_ID: process.env.EASY_ONBOARDING_RZP_USER_ID,
  EASY_ONBOARDING_FTUX_USERNAME: process.env.EASY_ONBOARDING_FTUX_USERNAME,
  EASY_ONBOARDING_FTUX_PASSWORD: process.env.EASY_ONBOARDING_FTUX_PASSWORD,
  EASY_ONBOARDING_P2PM_USERNAME: process.env.EASY_ONBOARDING_P2PM_USERNAME,
  EASY_ONBOARDING_P2PM_PASSWORD: process.env.EASY_ONBOARDING_P2PM_PASSWORD,
  CAPITAL_RESELLER_PARTNER_TEST_MODE_USERNAME:
    process.env.CAPITAL_RESELLER_PARTNER_TEST_MODE_USERNAME,
  CAPITAL_RESELLER_PARTNER_TEST_MODE_PASSWORD:
    process.env.CAPITAL_RESELLER_PARTNER_TEST_MODE_PASSWORD,
  RESELLER_PARTNER_TEST_MODE_USERNAME: process.env.RESELLER_PARTNER_TEST_MODE_USERNAME,
  RESELLER_PARTNER_TEST_MODE_PASSWORD: process.env.RESELLER_PARTNER_TEST_MODE_PASSWORD,
  RESELLER_PARTNER_POS_TEST_MODE_USERNAME: process.env.RESELLER_PARTNER_POS_TEST_MODE_USERNAME,
  RESELLER_PARTNER_POS_TEST_MODE_PASSWORD: process.env.RESELLER_PARTNER_POS_TEST_MODE_PASSWORD,
  RESELLER_PARTNER_AGENT_TEST_MODE_USERNAME: process.env.RESELLER_PARTNER_AGENT_TEST_MODE_USERNAME,
  RESELLER_PARTNER_AGENT_TEST_MODE_PASSWORD: process.env.RESELLER_PARTNER_AGENT_TEST_MODE_PASSWORD,
  AGGREGATOR_PARTNER_TEST_MODE_USERNAME: process.env.AGGREGATOR_PARTNER_TEST_MODE_USERNAME,
  AGGREGATOR_PARTNER_TEST_MODE_PASSWORD: process.env.AGGREGATOR_PARTNER_TEST_MODE_PASSWORD,
  PLATFORM_PARTNER_TEST_MODE_USERNAME: process.env.PLATFORM_PARTNER_TEST_MODE_USERNAME,
  PLATFORM_PARTNER_TEST_MODE_PASSWORD: process.env.PLATFORM_PARTNER_TEST_MODE_PASSWORD,
  POS_USERNAME: process.env.POS_USERNAME,
  POS_PASSWORD: process.env.POS_PASSWORD,
  POS_ORDER_DETAILS_USERNAME: process.env.POS_ORDER_DETAILS_USERNAME,
  POS_ORDER_DETAILS_PASSWORD: process.env.POS_ORDER_DETAILS_PASSWORD,
  EMAIL_OPTIMIZER_V1_USERNAME: process.env.EMAIL_OPTIMIZER_V1_USERNAME,
  EMAIL_OPTIMIZER_V1_PASSWORD: process.env.EMAIL_OPTIMIZER_V1_PASSWORD,
  MOBILE_TEST_MODE_POS_KYC_STATUS_NC: process.env.MOBILE_TEST_MODE_POS_KYC_STATUS_NC,
  POS_SALES_AGENT_USERNAME: process.env.POS_SALES_AGENT_USERNAME,
  POS_SALES_AGENT_PASSWORD: process.env.POS_SALES_AGENT_PASSWORD,
  E2E_SR_LUMBERJACK_KEY: process.env.LUMBERJACK_KEY,

  TEST_ENV: process.env.TEST_ENV ?? 'devstack',
};

// TODO: consume this from shared-utils in future
export const getStorageStatePath = (targetMode?: 'live' | 'test') => {
  const mode = targetMode ?? 'live';
  const STORAGE_PATH_MAPPING: Record<string, string> = {
    ACTIVATED_NOT_IE_STATE: `${BASE_PATH}/activated-not-ie-login.json`,
    ACTIVATED_RZP_MERCHANT: `${BASE_PATH}/activated-rzp-merchant.json`,
    AGGREGATOR_PARTNER_TEST_LOGIN_STATE: `${BASE_PATH}/aggregator-partner-desktop-test-mode-login.json`,
    CAPITAL_RESELLER_PARTNER_TEST_LOGIN_STATE: `${BASE_PATH}/capital-reseller-partner-desktop-test-mode-login.json`,
    CURLEC_TEST_CAW_LOGIN_STATE: `${BASE_PATH}/curlec-test-mode-caw-login.json`,
    CURLEC_TEST_LOGIN_STATE: `${BASE_PATH}/curlec-test-mode-login.json`,
    OPTIMIZER_V1_LOGIN_STATE: `${BASE_PATH}/desktop-optimizer-v1-login.json`,
    PLATFORM_PARTNER_TEST_LOGIN_STATE: `${BASE_PATH}/platform-partner-desktop-test-mode-login.json`,
    POS_KYC_STATUS_NC: `${BASE_PATH}/pos-kyc-status-nc.json`,
    POS_LOGIN_STATE: `${BASE_PATH}/pos-login.json`,
    POS_ORDER_DETAILS_LOGIN_STATE: `${BASE_PATH}/pos-login-order-details.json`,
    RESELLER_PARTNER_AGENT_TEST_LOGIN_STATE: `${BASE_PATH}/reseller-partner-agent-desktop-test-mode-login.json`,
    RESELLER_PARTNER_POS_TEST_LOGIN_STATE: `${BASE_PATH}/reseller-partner-pos-desktop-test-mode-login.json`,
    RESELLER_PARTNER_TEST_LOGIN_STATE: `${BASE_PATH}/reseller-partner-desktop-test-mode-login.json`,
    SETTLEMENTS_LOGIN_STATE: `${BASE_PATH}/desktop-settlement-login.json`,
    POS_SALES_AGENT: `${BASE_PATH}/pos-sales-agent.json`,
  };

  const isTestMode = mode === 'test';

  const updatedStoragePathMapping = Object.keys(STORAGE_PATH_MAPPING).reduce<
    Record<string, string>
  >((acc, key) => {
    acc[key] = isTestMode
      ? STORAGE_PATH_MAPPING[key].replace('.json', '-test-mode.json')
      : STORAGE_PATH_MAPPING[key];
    return acc;
  }, {});

  return updatedStoragePathMapping;
};

export const getEmailCredentials = () => {
  const StorageStatePath = getStorageStatePath();
  return [
    {
      type: 'activated-rzp-merchant.json',
      username: playwrightEnvs.ACTIVATED_RZP_MERCHANT_USERNAME,
      password: playwrightEnvs.ACTIVATED_RZP_MERCHANT_PASSWORD,
      storagePath: StorageStatePath.ACTIVATED_RZP_MERCHANT,
      hasTestMode: true,
    },
    {
      type: 'desktop-settlement-login.json',
      username: playwrightEnvs.EMAIL_SETTLEMENT_USERNAME,
      password: playwrightEnvs.EMAIL_SETTLEMENT_PASSWORD,
      storagePath: StorageStatePath.SETTLEMENTS_LOGIN_STATE,
    },
    {
      type: 'capital-reseller-partner-desktop-test-mode-login.json',
      username: playwrightEnvs.CAPITAL_RESELLER_PARTNER_TEST_MODE_USERNAME,
      password: playwrightEnvs.CAPITAL_RESELLER_PARTNER_TEST_MODE_PASSWORD,
      storagePath: StorageStatePath.CAPITAL_RESELLER_PARTNER_TEST_LOGIN_STATE,
    },
    {
      type: 'reseller-partner-desktop-test-mode-login.json',
      username: playwrightEnvs.RESELLER_PARTNER_TEST_MODE_USERNAME,
      password: playwrightEnvs.RESELLER_PARTNER_TEST_MODE_PASSWORD,
      storagePath: StorageStatePath.RESELLER_PARTNER_TEST_LOGIN_STATE,
    },
    {
      type: 'reseller-partner-pos-desktop-test-mode-login.json',
      username: playwrightEnvs.RESELLER_PARTNER_POS_TEST_MODE_USERNAME,
      password: playwrightEnvs.RESELLER_PARTNER_POS_TEST_MODE_PASSWORD,
      storagePath: StorageStatePath.RESELLER_PARTNER_POS_TEST_LOGIN_STATE,
    },
    {
      type: 'reseller-partner-agent-desktop-test-mode-login.json',
      username: playwrightEnvs.RESELLER_PARTNER_AGENT_TEST_MODE_USERNAME,
      password: playwrightEnvs.RESELLER_PARTNER_AGENT_TEST_MODE_PASSWORD,
      storagePath: StorageStatePath.RESELLER_PARTNER_AGENT_TEST_LOGIN_STATE,
    },
    {
      type: 'aggregator-partner-desktop-test-mode-login.json',
      username: playwrightEnvs.AGGREGATOR_PARTNER_TEST_MODE_USERNAME,
      password: playwrightEnvs.AGGREGATOR_PARTNER_TEST_MODE_PASSWORD,
      storagePath: StorageStatePath.AGGREGATOR_PARTNER_TEST_LOGIN_STATE,
    },
    {
      type: 'platform-partner-desktop-test-mode-login.json',
      username: playwrightEnvs.PLATFORM_PARTNER_TEST_MODE_USERNAME,
      password: playwrightEnvs.PLATFORM_PARTNER_TEST_MODE_PASSWORD,
      storagePath: StorageStatePath.PLATFORM_PARTNER_TEST_LOGIN_STATE,
    },
    {
      type: 'desktop-optimizer-v1-login.json',
      username: playwrightEnvs.EMAIL_OPTIMIZER_V1_USERNAME,
      password: playwrightEnvs.EMAIL_OPTIMIZER_V1_PASSWORD,
      storagePath: StorageStatePath.OPTIMIZER_V1_LOGIN_STATE,
      hasTestMode: true,
    },
  ];
};

export const getActivatedNotIECredentials = () => {
  const StorageStatePath = getStorageStatePath();
  return [
    {
      type: 'activated-not-ie-login.json',
      username: playwrightEnvs.ACTIVATED_NOT_IE_USERNAME,
      password: playwrightEnvs.ACTIVATED_NOT_IE_PASSWORD,
      storagePath: StorageStatePath.ACTIVATED_NOT_IE_STATE,
      hasTestMode: true,
    },
  ];
};

export const getPosCredentials = () => {
  const StorageStatePath = getStorageStatePath();
  return [
    {
      type: 'pos-login.json',
      username: playwrightEnvs.POS_USERNAME,
      password: playwrightEnvs.POS_PASSWORD,
      storagePath: StorageStatePath.POS_LOGIN_STATE,
    },
    {
      type: 'pos-login-order-details.json',
      username: playwrightEnvs.POS_ORDER_DETAILS_USERNAME,
      password: playwrightEnvs.POS_ORDER_DETAILS_PASSWORD,
      storagePath: StorageStatePath.POS_ORDER_DETAILS_LOGIN_STATE,
      hasTestMode: true,
    },
    {
      type: 'pos-sales-agent.json',
      username: playwrightEnvs.POS_SALES_AGENT_USERNAME,
      password: playwrightEnvs.POS_SALES_AGENT_PASSWORD,
      storagePath: StorageStatePath.POS_SALES_AGENT,
    },
  ];
};

export const getMobileCredentials = () => {
  const StorageStatePath = getStorageStatePath();
  return [
    {
      type: 'pos-kyc-status-nc.json',
      mobile: playwrightEnvs.MOBILE_TEST_MODE_POS_KYC_STATUS_NC,
      storagePath: StorageStatePath.POS_KYC_STATUS_NC,
    },
  ];
};

export const getCurlecCredentials = () => {
  const StorageStatePath = getStorageStatePath();
  return [
    {
      type: 'curlec-merchant-desktop-test-mode-login.json',
      username: process.env.CURLEC_TEST_MODE_USERNAME,
      password: process.env.CURLEC_TEST_MODE_PASSWORD,
      storagePath: StorageStatePath.CURLEC_TEST_LOGIN_STATE,
    },
    {
      type: 'curlec-merchant-desktop-test-mode-caw-login.json',
      username: process.env.CURLEC_CAW_TEST_MODE_USERNAME,
      password: process.env.CURLEC_CAW_TEST_MODE_PASSWORD,
      storagePath: StorageStatePath.CURLEC_TEST_CAW_LOGIN_STATE,
    },
  ];
};

export const getCredentials = () => {
  return {
    emailCred: getEmailCredentials(),
    mobileCred: getMobileCredentials(),
    activatedNotIe: getActivatedNotIECredentials(),
    posCredentials: getPosCredentials(),
    curlecCred: getCurlecCredentials(),
  };
};

export const routes: Record<string, string> = {
  SIGN_IN_PATH: '/?screen=sign_in&isTestEnv=true',
  DASHBOARD: '/app/dashboard',
  ACCOUNT_SETTINGS: '/app/account-settings',
  STREAKS_REWARDS: '/app/streak-reward',
  BALANCES: '/app/payments-and-refunds-settings/balances',
  CREDITS: '/app/payments-and-refunds-settings/credits',
  REMINDERS: '/app/payments-and-refunds-settings/reminders',
  CAPTURE_AND_REFUND_SETTINGS: '/app/payments-and-refunds-settings/capture-refund-settings',
  TRANSACTION_LIMITS: '/app/payments-and-refunds-settings/transaction-limits',
  MANAGE_TEAM: '/app/business-settings/team',
  SETTLEMENTS: '/app/settlements',
  GST_DETAILS: '/app/business-settings/gst',
  SMS_NOTIFICATIONS: '/app/notification-settings/sms',
  API_KEYS: '/app/website-app-settings/api-keys',
  WEBHOOKS: '/app/website-app-settings/webhooks',
  CUSTOMERS: '/app/customers',
  CUSTOMER_SUPPORT_DETAILS: '/app/business-settings/customer-support',
  BUSINESS_DETAILS: '/app/business-settings/business',
  PAYMENT_LINKS: '/app/paymentlinks',
  ROUTE_PAYMENTS: '/app/route/payments',
  TRANSACTIONS_PAYMENTS: '/app/payments',
  ITEMS: '/app/items',
  INVOICES: '/app/invoices',
  PAYMENT_BUTTONS: '/app/paymentbuttons',
  PAYMENTS: '/app/payments',
  DISPUTES: 'app/disputes',
  FAILED_PAYMENTS: 'app/failed-payments',
  ORDERS: 'app/orders',
  REFUNDS: 'app/refunds',
  BATCH_REFUNDS: 'app/refunds/batchuploads',
  BATCH_REFUNDS_UPLOAD: 'app/refunds/batchupload',
  SUCCESS_RATE: 'app/success-rate',
  PAYMENT_PAGES: '/app/paymentpages',
  MAGIC_CHECKOUT: '/app/magic/settings',
  FIRS: 'app/international-settings/firs',
  PARTNER_DASHBOARD: '/app/partners',
  PARTNER_PLAYBOOK: '/app/partners/playbook',
  PARTNER_PRICING_PLANS: '/app/partner-pricing-plans',
  CLIENT_ACCOUNTS: '/app/partners/submerchants',
  CLIENT_ACCOUNTS_CAPITAL: '/app/partners/submerchants/capital',
  CLIENT_ACCOUNTS_POS: '/app/partners/submerchants/pos',
  AFFILIATE_ACCOUNTS: '/app/partners/submerchants',
  AFFILIATE_ACCOUNTS_CAPITAL: '/app/partners/submerchants/capital',
  OPTIMIZER: '/app/optimizer/rules',
  POS: '/app/pos',
  BATCH_PAYMENT_PAGES: '/app/paymentpages/batchpaymentpages',
  WHATSAPP_ACCOUNT_SETUP: '/app/payments-and-refunds-settings/whatsapp-account-setup',
  PAYMENT_METRICS: '/app/payment-metrics',
  INTERNATIONAL_PAYMENTS: '/app/payment-methods/international-payments',
  WALLET_REPORTS: '/app/wallet/reports',
  GCMS_PROGRAMS: '/app/gcms/programs',
  GCMS_FUNDS_RESELLER_ACCOUNTS: '/app/gcms/funds/reseller-account',
  GCMS_BRAND_TRANSACTIONS: '/app/gcms/funds/brand-account',
  GCMS_ORDERS: '/app/gcms/orders',
  GCMS_ORDERS_CREATE: '/app/gcms/orders/create',
  GCMS_RESELLERS: '/app/gcms/resellers',
  GCMS_REPORTS: '/app/gcms/reports',
  APP_STORE: '/app/app-store',
  RIZE_MARKETPLACE: '/app/rize-marketplace',
  SUBSCRIPTIONS: '/app/subscriptions',
  SUBSCRIPTIONS_SETTINGS: '/app/subscriptions/settings',
  RISK_AND_FRAUD: '/app/risk-and-fraud',
  RECON_DASHBOARD: '/app/reconciliations/dashboard',
  ASSISTED_FINANCING: '/app/assisted-financing',
  SMART_COLLECT: '/app/smartcollect/virtualaccounts',
  NEW_REGISTRATION_LINKS: '/app/registration_links/new',
  OFFERS_HOME: '/app/offers',
};
