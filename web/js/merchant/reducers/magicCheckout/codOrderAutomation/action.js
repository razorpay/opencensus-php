import { merchantFetch } from 'merchant/utils/ajax';

const REDUCER_NAMESPACE = 'COD_ORDERS_AUTOMATION';
const CUSTOM_HEADERS = {
  'X-Dashboard-User-Id': window.rzp_user?.user?.id,
};

export const ACTIONS = {
  FETCH_CONFIGS: `${REDUCER_NAMESPACE}_CONFIGS_FETCH`,
  FETCH_CONFIGS_PENDING: `${REDUCER_NAMESPACE}_CONFIGS_FETCH::PENDING`,
  FETCH_CONFIGS_SUCCESS: `${REDUCER_NAMESPACE}_CONFIGS_FETCH::SUCCESS`,
  FETCH_CONFIGS_ERROR: `${REDUCER_NAMESPACE}_CONFIGS_FETCH::ERROR`,

  UPDATE_CONFIGS: `${REDUCER_NAMESPACE}_CONFIGS_UPDATE`,
  UPDATE_CONFIGS_PENDING: `${REDUCER_NAMESPACE}_CONFIGS_UPDATE::PENDING`,
  UPDATE_CONFIGS_SUCCESS: `${REDUCER_NAMESPACE}_CONFIGS_UPDATE::SUCCESS`,
  UPDATE_CONFIGS_ERROR: `${REDUCER_NAMESPACE}_CONFIGS_UPDATE::ERROR`,
};

export const fetchAutomationConfigs = () => {
  return {
    type: ACTIONS.FETCH_CONFIGS,
    payload: merchantFetch({
      url: '1cc/orders/review/automation/rule_configs',
    }),
  };
};

export const updateAutomationConfigs = (payload) => {
  const configs = payload?.map((item) => ({ rule_value: item.type, rule_action: item.action }));
  const data = {
    rule_config: [
      {
        type: 'risk_tier',
        data: configs ?? [],
      },
    ],
  };

  return {
    type: ACTIONS.UPDATE_CONFIGS,
    payload: merchantFetch({
      url: '1cc/orders/review/automation/rule_configs',
      method: 'post',
      headers: CUSTOM_HEADERS,
      data,
    }),
  };
};
