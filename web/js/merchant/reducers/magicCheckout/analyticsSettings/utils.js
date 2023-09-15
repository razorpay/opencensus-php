import { INTEGRATION_TYPE } from 'merchant/views/MagicCheckout/AnalyticsSettings/constants';

export const updateEventConfigs = (action, state) => {
  const { analyticsPlatform } = action;
  const response = action?.payload?.data?.events || {};
  const platformConfigs = state.merchantAnalyticsConfigs?.[analyticsPlatform];

  const newUpdateConfig =
    platformConfigs?.analytics_accounts?.length === 0
      ? Object.assign({}, platformConfigs, {
          analytics_accounts: [{ integration_method: INTEGRATION_TYPE.frontend }],
          events: Object.assign({}, platformConfigs?.events, response),
        })
      : Object.assign({}, platformConfigs, {
          events: Object.assign({}, platformConfigs?.events, response),
        });

  const updatedConfigs = Object.assign({}, state.merchantAnalyticsConfigs, {
    [analyticsPlatform]: newUpdateConfig,
  });

  return updatedConfigs;
};

export const addAccount = (action, state) => {
  const { analyticsPlatform: platform } = action;
  const { merchantAnalyticsConfigs } = state;

  const { analytics_accounts: analyticsAccounts } = merchantAnalyticsConfigs[platform];

  let updatedAccountConfigs;
  if (
    analyticsAccounts.length === 1 &&
    analyticsAccounts?.[0].integration_method === INTEGRATION_TYPE.frontend
  ) {
    updatedAccountConfigs = Object.assign({}, merchantAnalyticsConfigs, {
      [platform]: Object.assign({}, merchantAnalyticsConfigs[platform], {
        analytics_accounts: [action?.payload?.data],
      }),
    });
  } else {
    updatedAccountConfigs = Object.assign({}, merchantAnalyticsConfigs, {
      [platform]: Object.assign({}, merchantAnalyticsConfigs[platform], {
        analytics_accounts: merchantAnalyticsConfigs[platform]?.analytics_accounts.concat([
          action?.payload?.data,
        ]),
      }),
    });
  }

  return updatedAccountConfigs;
};
