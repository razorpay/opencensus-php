const MERCHANT_DASHBOARD_PROD = 'dashboard.razorpay.com';
const MERCHANT_DASHBOARD_BETA = 'beta-dashboard.stage.razorpay.in';
const MERCHANT_DASHBOARD_DEV = 'dashboard.dev.razorpay.in';
const LOCALHOST = 'localhost';

const isProdMerchantDashboard = window.location.hostname === MERCHANT_DASHBOARD_PROD;
const isBetaMerchantDashboard =
  window.location.hostname === MERCHANT_DASHBOARD_BETA ||
  window.location.hostname === MERCHANT_DASHBOARD_DEV ||
  window.location.hostname.includes('razorpay.in');
const isLocalhost = window.location.hostname === LOCALHOST;

// Enabled for merchant dashboard. Disabled for bank URLs
export const isCaptchaV3Enabled = () =>
  isProdMerchantDashboard || isBetaMerchantDashboard || isLocalhost;

export const getCaptchaVariant = () => (isCaptchaV3Enabled() ? 2 : 1);
