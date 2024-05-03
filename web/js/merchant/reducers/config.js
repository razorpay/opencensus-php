import moment from 'moment';
import { merchantFetch } from 'merchant/utils/ajax';
import { set, merge } from 'common/utils/immutable';
import { deepClone } from 'common/utils/rzp-utils';
import { computeBannerState } from 'merchant/utils/intlPaymentsRecommendation';
import { SYNCHRONIZE_EMAIL_SETTINGS_ON_GRAPHQL_EMAIL_MUTATION_UPDATE_SUCCESS } from 'merchant/reducers/graphql/configSettings/actions';

const CONFIG_FETCH = 'CONFIG_FETCH';
const LOCALE_FETCH = 'CONFIG_LOCALE_FETCH';
const LOCALE_UPDATE = 'CONFIG_LOCALE_UPDATE';
const EMAIL_CONFIG_UPDATE = 'EMAIL_CONFIG_UPDATE';
const LOCALE_SAVE = 'CONFIG_LOCALE_SAVE';
const FEATURES_FETCH = 'FEATURES_FETCH';
const FETCH_TICKET_RAISED_BY_AGENTS = 'FETCH_TICKET_RAISED_BY_AGENTS';
const MERCHANT_LOGO_UPLOADED = 'MERCHANT_LOGO_UPLOADED';
const CONFIG_SAVE = 'CONFIG_SAVE';
const CONFIG_SAVE_EMAIL = 'CONFIG_SAVE_EMAIL';
const FEATURES_SAVE = 'FEATURES_SAVE';
const FETCH_LATE_AUTH_CONFIG = 'FETCH_LATE_AUTH_CONFIG';
const CREATE_LATE_AUTH_CONFIG = 'CREATE_LATE_AUTH_CONFIG';
const GET_ONBOARDING_STATUS = 'GET_ONBOARDING_STATUS';
const FETCH_REFUND_PRICING = 'FETCH_REFUND_PRICING';
const UPDATE_BRAND_COLOR_CONTRAST = 'UPDATE_BRAND_COLOR_CONTRAST';
const FETCH_INTERNATIONAL_PRODUCTS_STATUS = 'FETCH_INTERNATIONAL_PRODUCTS_STATUS';
const REPLY_TO_CONVERSATION = 'REPLY_TO_CONVERSATION';
const FETCH_SUPPORT_TICKETS = 'FETCH_SUPPORT_TICKETS';
const FETCH_ACTIVE_TICKETS = 'FETCH_ACTIVE_TICKETS';
const REMOVE_LOGO = 'REMOVE_LOGO';
const FETCH_FEATURE_STATUS = 'FETCH_FEATURE_STATUS';
const FETCH_INTERNATIONAL_SETTING_STATUS = 'FETCH_INTERNATIONAL_SETTING_STATUS';
const FETCH_MERCHANT_MOPL_SUBSCRIPTION = 'FETCH_MERCHANT_MOPL_SUBSCRIPTION';
const FETCH_MOPL_PLANS = 'FETCH_MOPL_PLANS';
const FETCH_INSIGHTS = 'FETCH_INSIGHTS';
const FETCH_FEATURE_BY_NAME = 'FETCH_FEATURE_BY_NAME';

export const TICKET_BASE_URL = 'fd/support_dashboard/ticket';
const ADD_REPLY_URL_CARE_SERVICE =
  'care_service/merchant/twirp/rzp.care.freshdesk.v1.FreshdeskService/PostTicketReply';
export const FETCH_WORKFLOWS =
  'care_service/merchant/twirp/rzp.care.workflow.v1.WorkflowService/FetchWorkflows';

export const FETCH_TICKET =
  'care_service/merchant/twirp/rzp.care.freshdesk.v1.FreshdeskService/GetTicket';

export const FETCH_TICKETS =
  'care_service/merchant/twirp/rzp.care.freshdesk.v1.FreshdeskService/GetTickets';

export const fetchConfigAjax = () => {
  return merchantFetch('account/config');
};

