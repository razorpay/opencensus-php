import ajax from 'merchant/utils/ajax';
import { set, merge } from 'rzp/utils/immutable';

const CONFIG_FETCH = 'CONFIG_FETCH';
const CONFIG_AND_FEATURES_FETCH = 'CONFIG_AND_FEATURES_FETCH';
const MERCHANT_LOGO_UPLOADED = 'MERCHANT_LOGO_UPLOADED';
const CONFIG_SAVE = 'CONFIG_SAVE';
const FEATURES_SAVE = 'FEATURES_SAVE';

export const fetchConfigAjax = () => {
  return ajax({
    url: '/user/generic',
    appendModeInQueryParam: true,
    data: {
      route_name: 'merchant_fetch_config',
    },
  });
};

export const fetchFeaturesAjax = (currentUserId, mode) => {
  let params = {
    route_name: 'merchant_get_features',
    url_params: {
      '{id}': currentUserId,
    },
  };

  const ajaxQuery = {
    url: '/user/generic',
    data: params,
    appendModeInQueryParam: true,
  };

  if (mode) {
    ajaxQuery.data.mode = mode;
  }

  return ajax(ajaxQuery);
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
export const fetchConfigAndFeatures = currentUserId => {
  return {
    type: CONFIG_AND_FEATURES_FETCH,
    payload: Promise.all([fetchConfigAjax(), fetchFeaturesAjax(currentUserId)]),
  };
};

export const updateFeatures = (data, currentUserId) => {
  let params = {
    route_name: 'merchant_update_features',
    body: data,
    url_params: {
      '{id}': currentUserId,
    },
  };

  return {
    type: FEATURES_SAVE,
    payload: ajax({
      url: '/user/generic',
      method: 'post',
      data: params,
      appendModeInQueryParam: true,
    }),
  };
};

export const updateConfig = data => {
  var params = {
    route_name: 'merchant_edit_config',
    body: data,
  };
  return {
    type: CONFIG_SAVE,
    payload: ajax({
      url: '/user/generic',
      method: 'put',
      data: params,
      appendModeInQueryParam: true,
    }),
  };
};

export const uploadLogo = (file, fieldName) => {
  let params = {
    route_name: 'merchant_edit_config_logo',
    file_name: fieldName,
    file: file,
  };

  let formData = new FormData();
  for (let field in params) {
    let value = params[field];
    formData.append(field, value);
  }

  return {
    type: MERCHANT_LOGO_UPLOADED,
    payload: ajax({
      url: '/user/generic',
      file: file,
      data: formData,
      method: 'post',
      processData: false,
      contentType: false,
      appendModeInQueryParam: true,
    }),
  };
};

/* normalize config in proper format*/
const normalizeConfig = config => {
  config.transaction_report_email = config.transaction_report_email.join(',');

  /**
   * API is currently returning invalid logo urls
   * so we need to translate it into a valid URL
   */
  let logoUrl = config.logo_url;
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
    case `${CONFIG_AND_FEATURES_FETCH}::PENDING`:
      return set(state, 'loading', true);

    case `${CONFIG_AND_FEATURES_FETCH}::SUCCESS`:
      return merge(state, {
        loading: false,
        config: normalizeConfig(action.payload[0].data),
        features: action.payload[1].data.features,
        error: null,
      });

    case `${CONFIG_AND_FEATURES_FETCH}::ERROR`:
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
