export const FACEBOOK_EVENT_CONFIGS = {
  checkout_initiated: true,
  add_payment_info: false,
  purchase: false,
  custom_events: false,
};

export const GOOGLE_ADS_EVENT_CONFIGS = {
  purchase: false,
};

export const GOOGLE_ANALYTICS_EVENT_CONFIGS = {
  checkout_initiated: true,
  add_payment_info: false,
  add_shipping_info: false,
  purchase: false,
  custom_events: false,
};

export const FB_CONFIGS = {
  ga4: {
    analytics_accounts: [],
    events: {},
  },
  google_ads: {
    analytics_accounts: [],
    events: {},
  },
  fb: {
    analytics_accounts: [
      {
        id: '1352542',
        analytics_platform_user_id: '12345',
        integration_method: 'backend',
        api_secret: window.btoa('its a secret'),
      },
    ],
    events: {
      purchase: false,
      checkout_initiated: false,
      add_payment_info: false,
      custom_events: false,
    },
  },
};

export const DEFAULT_CONFIGS = {
  ga4: {
    analytics_accounts: [],
    events: GOOGLE_ANALYTICS_EVENT_CONFIGS,
  },
  google_ads: {
    analytics_accounts: [],
    events: GOOGLE_ADS_EVENT_CONFIGS,
  },
  fb: {
    analytics_accounts: [],
    events: FACEBOOK_EVENT_CONFIGS,
  },
};

export const OAUTH_CONFIGS = {
  outh_id: {
    email: 'test@gmail.com',
    picture: 'https://test-img.com',
    google_ads_ids: ['test123', 'test456'],
  },
};

export const GOOGLE_ADS_CONFIGS = {
  oauth_accounts: OAUTH_CONFIGS,
  ga4: {
    analytics_accounts: [],
    events: {},
  },
  google_ads: {
    analytics_accounts: [
      {
        id: '1352540',
        analytics_platform_user_id: '12456464',
        integration_method: 'backend',
        google_ads_conversion_label: window.btoa('its a secret'),
        google_ads_conversion_id: 'test123',
      },
    ],
    events: {
      purchase: true,
    },
  },
  fb: {
    analytics_accounts: [],
    events: {},
  },
};

export const GOOGLE_ADS_AUTH_CONFIG = {
  oauth_accounts: OAUTH_CONFIGS,
  ga4: {
    analytics_accounts: [],
    events: {},
  },
  google_ads: {
    analytics_accounts: [],
    events: {},
  },
  fb: {
    analytics_accounts: [],
    events: {},
  },
};

export const GOOGLE_ANALYTICS_CONFIGS = {
  ga4: {
    analytics_accounts: [
      {
        id: '1352543',
        analytics_platform_user_id: '12343',
        api_secret: window.btoa('test secret'),
        integration_method: 'backend',
      },
      {
        id: '1358755',
        analytics_platform_user_id: '12943',
        api_secret: window.btoa('its a secret'),
        integration_method: 'backend',
      },
    ],
    events: {
      purchase: false,
      checkout_initiated: false,
      add_shipping_info: false,
      add_payment_info: false,
      custom_events: false,
    },
  },
  google_ads: {
    analytics_accounts: [],
    events: {},
  },
  fb: {
    analytics_accounts: [],
    events: {},
  },
};
