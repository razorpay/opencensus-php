import ajax, { merchantFetch } from 'merchant/utils/ajax';
import { set, merge } from 'common/utils/immutable';
import { deepClone } from 'common/utils/rzp-utils';
import { param_to_qs } from 'merchant/views/TicketSupport/components/data';
import { ACTIVE_TICKETS } from '../views/TicketSupport/components/data';

const CONFIG_FETCH = 'CONFIG_FETCH';
const FEATURES_FETCH = 'FEATURES_FETCH';
const MERCHANT_LOGO_UPLOADED = 'MERCHANT_LOGO_UPLOADED';
const CONFIG_SAVE = 'CONFIG_SAVE';
const FEATURES_SAVE = 'FEATURES_SAVE';
const FETCH_LATE_AUTH_CONFIG = 'FETCH_LATE_AUTH_CONFIG';
const CREATE_LATE_AUTH_CONFIG = 'CREATE_LATE_AUTH_CONFIG';
const GET_ONBOARDING_STATUS = 'GET_ONBOARDING_STATUS';
const FETCH_REFUND_PRICING = 'FETCH_REFUND_PRICING';
const FETCH_CALL_ELIGIBILITY = 'FETCH_CALL_ELIGIBILITY';
const UPDATE_BRAND_COLOR_CONTRAST = 'UPDATE_BRAND_COLOR_CONTRAST';
const FETCH_INTERNATIONAL_PRODUCTS_STATUS = 'FETCH_INTERNATIONAL_PRODUCTS_STATUS';
const REPLY_TO_CONVERSATION = 'REPLY_TO_CONVERSATION';
const FETCH_SUPPORT_TICKETS = 'FETCH_SUPPORT_TICKETS';
const FETCH_ACTIVE_TICKETS = 'FETCH_ACTIVE_TICKETS';

export const TICKET_BASE_URL = 'fd/support_dashboard/ticket';

export const fetchConfigAjax = () => {
  return merchantFetch('account/config');
};

export const FetchSupportTickets = (params) => {
  let query;
  if (params) {
    query = param_to_qs(params);
  }

  return merchantFetch({
    url: TICKET_BASE_URL,
    mode: 'live',
  }).then((res) => {
    return {
      data: res.data.results,
      query: params,
    };
  });
};

export const FetchRefundPricing = () => {
  return merchantFetch('instant_refunds/pricing');
};

export const ReplyToConversation = (ticket_id, body) => {
  const params = {
    url: `${TICKET_BASE_URL}/${ticket_id}/reply`,
    mode: 'live',
    method: 'post',
    data: body,
    headers: { 'Content-Type': 'multipart/form-data' },
  };

  return merchantFetch(params);
};

export const FetchActiveTickets = () => {
  const url = TICKET_BASE_URL;
  return merchantFetch({
    url,
    mode: 'live',
  }).then((res) => res.data.results);
};

