import ajax, { merchantFetch } from 'merchant/utils/ajax';
import { set, merge } from 'common/utils/immutable';

const CONFIG_FETCH = 'CONFIG_FETCH';
const FEATURES_FETCH = 'FEATURES_FETCH';
const MERCHANT_LOGO_UPLOADED = 'MERCHANT_LOGO_UPLOADED';
const CONFIG_SAVE = 'CONFIG_SAVE';
const FEATURES_SAVE = 'FEATURES_SAVE';
const FETCH_LATE_AUTH_CONFIG = 'FETCH_LATE_AUTH_CONFIG';
const CREATE_LATE_AUTH_CONFIG = 'CREATE_LATE_AUTH_CONFIG';
const GET_ONBOARDING_STATUS = 'GET_ONBOARDING_STATUS';
const FETCH_REFUND_PRICING = 'FETCH_REFUND_PRICING';

export const fetchConfigAjax = () => {
  return merchantFetch('account/config');
};

export const FetchRefundPricing = () => {
  return merchantFetch('instant_refunds/pricing');
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

export const onboardTerminal = gateway => {
  let params = {
    url: `terminals/onboard`,
    method: 'post',
    data: {
      gateway: gateway,
    },
  };

  return merchantFetch(params);
};

export const fetchConfig = () => {
  return {
    type: CONFIG_FETCH,
    payload: fetchConfigAjax(),
  };
};

export const fetchRefundPricing = () => {
  return {
    type: FETCH_REFUND_PRICING,
    payload: FetchRefundPricing(),
  };
};

export const fetchOnboardingStatus = gateway => {
  let params = {
    url: `proxy/merchant/terminals?gateway=${gateway}`,
  };
  if (gateway) {
    params.gateway = gateway;
  }
  return merchantFetch(params);
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

export const getOnboardingStatus = gateway => {
  return {
    type: GET_ONBOARDING_STATUS,
    payload: fetchOnboardingStatus(gateway),
  };
};

export const getRefundPricing = gateway => {
  return {
    type: GET_ONBOARDING_STATUS,
    payload: fetchOnboardingStatus(gateway),
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

export const fetchLateAuthConfig = () => {
  return {
    type: FETCH_LATE_AUTH_CONFIG,
    payload: merchantFetch('payment/config/late_auth'),
  };
};

export const createLateAuthConfig = (payload, method) => {
  return {
    type: CREATE_LATE_AUTH_CONFIG,
    payload: merchantFetch({
      url: `payment/config`,
      method: method,
      data: payload,
    }),
  };
};

let initialState = {
  loading: true,
  error: null,
  refund_pricing: { rules: [], custom_pricing: true, not_loaded: true },
  config: {},
  features: [],
  lateAuthConfig: {
    loading: true,
    data: {},
    error: null,
  },
  createdLateAuthConfig: {
    data: {},
    error: null,
  },
  paypal_terminals: [],
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

    case `${FETCH_REFUND_PRICING}::SUCCESS`:
      return merge(state, {
        loading: false,
        refund_pricing: action.payload.data,
        error: null,
      });

    case `${FEATURES_FETCH}::ERROR`:
      return merge(state, {
        loading: false,
        error: action.payload.errors,
        ...initialState,
      });

    case `${FETCH_LATE_AUTH_CONFIG}::SUCCESS`:
      return set(state, 'lateAuthConfig', {
        loading: false,
        data: action.payload.data,
        error: null,
      });

    case `${FETCH_LATE_AUTH_CONFIG}::ERROR`:
      return set(state, 'lateAuthConfig', {
        loading: false,
        data: {},
        error: action.payload.errors,
      });

    case `${CREATE_LATE_AUTH_CONFIG}::SUCCESS`:
      return set(state, 'createdLateAuthConfig', {
        data: action.payload.data,
        error: null,
      });

    case `${CREATE_LATE_AUTH_CONFIG}::ERROR`:
      return set(state, 'createdLateAuthConfig', {
        data: {},
        error: action.payload.errors,
      });

    case `${CONFIG_FETCH}::SUCCESS`:
    case `${CONFIG_SAVE}::SUCCESS`:
    case `${MERCHANT_LOGO_UPLOADED}::SUCCESS`:
      return set(state, 'config', normalizeConfig(action.payload.data));

    case `${FEATURES_SAVE}::SUCCESS`:
      return set(state, 'features', action.payload.data.features);

    case `${GET_ONBOARDING_STATUS}::SUCCESS`:
      return set(state, 'paypal_terminals', action.payload.data.items);

    default:
      return state;
  }
}
