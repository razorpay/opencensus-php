import { merchantFetch } from 'merchant/utils/ajax';

const REDUCER_NAMESPACE = 'MAGIC_ANALYTICS_CONFIGS';

export const ACTIONS = {
  FETCH_CONFIGS: `${REDUCER_NAMESPACE}_CONFIGS_FETCH`,
  FETCH_CONFIGS_PENDING: `${REDUCER_NAMESPACE}_CONFIGS_FETCH::PENDING`,
  FETCH_CONFIGS_SUCCESS: `${REDUCER_NAMESPACE}_CONFIGS_FETCH::SUCCESS`,
  FETCH_CONFIGS_ERROR: `${REDUCER_NAMESPACE}_CONFIGS_FETCH::ERROR`,

  DELETE_CONFIGS: `${REDUCER_NAMESPACE}_CONFIGS_DELETE`,
  DELETE_CONFIGS_PENDING: `${REDUCER_NAMESPACE}_CONFIGS_DELETE::PENDING`,
  DELETE_CONFIGS_SUCCESS: `${REDUCER_NAMESPACE}_CONFIGS_DELETE::SUCCESS`,
  DELETE_CONFIGS_ERROR: `${REDUCER_NAMESPACE}_CONFIGS_DELETE::ERROR`,

  ADD_ACCOUNT: `${REDUCER_NAMESPACE}_ACCOUNT_ADD`,
  ADD_ACCOUNT_PENDING: `${REDUCER_NAMESPACE}_ACCOUNT_ADD::PENDING`,
  ADD_ACCOUNT_SUCCESS: `${REDUCER_NAMESPACE}_ACCOUNT_ADD::SUCCESS`,
  ADD_ACCOUNT_ERROR: `${REDUCER_NAMESPACE}_ACCOUNT_ADD::ERROR`,

  UPDATE_EVENT_CONFIGS: `${REDUCER_NAMESPACE}_EVENT_CONFIGS_UPDATE`,
  UPDATE_EVENT_CONFIGS_PENDING: `${REDUCER_NAMESPACE}_EVENT_CONFIGS_UPDATE::PENDING`,
  UPDATE_EVENT_CONFIGS_SUCCESS: `${REDUCER_NAMESPACE}_EVENT_CONFIGS_UPDATE::SUCCESS`,
  UPDATE_EVENT_CONFIGS_ERROR: `${REDUCER_NAMESPACE}_EVENT_CONFIGS_UPDATE::ERROR`,

  OAUTH_API: `${REDUCER_NAMESPACE}_OAUTH_API`,
  OAUTH_API_PENDING: `${REDUCER_NAMESPACE}_OAUTH_API::PENDING`,
  OAUTH_API_SUCCESS: `${REDUCER_NAMESPACE}_OAUTH_API::SUCCESS`,
  OAUTH_API_ERROR: `${REDUCER_NAMESPACE}_OAUTH_API::ERROR`,

  RESET_ANALYTICS_CONFIGS: `${REDUCER_NAMESPACE}_RESET`,
};

export const fetchConfigs = () => {
  return {
    type: ACTIONS.FETCH_CONFIGS,
    payload: merchantFetch({
      url: '1cc/analytics_integration/configs',
    }),
  };
};

export const deleteAccountConfigs = (id, analyticsPlatform) => {
  return {
    type: ACTIONS.DELETE_CONFIGS,
    payload: merchantFetch({
      url: `1cc/analytics_integration/accounts/${id}`,
      method: 'delete',
    }),
    id,
    analyticsPlatform,
  };
};

export const addAccountConfigs = (payload) => {
  return {
    type: ACTIONS.ADD_ACCOUNT,
    payload: merchantFetch({
      url: '1cc/analytics_integration/accounts',
      method: 'post',
      data: payload,
    }),
    analyticsPlatform: payload.platform,
  };
};

export const saveEventConfigs = (configs, analyticsPlatform) => {
  const payload = {
    platform: analyticsPlatform,
    events: configs,
  };

  return {
    type: ACTIONS.UPDATE_EVENT_CONFIGS,
    payload: merchantFetch({
      url: '1cc/analytics_integration/event_configs',
      method: 'post',
      data: payload,
    }),
    analyticsPlatform,
  };
};

export const fetchOauthId = () => {
  return {
    type: ACTIONS.OAUTH_API,
    payload: merchantFetch({
      url: '1cc/analytics_integration/oauth/redirect_url',
      method: 'post',
      data: {
        oauth_provider: 'google',
        target_url: window.btoa(
          'https://dashboard.dev.razorpay.in/app/magic/settings/analytics-settings?platform=google-ads',
        ),
      },
    }),
  };
};

export const resetAnalyticsSettings = () => {
  return {
    type: ACTIONS.RESET_ANALYTICS_CONFIGS,
  };
};
