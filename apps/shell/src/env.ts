if (!__STAGE__) {
  throw new Error('Env variable not set - STAGE');
}

// ENVS
export const STAGE = __STAGE__;
export const IS_PRODUCTION = ['production', 'canary'].includes(STAGE);
export const UNIVERSE_PUBLIC_ASSETS_URL = process.env['UNIVERSE_PUBLIC_ASSETS_URL'];
export const SERVER_PORT = process.env['SERVER_PORT'];
export const PHP_BASE_URL = process.env['PHP_BASE_URL'] ?? '';
export const COOKIE_ENCRYPTION_SECRET = process.env['COOKIE_ENCRYPTION_SECRET'] ?? '';
export const INTERNAL_PHP_ENDPOINT = process.env['INTERNAL_PHP_ENDPOINT'];
export const SPLITZ_INTERNAL_API_ENDPOINT = process.env['SPLITZ_INTERNAL_API_ENDPOINT'];
export const ADMIN_DASHBOARD_INTERNAL_URL = process.env['ADMIN_DASHBOARD_INTERNAL_URL'];

// From BE
export const BANKING_SERVICE_URL = process.env['BANKING_SERVICE_URL'];
/**
 * @deprecated
 * @description Only used for AB related flows. Forwarding it to window as well. Should not be used in shell.
 * @description Use `STAGE` instead. For STAGE==="devstack", `APP_ENV` will be stage.
 */
export const APP_ENV = process.env['APP_ENV'];

// Injected via CI
export const DASHBOARD_MICROAPPS = process.env['DASHBOARD_MICROAPPS'];

// To be used in window obj
export const API_URL = process.env['API_URL'];
export const CHECKOUT_API_URL = process.env['CHECKOUT_API_URL'];
export const LUMBERJACK_API_URL = process.env['LUMBERJACK_API_URL'];
export const SEGMENT_API_KEY = process.env['SEGMENT_API_KEY'];
export const WEBSITE_SEGMENT_API_KEY = process.env['WEBSITE_SEGMENT_API_KEY'];
export const X_WEBSITE_SEGMENT_API_KEY = process.env['X_WEBSITE_SEGMENT_API_KEY'];
export const SHIELD_STAGE = process.env['SHIELD_STAGE'];
export const CDN_DASHBOARD_URL = process.env['CDN_DASHBOARD_URL'];
export const CDN_BASE_URL = process.env['CDN_BASE_URL'];
export const INVISIBLE_CAPTCHA_SITE_KEY = process.env['INVISIBLE_CAPTCHA_SITE_KEY'];
export const CHECKBOX_CAPTCHA_SITE_KEY = process.env['CHECKBOX_CAPTCHA_SITE_KEY'];
export const RECAPTCHA_V3_SITE_KEY = process.env['RECAPTCHA_V3_SITE_KEY'];
export const STREAKS_REWARDS = process.env['STREAKS_REWARDS'];
export const REFINER_PROJECT_ID = process.env['REFINER_PROJECT_ID'];
export const EASY_ONBOARDING_URL = process.env['EASY_ONBOARDING_URL'];
export const PP_ECOMMERCE_URL = process.env['PP_ECOMMERCE_URL'];
export const BANK_DETAILS_URL = process.env['BANK_DETAILS_URL'];
export const APP_NAME = process.env['APP_NAME'];
export const LUMBERJACK_METRICS_API_URL = process.env['LUMBERJACK_METRICS_API_URL'];
export const RZP_WEBSITE_URL = process.env['RZP_WEBSITE_URL'];
export const INSTANCE_TYPE = process.env['INSTANCE_TYPE'] as string;
export const PUBLIC_API_URL = process.env['PUBLIC_API_URL'];

/**
 * @description this comes from vault, its needed only for logging out. This is injected by PHP in window for newAuth flow.
 * @todo Check if this can be owned by newAuth/usl service, maybe set in cookies and consumed by it on the client side whenever needed.
 */
export const OAUTH_CLIENT_ID = process.env['MERCHANT_OAUTH_CLIENT_ID'];
export const LUMBERJACK_API_KEY = process.env['LJ_KEY'];
export const CDN_DASHBOARD_ASSETS_URL = process.env['CDN_DASHBOARD_ASSETS_URL'];
export const RAZORPAY_WEBSITE = process.env['RZP_WEBSITE_URL'];
export const BANK_LMS_BANKING_SERVICE_URL = process.env['BANK_LMS_BANKING_SERVICE_URL'];
export const SPLITZ_INTERNAL_AUTH_TOKEN = process.env['SPLITZ_AUTH_DASHBOARDSHELLPASSWORD'];
export const CURLEC_LINKED_ACCOUNT_ONBOARDING_URL =
  process.env['CURLEC_LINKED_ACCOUNT_ONBOARDING_URL'];
export const INSIGHT_X_SUPERSET_URL = process.env['INSIGHT_X_SUPERSET_URL'];
export const INSIGHT_X_SUPERSET_OVERVIEW_ID = process.env['INSIGHT_X_SUPERSET_OVERVIEW_ID'];
export const INSIGHT_X_SUPERSET_UPI_ID = process.env['INSIGHT_X_SUPERSET_UPI_ID'];
export const INSIGHT_X_SUPERSET_CARDS_ID = process.env['INSIGHT_X_SUPERSET_CARDS_ID'];
export const INSIGHT_X_SUPERSET_NETBANKING_ID = process.env['INSIGHT_X_SUPERSET_NETBANKING_ID'];
export const INSIGHT_X_SUPERSET_WALLETS_ID = process.env['INSIGHT_X_SUPERSET_WALLETS_ID'];

// Available only in development
export const LOCAL_DEV_REMOTES = process.env['LOCAL_DEV_REMOTES'];