export const fetchSupportTicketsApiCall = (
  params,
  filter,
  isFetchTicketsApiMigrationActive,
  isPosMerchantActivated,
) => {
  const requestPayload = {
    url: TICKET_BASE_URL,
    mode: 'live',
  };

  if (isPosMerchantActivated || isFetchTicketsApiMigrationActive) {
    requestPayload.url = FETCH_TICKETS;
    requestPayload.method = 'post';
    requestPayload.data = {
      type: 'support_dashboard',
    };
    requestPayload.headers = {
      'Content-Type': 'application/json',
    };
    if (filter) {
      const key = Object.keys(filter)[0];
      requestPayload.data[key] = filter[key];
    }
  } else if (filter) {
    const key = Object.keys(filter)[0];
    requestPayload.url = `${requestPayload.url}?${key}=${filter[key]}`;
  }

  return merchantFetch(requestPayload).then((res) => {
    const filteredResults = res?.data?.results?.filter(
      (item) => !item?.custom_fields?.cf_workflow_id,
    );
    return {
      data: filteredResults,
      query: params,
    };
  });
};

export const fetchRefundPricingApiCall = () => {
  if (!window.rzp_user) {
    return Promise.resolve();
  }

  return merchantFetch('instant_refunds/pricing');
};

export const replyToConversationApiCal = (ticket_id, body, isAddReplyMigrationActive = false) => {
  let url = `${TICKET_BASE_URL}/${ticket_id}/reply`;
  if (isAddReplyMigrationActive) {
    url = ADD_REPLY_URL_CARE_SERVICE;
  }
  const params = {
    url,
    mode: 'live',
    method: 'post',
    data: body,
    headers: {
      'Content-Type': 'multipart/form-data',
    },
  };

  return merchantFetch(params);
};

export const fetchActiveTicketsApiCall = () => {
  const url = TICKET_BASE_URL;
  return merchantFetch({
    url,
    mode: 'live',
  }).then((res) => res.data.results);
};

export const fetchFeaturesAjax = (currentUserId, mode) => {
  const params = {
    url: `merchants/me/features`,
  };

  if (mode) {
    params.mode = mode;
  }
  return merchantFetch(params);
};

export const onboardTerminal = (gateway) => {
  const params = {
    url: `terminals/onboard`,
    method: 'post',
    data: {
      gateway,
    },
  };

  return merchantFetch(params);
};

export const missedOrderPlanActivation = (planId) => {
  const params = {
    url: `payment_links/subscriptions`,
    method: 'post',
    data: {
      plan_id: planId,
    },
  };

  return merchantFetch(params);
};

export const missedOrderPlanDeActivation = () => {
  const params = {
    url: `payment_links/subscriptions/deactivate`,
    method: 'post',
  };

  return merchantFetch(params);
};

export const onboardPaytmTerminal = (
  gateway,
  merchant_provided_paytm_key,
  merchant_provided_id,
  industry,
  website,
  mode,
) => {
  const params = {
    url: `terminals/onboard`,
    method: 'post',
    mode,
    data: {
      gateway,
      secrets: {
        gateway_secure_secret: merchant_provided_paytm_key,
      },
      identifiers: {
        gateway_merchant_id: merchant_provided_id,
        gateway_terminal_id: industry,
        gateway_access_code: website,
      },
    },
  };

  return merchantFetch(params);
};

