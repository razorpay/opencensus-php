import { merchantFetch, merchantFetchWithContentType } from 'merchant/utils/ajax';
import { transformToApiFormat } from 'merchant/reducers/magicCheckout/magicSettings/utils';

const REDUCER_NAMESPACE = 'MAGIC_SETTINGS';

export const ACTIONS = {
  FETCH_MAGIC_SETTINGS: `${REDUCER_NAMESPACE}_FETCH`,
  FETCH_MAGIC_SETTINGS_PENDING: `${REDUCER_NAMESPACE}_FETCH::PENDING`,
  FETCH_MAGIC_SETTINGS_SUCCESS: `${REDUCER_NAMESPACE}_FETCH::SUCCESS`,
  FETCH_MAGIC_SETTINGS_ERROR: `${REDUCER_NAMESPACE}_FETCH::ERROR`,

  UPDATE_MAGIC_SETTINGS: `${REDUCER_NAMESPACE}_UPDATE`,
  UPDATE_MAGIC_SETTINGS_PENDING: `${REDUCER_NAMESPACE}_UPDATE::PENDING`,
  UPDATE_MAGIC_SETTINGS_SUCCESS: `${REDUCER_NAMESPACE}_UPDATE::SUCCESS`,
  UPDATE_MAGIC_SETTINGS_ERROR: `${REDUCER_NAMESPACE}_UPDATE::ERROR`,
};

export const fetchMagicSettings = () => {
  return {
    type: ACTIONS.FETCH_MAGIC_SETTINGS,
    payload: merchantFetch({
      url: '1cc/merchant/configs',
    }),
  };
};

export const updateMagicSettings = (payload) => {
  if (payload.cod_slabs) {
    payload.cod_slabs = transformToApiFormat(payload.cod_slabs);
  }
  return {
    type: ACTIONS.UPDATE_MAGIC_SETTINGS,
    payload: merchantFetchWithContentType({
      url: '1cc/merchant/configs',
      method: 'post',
      data: payload,
    }),
    data: payload,
  };
};
