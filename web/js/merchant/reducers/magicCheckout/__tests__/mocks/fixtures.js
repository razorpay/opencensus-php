export const eventConfigs = {
  analyticsPlatform: 'ga4',
  payload: {
    data: {
      events: {
        purchase: true,
        checkout_initiated: false,
      },
    },
  },
};

export const stateWithEmptyConfigs = {
  merchantAnalyticsConfigs: {
    ga4: {
      accounts: [],
      events: {
        purchase: false,
        checkout_initiated: false,
      },
    },
  },
};

export const stateWithFrontendConfigs = {
  merchantAnalyticsConfigs: {
    ga4: {
      accounts: [{ integration_method: 'frontend' }],
      events: {
        purchase: false,
        checkout_initiated: false,
      },
    },
  },
};

export const stateWithBackendConfigs = {
  merchantAnalyticsConfigs: {
    ga4: {
      accounts: [
        {
          integration_method: 'backend',
          platform_user_id: 'test789',
          measurement_id: 'test001',
        },
      ],
      events: {
        purchase: false,
        checkout_initiated: false,
      },
    },
  },
};

export const addedConfigs = {
  analyticsPlatform: 'ga4',
  payload: {
    data: {
      integration_method: 'backend',
      platform_user_id: 'test123',
      measurement_id: 'test002',
    },
  },
};
