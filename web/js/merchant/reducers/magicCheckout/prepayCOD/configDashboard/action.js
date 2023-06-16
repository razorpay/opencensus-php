import { merchantFetch } from 'merchant/utils/ajax';

const REDUCER_NAMESPACE = 'MAGIC_PREPAY_COD';
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

  RESET_CONFIGS: `${REDUCER_NAMESPACE}_CONFIGS`,
};

export const fetchPrepayCODConfigs = () => {
  return {
    type: ACTIONS.FETCH_CONFIGS,
    payload: merchantFetch({
      url: '1cc/prepay/configs',
      headers: CUSTOM_HEADERS,
    }),
  };
};

export const updatePrepayCODConfigs = (payload) => {
  return {
    type: ACTIONS.UPDATE_CONFIGS,
    payload: merchantFetch({
      url: '1cc/merchant/configs',
      method: 'post',
      headers: CUSTOM_HEADERS,
      data: payload,
    }),
    data: payload,
  };
};

export const resetPrepayCODConfigs = () => ({
  type: ACTIONS.RESET_CONFIGS,
});
