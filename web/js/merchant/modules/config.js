import ajax from 'merchant/utils/ajax';
import { set, merge } from 'rzp/utils/immutable';
import { merchantFetch } from 'rzp/utils/ajax';

const CONFIG_FETCH = 'CONFIG_FETCH';
const FEATURES_FETCH = 'FEATURES_FETCH';
const MERCHANT_LOGO_UPLOADED = 'MERCHANT_LOGO_UPLOADED';
const CONFIG_SAVE = 'CONFIG_SAVE';
const FEATURES_SAVE = 'FEATURES_SAVE';

export const fetchConfigAjax = () => {
  return merchantFetch('account/config');
};

export const fetchFeaturesAjax = (currentUserId, mode) => {
  let params = {
    url: `merchants/me/features`,
  };

  if (mode) {
    params.mode = mode;
  }
  return merchantFetch(params);
};

export const fetchConfig = () => {
  return {
    type: CONFIG_FETCH,
    payload: fetchConfigAjax(),
  };
};

/*
 * Fetches merchant's config and features
 */
export const fetchFeatures = currentUserId => {
  return {
    type: FEATURES_FETCH,
    payload: fetchFeaturesAjax(currentUserId),
  };
};

export const updateFeatures = (data, currentUserId) => {
  return {
    type: FEATURES_SAVE,
    payload: merchantFetch({
      url: `merchants/me/features`,
      method: 'post',
      data: data,
    }),
  };
};

export const updateConfig = data => {
  return {
    type: CONFIG_SAVE,
    payload: merchantFetch({
      url: 'account/config',
      method: 'put',
      data,
    }),
  };
};

export const uploadLogo = (file, fieldName) => {
  let formData = new FormData();
  formData.append(fieldName, file);

  return {
    type: MERCHANT_LOGO_UPLOADED,
    payload: merchantFetch({
      url: 'account/config/logo',
      method: 'post',
      file,
      data: formData,
    }),
  };
};

/* normalize config in proper format*/
const normalizeConfig = config => {
  let logoUrl = config.logo_url;

  config.transaction_report_email = config.transaction_report_email.join(',');

  config.brand_color = config.brand_color || '#528FF0';
  config.hasPersonalised = !!logoUrl;

  /**
   * API is currently returning invalid logo urls
   * so we need to translate it into a valid URL
   */
  if (logoUrl !== null && !/^http/.test(logoUrl)) {
    logoUrl = `https://cdn.razorpay.com${logoUrl.replace(
      /\.([^\.]+$)/,
      '_medium.$1'
    )}`;
  }
  config.logo_url = logoUrl;

  return config;
};

let initialState = {
  loading: true,
  error: null,
  config: {},
  features: [],
};

export default function(state = initialState, action) {
  switch (action.type) {
    case `${FEATURES_FETCH}::PENDING`:
      return set(state, 'loading', true);

    case `${FEATURES_FETCH}::SUCCESS`:
      return merge(state, {
        loading: false,
        features: action.payload.data.features,
        error: null,
      });

    case `${FEATURES_FETCH}::ERROR`:
      return merge(state, {
        loading: false,
        error: action.payload.errors,
        ...initialState,
      });

    case `${CONFIG_FETCH}::SUCCESS`:
    case `${CONFIG_SAVE}::SUCCESS`:
    case `${MERCHANT_LOGO_UPLOADED}::SUCCESS`:
      return set(state, 'config', normalizeConfig(action.payload.data));

    case `${FEATURES_SAVE}::SUCCESS`:
      return set(state, 'features', action.payload.data.features);

    default:
      return state;
  }
}
