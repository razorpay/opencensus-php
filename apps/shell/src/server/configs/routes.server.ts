export const SHELL_SERVER_ROUTES = {
  STATIC_ASSETS: '/static',
  APP_HEALTH: '/app-health',
  SERVICE_WORKER: '/service-worker.m?js',
  DEFAULT_ASSETS: '/build',
  PROM_METRICS: '/metrics',
  APP_VERSIONS: '/app-versions',
  APP_INTERNAL_PROXY: '/app-internal-proxy',
  FRONTEND_APPS: '/app/*',
  STRICT_FRONTEND_APPS: '/app',
  DEV_INDEX_ROUTE: '/',
};

export const SHELL_EXTERNAL_API_ROUTES = {
  USER: '/user',
  USER_OPTIMIZED:
    '/user?tags=0&merchant_details=0&features=0&experiments=0&splitz_experiments=0&payouts=0',
  NON_CACHED_USER_OPTIMIZED:
    '/user?tags=0&merchant_details=0&features=0&experiments=0&splitz_experiments=0&payouts=0&skip_cached_data=1',
  MERCHANT_DETAILS: '/merchant/details',
  MERCHANT_EXPERIMENTS: '/merchant/experiments',
  MERCHANT_PAYOUTS_LIVE: '/merchant/api/live/payouts?count=1&product=banking', // Not used by PG, kept for reference
  MERCHANT_PAYOUTS_TEST: '/merchant/api/test/payouts?count=1&product=banking', // Not used by PG, kept for reference
  MERCHANT_SPLITZ_EXPERIMENTS: '/merchant/splitzexperiments',
  MERCHANT_SPLITZ_EXPERIMENTS_V2: '/merchant/splitzexperimentsv2',
  MERCHANT_FEATURES: '/merchant/features',
  MERCHANT_TAGS: '/merchant/tags',
  ORG: '/org',
  SHELL_REDIRECT: '/shell/redirect',
  USER_SESSION: '/user/session',
  MERCHANT_CONFIG_STORE_LIVE_ONBOARDING:
    '/merchant/api/live/merchants/config/store?namespace=onboarding',
  MERCHANT_CONFIG_STORE_TEST_ONBOARDING:
    '/merchant/api/test/merchants/config/store?namespace=onboarding',
};

export const KNOWN_ROUTES = {
  ...SHELL_EXTERNAL_API_ROUTES,
  ...SHELL_SERVER_ROUTES,
};
