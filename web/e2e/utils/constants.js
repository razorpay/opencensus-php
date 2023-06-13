const BASE_PATH = './e2e/storageState';

const StorageStatePath = {
  EMAIL_TEST_LOGIN_STATE: `${BASE_PATH}/desktop-test-mode-login.json`,
  EMAIL_LIVE_LOGIN_STATE: `${BASE_PATH}/desktop-live-mode-login.json`,
  TRANSACTIONS_LOGIN_STATE: `${BASE_PATH}/desktop-transactions-login.json`,
  MOBILE_TEST_LOGIN_STATE: `${BASE_PATH}/mobile-test-mode-login.json`,
  MOBILE_LIVE_LOGIN_STATE: `${BASE_PATH}/mobile-live-mode-login.json`,
  ACTIVATED_NOT_IE_STATE: `${BASE_PATH}/activated-not-ie-login.json`,
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
};

const EmailCredentials = [
  {
    type: 'desktop-test-mode-login.json',
    username: process.env.EMAIL_TEST_MODE_USERNAME,
    password: process.env.EMAIL_TEST_MODE_PASSWORD,
    storagePath: StorageStatePath.EMAIL_TEST_LOGIN_STATE,
  },
  {
    type: 'desktop-live-mode-login.json',
    username: process.env.EMAIL_LIVE_MODE_USERNAME,
    password: process.env.EMAIL_LIVE_MODE_PASSWORD,
    storagePath: StorageStatePath.EMAIL_LIVE_LOGIN_STATE,
  },
  {
    type: 'desktop-transactions-login.json',
    username: process.env.EMAIL_TRANSACTIONS_USERNAME,
    password: process.env.EMAIL_TRANSACTIONS_PASSWORD,
    storagePath: StorageStatePath.TRANSACTIONS_LOGIN_STATE,
  },
];

const MobileCredentials = [
  {
    type: 'mobile-test-mode-login.json',
    username: process.env.MOBILE_TEST_MODE_USERNAME,
    password: process.env.MOBILE_TEST_MODE_PASSWORD,
    mobile: process.env.MOBILE_TEST_MODE_MOBILE,
    storagePath: StorageStatePath.MOBILE_TEST_LOGIN_STATE,
  },
  {
    type: 'mobile-live-mode-login.json',
    username: process.env.MOBILE_LIVE_MODE_USERNAME,
    password: process.env.MOBILE_LIVE_MODE_PASSWORD,
    mobile: process.env.MOBILE_LIVE_MODE_MOBILE,
    storagePath: StorageStatePath.MOBILE_LIVE_LOGIN_STATE,
  },
];

const ActivatedNotIECredentials = [
  {
    type: 'activated-not-ie-login.json',
    username: process.env.ACTIVATED_NOT_IE_USERNAME,
    password: process.env.ACTIVATED_NOT_IE_PASSWORD,
    storagePath: StorageStatePath.ACTIVATED_NOT_IE_STATE,
  },
];

module.exports = {
  routes,
  EmailCredentials,
  MobileCredentials,
  ActivatedNotIECredentials,
  StorageStatePath,
};