export const CheckCallEligibility = () => {
  return merchantFetch('merchants/support_call/can_submit').then(
    (res) => res && res.success && res.data && res.data.response === true,
  );
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

export const onboardTerminal = (gateway) => {
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

export const checkCallEligibility = () => {
  return {
    type: FETCH_CALL_ELIGIBILITY,
    payload: CheckCallEligibility(),
  };
};

export const fetchActiveTickets = () => {
  return {
    type: FETCH_ACTIVE_TICKETS,
    payload: FetchActiveTickets(),
  };
};

export const replyToConversation = (ticket, body) => {
  return {
    type: REPLY_TO_CONVERSATION,
    payload: ReplyToConversation(ticket, body),
  };
};

export const fetchOnboardingStatus = (gateway) => {
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
export const fetchFeatures = (currentUserId) => {
  return {
    type: FEATURES_FETCH,
    payload: fetchFeaturesAjax(currentUserId),
  };
};

export const fetchSupportTickets = (params) => {
  return {
    type: FETCH_SUPPORT_TICKETS,
    payload: FetchSupportTickets(params),
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

export const updateConfig = (data) => {
  return {
    type: CONFIG_SAVE,
    payload: merchantFetch({
      url: 'account/config',
      method: 'put',
      data,
    }),
  };
};

export const updateBrandColorContrast = (isBrandColorDark = false) => {
  return {
    type: UPDATE_BRAND_COLOR_CONTRAST,
    payload: isBrandColorDark,
  };
};

export const getOnboardingStatus = (gateway) => {
  return {
    type: GET_ONBOARDING_STATUS,
    payload: fetchOnboardingStatus(gateway),
  };
};

export const getRefundPricing = (gateway) => {
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
const normalizeConfig = (config) => {
  let logoUrl = config.logo_url;

  config.transaction_report_email = config.transaction_report_email.join(',');

  config.brand_color = config.brand_color || '#528FF0';
  config.hasPersonalised = !!logoUrl;

  /**
   * API is currently returning invalid logo urls
   * so we need to translate it into a valid URL
   */
  if (logoUrl !== null && !/^http/.test(logoUrl)) {
    logoUrl = `https://cdn.razorpay.com${logoUrl.replace(/\.([^\.]+$)/, '_medium.$1')}`;
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

export const fetchInternationalProductsStatus = () => {
  return {
    type: FETCH_INTERNATIONAL_PRODUCTS_STATUS,
    payload: merchantFetch({
      url: 'merchants/product_international/workflow/status/all',
      mode: 'live',
      method: 'GET',
    }),
  };
};

let initialState = {
  loading: true,
  error: null,
  refund_pricing: { rules: [], custom_pricing: true, not_loaded: true },
  config: {},
  isBrandColorDark: false,
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
  support_tickets: {
    loading: false,
    data: {},
    active: [],
  },
  internationalProductsStatus: {
    loading: false,
    data: {},
    error: null,
  },
  isCallEnabled: false,
};

export default function (state = initialState, action) {
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

    case `${FETCH_CALL_ELIGIBILITY}::SUCCESS`:
      return set(state, 'isCallEnabled', !!action.payload);

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

    case 'UPDATE_BRAND_COLOR_CONTRAST':
      return set(state, 'isBrandColorDark', !!action.payload);

    case `${FETCH_SUPPORT_TICKETS}::PENDING`:
      let support_tickets = deepClone(state.support_tickets);
      support_tickets.loading = true;
      return merge(state, {
        support_tickets: support_tickets,
      });

    case `${FETCH_SUPPORT_TICKETS}::SUCCESS`:
      let st = deepClone(state.support_tickets);
      st.data[action.payload.query.page] = action.payload.data;
      st.loading = false;
      return merge(state, {
        support_tickets: st,
      });

    case `${FETCH_SUPPORT_TICKETS}::ERROR`:
      let S = deepClone(state.support_tickets);
      S.loading = false;
      return merge(state, {
        support_tickets: S,
      });

    case `${FETCH_ACTIVE_TICKETS}::SUCCESS`:
      let ST = deepClone(state.support_tickets);
      ST.active = action.payload;
      ST.active = ST.active.map((t) => {
        t.created_at = moment(t.created_at).fromNow();
        t.subject = t.subject.replace('[Merchant]', '');
        return t;
      });
      window.rzpActiveTickets = ST.active.slice(0, 3);
      return merge(state, {
        support_tickets: ST,
      });

    case `${FETCH_INTERNATIONAL_PRODUCTS_STATUS}::PENDING`:
      return merge(state, {
        internationalProductsStatus: {
          loading: true,
          ...state.internationalProductsStatus,
        },
      });

    case `${FETCH_INTERNATIONAL_PRODUCTS_STATUS}::SUCCESS`:
      return set(state, 'internationalProductsStatus', {
        loading: false,
        data: action.payload.data.data,
        error: null,
      });

    case `${FETCH_INTERNATIONAL_PRODUCTS_STATUS}::ERROR`:
      return set(state, 'internationalProductsStatus', {
        loading: false,
        data: {},
        error: action.payload.errors,
      });

    default:
      return state;
  }
}
