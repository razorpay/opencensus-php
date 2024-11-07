import { merchantFetch } from 'merchant/utils/ajax';

const REDUCER_NAMESPACE = 'MAGIC_PARTIAL_COD';
const CUSTOM_HEADERS = {
  'X-Dashboard-User-Id': window.rzp_user?.user?.id,
};

export const ACTIONS = {
  FETCH_PARTIAL_COD: `${REDUCER_NAMESPACE}_FETCH`,
  FETCH_PARTIAL_COD_PENDING: `${REDUCER_NAMESPACE}_FETCH::PENDING`,
  FETCH_PARTIAL_COD_SUCCESS: `${REDUCER_NAMESPACE}_FETCH::SUCCESS`,
  FETCH_PARTIAL_COD_ERROR: `${REDUCER_NAMESPACE}_FETCH::ERROR`,

  UPDATE_CONFIGS: `${REDUCER_NAMESPACE}_CONFIGS_UPDATE`,
  UPDATE_CONFIGS_PENDING: `${REDUCER_NAMESPACE}_CONFIGS_UPDATE::PENDING`,
  UPDATE_CONFIGS_SUCCESS: `${REDUCER_NAMESPACE}_CONFIGS_UPDATE::SUCCESS`,
  UPDATE_CONFIGS_ERROR: `${REDUCER_NAMESPACE}_CONFIGS_UPDATE::ERROR`,

  RESET_PARTIAL_COD: `${REDUCER_NAMESPACE}_RESET`,
};

export const fetchPartialCODConfigs = () => {
  return {
    type: ACTIONS.FETCH_PARTIAL_COD,
    payload: merchantFetch({
      url: '1cc/partial_cod/configs',
      headers: CUSTOM_HEADERS,
    }),
  };
};

export const resetPartialCODConfigs = () => ({
  type: ACTIONS.RESET_PARTIAL_COD,
});

export const updatePartialCODConfigs = (payload) => {
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
