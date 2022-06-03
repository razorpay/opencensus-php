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

  UPDATE_PAGE_VIEW: `${REDUCER_NAMESPACE}_UPDATE::PAGE`,

  UPDATE_DOMAIN_DETAIL: `${REDUCER_NAMESPACE}_UPDATE::DOMAIN`,

  UPDATE_COD_SLABS_SET: `${REDUCER_NAMESPACE}_UPDATE_COD_SLABS_SET`,
};

export const updatePageView = (newView) => {
  return {
    type: ACTIONS.UPDATE_PAGE_VIEW,
    payload: {
      view: newView,
    },
  };
};

export const updateDomainDetail = (domain) => {
  return {
    type: ACTIONS.UPDATE_DOMAIN_DETAIL,
    payload: {
      domain,
    },
  };
};

export const fetchMagicSettings = () => {
  return {
    type: ACTIONS.FETCH_MAGIC_SETTINGS,
    payload: merchantFetch({
      url: '1cc/merchant/configs',
    }),
  };
};

export const updateMagicSettings = (payload, showLoader = true) => {
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
    data: { ...payload, showLoader },
  };
};

export const codSlabsSet = (codSlabsSetFlag) => {
  return {
    type: ACTIONS.UPDATE_COD_SLABS_SET,
    payload: { codSlabsSetFlag },
  };
};