export const getPaytmCredentials = (mid) => {
  const params = {
    url: `terminals/credentials`,
    method: 'POST',
    data: {
      gateway: 'paytm',
      merchant_ids: [mid],
      procurer: 'merchant',
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

export const fetchLocale = () => {
  return {
    type: LOCALE_FETCH,
    payload: merchantFetch('payment/config/locale?count=1'),
  };
};

export const updateLocale = (locale) => {
  return {
    type: LOCALE_UPDATE,
    payload: locale,
  };
};

export const updateEmailConfig = (config) => {
  return {
    type: EMAIL_CONFIG_UPDATE,
    payload: config,
  };
};

export const saveLocale = (data) => {
  return {
    type: LOCALE_SAVE,
    payload: merchantFetch({
      url: 'payment/config',
      method: data.id ? 'patch' : 'post',
      data,
    }),
  };
};

export const fetchRefundPricing = () => {
  return {
    type: FETCH_REFUND_PRICING,
    payload: fetchRefundPricingApiCall(),
  };
};

export const fetchActiveTickets = () => {
  return {
    type: FETCH_ACTIVE_TICKETS,
    payload: fetchActiveTicketsApiCall(),
  };
};

export const replyToConversation = (ticket, body, isAddReplyMigrationActive = false) => {
  return {
    type: REPLY_TO_CONVERSATION,
    payload: replyToConversationApiCal(ticket, body, isAddReplyMigrationActive),
  };
};

export const fetchOnboardingStatus = (gateway) => {
  const params = {
    url: `proxy/terminal/onboard/status?gateway=${gateway}`,
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

export const fetchTicketsRaisedByAgents = (isFetchTicketsApiMigrationActive = false) => {
  return {
    type: FETCH_TICKET_RAISED_BY_AGENTS,
    payload: fetchSupportTicketsApiCall(
      {
        page: 1,
        per_page: 20,
      },
      {
        cf_created_by: 'agent',
      },
      isFetchTicketsApiMigrationActive,
    ),
  };
};

export const fetchSupportTickets = (
  params,
  filter,
  isFetchTicketsApiMigrationActive = false,
  isPosMerchantActivated,
) => {
  return {
    type: FETCH_SUPPORT_TICKETS,
    payload: fetchSupportTicketsApiCall(
      params,
      filter,
      isFetchTicketsApiMigrationActive,
      isPosMerchantActivated,
    ),
  };
};

export const updateFeatures = (data) => {
  return {
    type: FEATURES_SAVE,
    payload: merchantFetch({
      url: `merchants/me/features`,
      method: 'post',
      data,
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

export const uploadRectLogo = (file, fieldName) => {
  const formData = new FormData();
  formData.append(fieldName, file);
  formData.append('isRectangularLogo', true);
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

export const uploadLogo = (file, fieldName) => {
  const formData = new FormData();
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
export const removeLogo = (payload) => {
  return {
    type: REMOVE_LOGO,
    payload: merchantFetch({
      url: 'account/config/logo',
      method: 'delete',
      data: payload,
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
    logoUrl = `https://cdn.razorpay.com${logoUrl.replace(/\.([^.]+$)/, '_medium.$1')}`;
  }
  config.logo_url = logoUrl;

  return config;
};

export const fetchLateAuthConfig = () => {
  return {
    type: FETCH_LATE_AUTH_CONFIG,
    payload: merchantFetch('payment/config/late_auth?is_default=1'),
  };
};

export const createLateAuthConfig = (payload, method) => {
  return {
    type: CREATE_LATE_AUTH_CONFIG,
    payload: merchantFetch({
      url: `payment/config`,
      method,
      data: payload,
    }),
  };
};

export const fetchMerchantMOPLSubscription = () => {
  return {
    type: FETCH_MERCHANT_MOPL_SUBSCRIPTION,
    payload: merchantFetch({ url: `payment_links/subscriptions`, method: 'GET' }),
  };
};

export const fetchMOPLPlans = () => {
  return {
    type: FETCH_MOPL_PLANS,
    payload: merchantFetch({ url: `payment_links/plans`, method: 'GET' }),
  };
};

export const fetchInsights = (month) => {
  return {
    type: FETCH_INSIGHTS,
    payload: merchantFetch({ url: `payment_links/insights?month=${month}`, method: 'GET' }),
  };
};

export const fetchFeatureStatus = (currentUserId, featureName) => {
  return {
    type: FETCH_FEATURE_STATUS,
    payload: merchantFetch(`feature/merchant/${currentUserId}/${featureName}`),
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

// start updateEmailSettings

export const updateEmailSettings = (data) => {
  return {
    type: CONFIG_SAVE_EMAIL,
    payload: merchantFetch({
      url: 'account/config/email',
      method: 'post',
      data,
    }),
  };
};

// end updateEmailSettings

const fetchBannerState = async () => {
  const { data } = await merchantFetch({
    url: 'international_enablement/visibility',
    method: 'GET',
  });
  const status = computeBannerState(data);
  const res = { ...data, ...status };
  return res;
};

// Start of Checkout EmailLess Config

export const EmailLessCheckoutConfigOptions = {
  NO: 'no',
  OPTIONAL: 'optional',
  REQUIRED: 'required',
};

export const CHECKOUT_EMAIL_FEATURE_FLAG = {
  EMAIL_OPTIONAL_ON_CHECKOUT: 'email_optional_oncheckout',
  SHOW_EMAIL_ON_CHECKOUT: 'show_email_on_checkout',
};

export function getEmailConfigFlags(featureFlags) {
  let emailShown = false;
  let emailOptional = false;
  featureFlags.forEach(({ feature, value }) => {
    if (feature === CHECKOUT_EMAIL_FEATURE_FLAG.SHOW_EMAIL_ON_CHECKOUT) {
      emailShown = value;
    }
    if (feature === CHECKOUT_EMAIL_FEATURE_FLAG.EMAIL_OPTIONAL_ON_CHECKOUT) {
      emailOptional = value;
    }
  });
  return { emailShown, emailOptional };
}

function computeFeatureFlagsForCheckoutEmailConfig(featureFlags) {
  const { emailShown, emailOptional } = getEmailConfigFlags(featureFlags);
  if ((!emailOptional && !emailShown) || (!emailShown && emailOptional)) {
    return EmailLessCheckoutConfigOptions.NO;
  }
  if (emailShown && emailOptional) {
    return EmailLessCheckoutConfigOptions.OPTIONAL;
  }
  return EmailLessCheckoutConfigOptions.REQUIRED;
}

// End of Checkout EmailLess Config

export const fetchInternationalSettingStatus = () => {
  return {
    type: FETCH_INTERNATIONAL_SETTING_STATUS,
    payload: fetchBannerState(),
  };
};

export const fetchFeatureByName = ({ userId, feature }) => {
  return {
    type: FETCH_FEATURE_BY_NAME,
    resource: {
      feature,
    },
    payload: merchantFetch(`feature/merchant/${userId}/${feature}`),
  };
};

const initialState = {
  loading: true,
  error: null,
  ticketsRaisedByAgents: {
    loading: false,
    data: {
      1: [],
    },
    tickets: [],
  },
  refund_pricing: {
    rules: [],
    custom_pricing: true,
    not_loaded: true,
  },
  config: {},
  locale: null,
  isBrandColorDark: false,
  features: [],
  missed_order_payment_link: {
    subscription: { loading: true, data: {}, error: null },
    plans: { loading: true, data: {}, error: null },
    insights: { loading: true, data: {}, error: null },
  },
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
  internationalSettingStatus: {
    loading: true,
    data: {},
    error: null,
  },
  email_config: EmailLessCheckoutConfigOptions.NO,
  featureStatusConfig: {
    loading: true,
    data: {},
    error: null,
  },
};

const defaultLocale = {
  config: {
    language_code: 'en',
  },
  name: '_',
};

const configReducer = (state = initialState, action) => {
  switch (action.type) {
    case `${FEATURES_FETCH}::PENDING`:
      return set(state, 'loading', true);

    case `${FEATURES_FETCH}::SUCCESS`:
      return merge(state, {
        loading: false,
        features: action.payload.data.features,
        email_config: computeFeatureFlagsForCheckoutEmailConfig(action.payload.data.features),
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

    case `${FETCH_LATE_AUTH_CONFIG}::SUCCESS`: {
      // only show default configs
      const filteredItems = action.payload.data.items.filter((item) => item.is_default === true);

      return set(state, 'lateAuthConfig', {
        loading: false,
        data: {
          ...action.payload.data,
          items: filteredItems,
        },
        error: null,
      });
    }

    case `${FETCH_LATE_AUTH_CONFIG}::ERROR`:
      return set(state, 'lateAuthConfig', {
        loading: false,
        data: {},
        error: action.payload.errors,
      });

    case `${FETCH_FEATURE_STATUS}::SUCCESS`: {
      return set(state, 'lateAuthConfig', {
        loading: false,
        data: action.payload.data,
        error: null,
      });
    }

    case `${FETCH_FEATURE_STATUS}::ERROR`:
      return set(state, 'lateAuthConfig', {
        loading: false,
        data: {},
        error: action.payload.errors,
      });

    case `${FETCH_MERCHANT_MOPL_SUBSCRIPTION}::SUCCESS`: {
      return set(state, 'missed_order_payment_link.subscription', {
        loading: false,
        data: action.payload.data,
        error: null,
      });
    }

    case `${FETCH_MERCHANT_MOPL_SUBSCRIPTION}::ERROR`:
      return set(state, 'missed_order_payment_link.subscription', {
        loading: false,
        data: {},
        error: action.payload.errors,
      });

    case `${FETCH_MOPL_PLANS}::SUCCESS`: {
      return set(state, 'missed_order_payment_link.plans', {
        loading: false,
        data: action.payload.data,
        error: null,
      });
    }

    case `${FETCH_MOPL_PLANS}::ERROR`:
      return set(state, 'missed_order_payment_link.plans', {
        loading: false,
        data: {},
        error: action.payload.errors,
      });

    case `${FETCH_INSIGHTS}::SUCCESS`: {
      return set(state, 'missed_order_payment_link.insights', {
        loading: false,
        data: action.payload.data,
        error: null,
      });
    }

    case `${FETCH_INSIGHTS}::ERROR`:
      return set(state, 'missed_order_payment_link.insights', {
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
    case `${CONFIG_SAVE_EMAIL}::SUCCESS`:
    case `${MERCHANT_LOGO_UPLOADED}::SUCCESS`:
      return set(state, 'config', normalizeConfig(action.payload.data));
    case `${REMOVE_LOGO}::SUCCESS`:
      return set(state, 'config', normalizeConfig(action.payload.data));
    case `${LOCALE_FETCH}::SUCCESS`: {
      const locale = action.payload.data.items[0];
      return set(state, 'locale', locale || defaultLocale);
    }

    case `${LOCALE_UPDATE}`:
      return set(state, 'locale', {
        ...state.locale,
        config: {
          language_code: action.payload,
        },
      });
    case EMAIL_CONFIG_UPDATE:
      return set(state, 'email_config', action.payload);
    case `${LOCALE_SAVE}::SUCCESS`:
      return set(state, 'locale', action.payload.data);

    case `${FEATURES_SAVE}::SUCCESS`:
      return set(state, 'features', action.payload.data.features);

    case `${GET_ONBOARDING_STATUS}::SUCCESS`:
      return set(state, 'paypal_terminals', action.payload.data);

    case 'UPDATE_BRAND_COLOR_CONTRAST':
      return set(state, 'isBrandColorDark', !!action.payload);

    case `${FETCH_SUPPORT_TICKETS}::PENDING`: {
      const support_tickets = deepClone(state.support_tickets);
      support_tickets.loading = true;
      return merge(state, {
        support_tickets,
      });
    }

    case `${FETCH_TICKET_RAISED_BY_AGENTS}::SUCCESS`: {
      const STATE = deepClone(state.ticketsRaisedByAgents);
      STATE.data[action.payload.query.page] = action.payload.data;
      STATE.loading = false;
      return merge(state, {
        ticketsRaisedByAgents: STATE,
      });
    }

    case `${FETCH_SUPPORT_TICKETS}::SUCCESS`: {
      const st = deepClone(state.support_tickets);
      st.data[action.payload.query.page] = action.payload.data;
      st.loading = false;
      return merge(state, {
        support_tickets: st,
      });
    }

    case `${FETCH_SUPPORT_TICKETS}::ERROR`: {
      const S = deepClone(state.support_tickets);
      S.loading = false;
      S.error = true;
      return merge(state, {
        support_tickets: S,
      });
    }

    case `${FETCH_ACTIVE_TICKETS}::SUCCESS`: {
      const ST = deepClone(state.support_tickets);
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
    }

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

    case `${FETCH_INTERNATIONAL_SETTING_STATUS}::PENDING`:
      return merge(state, {
        internationalSettingStatus: {
          loading: true,
          ...state.internationalSettingStatus,
        },
      });

    case `${FETCH_INTERNATIONAL_SETTING_STATUS}::SUCCESS`:
      return set(state, 'internationalSettingStatus', {
        loading: false,
        data: action.payload,
        error: null,
      });

    case `${FETCH_INTERNATIONAL_SETTING_STATUS}::ERROR`:
      return set(state, 'internationalSettingStatus', {
        loading: false,
        data: {},
        error: action.payload.errors,
      });

    case `${FETCH_FEATURE_BY_NAME}::PENDING`: {
      return set(state, 'featureStatusConfig', {
        ...state.featureStatusConfig,
        loading: true,
      });
    }

    case `${FETCH_FEATURE_BY_NAME}::SUCCESS`: {
      const {
        resource: { feature },
        payload: {
          data: { status },
        },
      } = action;
      return set(state, 'featureStatusConfig', {
        loading: false,
        data: {
          ...state.featureStatusConfig.data,
          [feature]: status,
        },
        error: null,
      });
    }

    case `${FETCH_FEATURE_BY_NAME}::ERROR`: {
      return set(state, 'featureStatusConfig', {
        loading: false,
        data: {
          ...state.featureStatusConfig.data,
        },
        error: action.payload.errors,
      });
    }
    case SYNCHRONIZE_EMAIL_SETTINGS_ON_GRAPHQL_EMAIL_MUTATION_UPDATE_SUCCESS: {
      const { transaction_report_email } = action.payload;

      return {
        ...state,
        config: {
          ...state.config,
          transaction_report_email,
        },
      };
    }

    default:
      return state;
  }
};

export default configReducer;
