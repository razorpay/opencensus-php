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
    accounts: [],
    events: {},
  },
  google_ads: {
    accounts: [],
    events: {},
  },
  fb: {
    accounts: [
      {
        id: '1352542',
        platform_user_id: '12345',
        integration_method: 'backend',
        access_token: window.btoa('its a secret'),
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
  google: {
    accounts: [],
  },
  ga4: {
    accounts: [],
    events: GOOGLE_ANALYTICS_EVENT_CONFIGS,
  },
  google_ads: {
    accounts: [],
    events: GOOGLE_ADS_EVENT_CONFIGS,
  },
  fb: {
    accounts: [],
    events: FACEBOOK_EVENT_CONFIGS,
  },
};

export const OAUTH_CONFIGS = {
  accounts: [
    {
      email: 'test@gmail.com',
      picture: 'https://test-img.com',
      google_ads_ids: ['test123', 'test456'],
      id: 'test_google_uuid',
    },
  ],
};

export const GOOGLE_ADS_CONFIGS = {
  google: OAUTH_CONFIGS,
  ga4: {
    accounts: [],
    events: {},
  },
  google_ads: {
    accounts: [
      {
        id: '1352540',
        platform_user_id: '12456464',
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
    accounts: [],
    events: {},
  },
};

export const GOOGLE_ADS_AUTH_CONFIG = {
  google: OAUTH_CONFIGS,
  ga4: {
    accounts: [],
    events: {},
  },
  google_ads: {
    accounts: [],
    events: {},
  },
  fb: {
    accounts: [],
    events: {},
  },
};

export const GOOGLE_ANALYTICS_CONFIGS = {
  ga4: {
    accounts: [
      {
        id: '1352543',
        platform_user_id: '12343',
        access_token: window.btoa('test secret'),
        integration_method: 'backend',
      },
      {
        id: '1358755',
        platform_user_id: '12943',
        access_token: window.btoa('its a secret'),
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
    accounts: [],
    events: {},
  },
  fb: {
    accounts: [],
    events: {},
  },
};
