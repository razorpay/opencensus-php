const BASE_PATH = './e2e/storageState';
const { default: ENV } = require('./env');

const StorageStatePath = {
  EMAIL_TEST_LOGIN_STATE: `${BASE_PATH}/desktop-test-mode-login.json`,
  EMAIL_LIVE_LOGIN_STATE: `${BASE_PATH}/desktop-live-mode-login.json`,
  TRANSACTIONS_LOGIN_STATE: `${BASE_PATH}/desktop-transactions-login.json`,
  SETTLEMENTS_LOGIN_STATE: `${BASE_PATH}/desktop-settlement-login.json`,
  INTERNATIONAL_LOGIN_STATE: `${BASE_PATH}/desktop-international-login.json`,
  MOBILE_TEST_LOGIN_STATE: `${BASE_PATH}/mobile-test-mode-login.json`,
  MOBILE_LIVE_LOGIN_STATE: `${BASE_PATH}/mobile-live-mode-login.json`,
  ACTIVATED_NOT_IE_STATE: `${BASE_PATH}/activated-not-ie-login.json`,
  CAPITAL_RESELLER_PARTNER_TEST_LOGIN_STATE: `${BASE_PATH}/capital-reseller-partner-desktop-test-mode-login.json`,
  MAGIC_CHECKOUT_STATE: `${BASE_PATH}/magic-checkout.json`,
  RESELLER_PARTNER_TEST_LOGIN_STATE: `${BASE_PATH}/reseller-partner-desktop-test-mode-login.json`,
  AGGREGATOR_PARTNER_TEST_LOGIN_STATE: `${BASE_PATH}/aggregator-partner-desktop-test-mode-login.json`,
  PLATFORM_PARTNER_TEST_LOGIN_STATE: `${BASE_PATH}/platform-partner-desktop-test-mode-login.json`,
  OPTIMIZER_LOGIN_STATE: `${BASE_PATH}/desktop-optimizer-login.json`,
  OPTIMIZER_V1_LOGIN_STATE: `${BASE_PATH}/desktop-optimizer-v1-login.json`,
};

const routes = {
  SIGN_IN_PATH: '/?screen=sign_in&isTestEnv=true',
  DASHBOARD: '/app/dashboard',
  ACCOUNT_SETTINGS: '/app/account-settings',
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
  PAYMENTS: 'app/payments',
  DISPUTES: 'app/disputes',
  FAILED_PAYMENTS: 'app/failed-payments',
  ORDERS: 'app/orders',
  REFUNDS: 'app/refunds',
  BATCH_REFUNDS: 'app/refunds/batchuploads',
  BATCH_REFUNDS_UPLOAD: 'app/refunds/batchupload',
  SUCCESS_RATE: 'app/success-rate',
  PAYMENT_PAGES: 'app/paymentpages',
  MAGIC_CHECKOUT: 'app/magic/settings',
  FIRS: 'app/international-settings/firs',
  PARTNER_DASHBOARD: '/app/partners',
  PARTNER_PLAYBOOK: '/app/partners/playbook',
  PARTNER_PRICING_PLANS: '/app/partner-pricing-plans',
  AFFILIATE_ACCOUNTS: '/app/partners/submerchants',
  AFFILIATE_ACCOUNTS_CAPITAL: '/app/partners/submerchants/capital',
  OPTIMIZER: 'app/optimizer/rules',
  PAYMENT_METRICS: '/app/payment-metrics',
};

const EmailCredentials = [
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
];

const MobileCredentials = [
  {
    type: 'mobile-test-mode-login.json',
    username: ENV.MOBILE_TEST_MODE_USERNAME,
    password: ENV.MOBILE_TEST_MODE_PASSWORD,
    mobile: ENV.MOBILE_TEST_MODE_MOBILE,
    storagePath: StorageStatePath.MOBILE_TEST_LOGIN_STATE,
  },
  {
    type: 'mobile-live-mode-login.json',
    username: ENV.MOBILE_LIVE_MODE_USERNAME,
    password: ENV.MOBILE_LIVE_MODE_PASSWORD,
    mobile: ENV.MOBILE_LIVE_MODE_MOBILE,
    storagePath: StorageStatePath.MOBILE_LIVE_LOGIN_STATE,
  },
];

const ActivatedNotIECredentials = [
  {
    type: 'activated-not-ie-login.json',
    username: ENV.ACTIVATED_NOT_IE_USERNAME,
    password: ENV.ACTIVATED_NOT_IE_PASSWORD,
    storagePath: StorageStatePath.ACTIVATED_NOT_IE_STATE,
  },
];

const MagicCheckoutCredentials = [
  {
    type: 'magic-checkout.json',
    username: ENV.MAGIC_CHECKOUT_USERNAME,
    password: ENV.MAGIC_CHECKOUT_PASSWORD,
    storagePath: StorageStatePath.MAGIC_CHECKOUT_STATE,
  },
];

module.exports = {
  routes,
  EmailCredentials,
  MobileCredentials,
  ActivatedNotIECredentials,
  MagicCheckoutCredentials,
  StorageStatePath,
};
