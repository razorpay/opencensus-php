import ajax, { merchantFetch } from 'merchant/utils/ajax';
import User, { ORG_CUSTOM_CODE_MAP } from 'merchant/models/User';
import { set, merge } from 'common/utils/immutable';
import { titleCase } from 'common/utils/rzp-utils';

const UPDATE_SESSION = 'UPDATE_SESSION';
const UPDATE_USER_ASYNC = 'UPDATE_USER_ASYNC';
const UPDATE_USER = 'UPDATE_USER';
const UPDATE_USER_FEATURES = 'UPDATE_USER_FEATURES';
const UPDATE_USER_CAMPAIGNS = 'UPDATE_USER_CAMPAIGNS';
const UPDATE_USER_TAGS = 'UPDATE_USER_TAGS';
const UPDATE_I18N_TAGS = 'UPDATE_I18N_TAGS';
const FETCH_PRICING_CONFIG = 'FETCH_PRICING_CONFIG';
const USER_FETCH = 'USER_FETCH';
const ORG_FETCH = 'ORG_FETCH';
export const USER_LOGOUT = 'USER_LOGOUT';
const SHOW_HIDE_TOUR = 'SHOW_HIDE_TOUR';
const UPDATE_USER_SEGMENT_DATA = 'UPDATE_USER_SEGMENT_DATA';
const UPDATE_MERCHANT = 'UPDATE_MERCHANT';
const TOGGLE_HELP_WIDGET = 'TOGGLE_HELP_WIDGET';

const UPDATE_HIGHLIGHT_MODE = 'UPDATE_HIGHLIGHT_MODE';

export const updateSession = (payload) => {
  return {
    type: UPDATE_SESSION,
    payload,
  };
};

export const updateMerchant = (payload) => {
  return {
    type: UPDATE_MERCHANT,
    payload,
  };
};

export const fetchUser = () => {
  const user = new User();

  return {
    type: USER_FETCH,
    payload: user.fetch(),
  };
};

export const updateUserFeatures = (FEATURE, isEnabled) => {
  return {
    type: UPDATE_USER_FEATURES,
    data: {
      FEATURE,
      isEnabled,
    },
  };
};

export const fetchUserTags = () => {
  return {
    type: UPDATE_USER_TAGS,
    payload: ajax({
      url: `/merchant/tags`,
      appendModeInURL: false,
    }),
  };
};

export const fetchConfigTags = (countryCode) => {
  return {
    type: UPDATE_I18N_TAGS,
    payload: merchantFetch({
      url: `country/${countryCode}/dashboard/configs`,
      method: 'GET',
    }),
  };
};

export const fetchPricingConfig = (pricingPlanId) => {
  return {
    type: FETCH_PRICING_CONFIG,
    payload: merchantFetch({
      url: `nocodeapps/pricing/plan/${pricingPlanId}`,
      method: 'GET',
    }),
  };
};

export const updateUser = (data) => {
  return {
    type: UPDATE_USER,
    data,
  };
};

export const fetchOrg = () => {
  return {
    type: ORG_FETCH,
    payload: ajax({
      url: '/org',
      appendModeInURL: false,
    }),
  };
};

export const switchMerchant = (merchantId) => {
  return () => {
    return ajax({
      url: `/settings/merchants/switch/${merchantId}`,
      appendModeInURL: false,
    });
  };
};

export const logout = () => {
  return {
    type: USER_LOGOUT,
    payload: ajax({
      method: 'post',
      url: '/user/logout',
      appendModeInURL: false,
    }),
  };
};

export const fetchUserDetailsById = (userId) => {
  return merchantFetch({
    url: `users/fetch_for_merchant/${userId}`,
  });
};

export const fetchCampaignsAjax = () => {
  const params = {
    url: 'credits?fetch_expired=0&is_promotion=1',
    method: 'GET',
  };

  return merchantFetch(params);
};

export const fetchCampaigns = () => {
  return {
    type: UPDATE_USER_CAMPAIGNS,
    payload: fetchCampaignsAjax(),
  };
};

export const showOrHideTour = (toShowTour) => {
  return {
    type: SHOW_HIDE_TOUR,
    toShowTour,
  };
};

export const showOrHideHighlightMode = (value) => {
  return {
    type: UPDATE_HIGHLIGHT_MODE,
    payload: value,
  };
};

export const updateUserSegmentData = (segmentData) => {
  return {
    type: UPDATE_USER_SEGMENT_DATA,
    payload: segmentData,
  };
};

export const toggleHelpWidget = ({ showWidget }) => {
  return {
    type: TOGGLE_HELP_WIDGET,
    payload: showWidget,
  };
};

export const initialState = {
  user: new User(),
  org: {
    security_branding_logo: 'https://cdn.razorpay.com/static/assets/pay_methods_branding.png',
  },
  mode: 'test',
  partnerMode: 'test',
  modeFormatted: 'Test',
  partnerModeFormatted: 'Test',
  highlightMode: true,
  isTourVisible: false,
  isUsingPartnerMode: false,
  user_segment_data: null,
  isTagsLoaded: false,
  isHelpWidgetVisible: true,
};

const updateOrg = (data) => {
  return {
    ...data,
    /*
      security_branding_logo is not available for all of the orgs, so we added the fall back.
      All of the orgs, expect the curlec org is belongs to india, so default security branding logo is applicable for everyone.
    */
    isjkOrg: data.custom_code === ORG_CUSTOM_CODE_MAP.JAMMU_KASHMIR_BANK,
    security_branding_logo: data.security_branding_logo || initialState.org.security_branding_logo,
  };
};

export default function sessionReducer(state = initialState, action) {
  switch (action.type) {
    case UPDATE_SESSION:
      return merge(state, {
        ...action.payload,
        modeFormatted: titleCase(action.payload.mode || state.mode),
        partnerModeFormatted: titleCase(action.payload.partnerMode || state.partnerMode),
        org: updateOrg(action.payload.org || state.org),
      });

    // when action involves async API call
    case `${UPDATE_USER_ASYNC}::SUCCESS`:
      return onUpdateUser(state, action.payload.data);

    case UPDATE_USER:
      return onUpdateUser(state, action.data);

    case `${UPDATE_USER_TAGS}::SUCCESS`:
      // in lot of other places rzp_user is getting directly used to update the session
      // we have to update the user object with the tags
      window.rzp_user = {
        ...window.rzp_user,
        tags: action?.payload?.data || [],
      };

      return merge(state, {
        user: new User({
          ...state.user,
          tags: action?.payload?.data || [],
        }),
        isTagsLoaded: true,
      });

    case `${UPDATE_USER_TAGS}::ERROR`:
      return merge(state, {
        user: new User({
          ...state.user,
          tags: [],
        }),
        isTagsLoaded: true,
      });

    case `${UPDATE_I18N_TAGS}::SUCCESS`:
      window.rzp_user = {
        ...window.rzp_user,
        configTags: action?.payload?.data?.UIControls || {},
      };

      return merge(state, {
        user: new User({
          ...state.user,
          configTags: action?.payload?.data?.UIControls || {},
        }),
      });

    case `${FETCH_PRICING_CONFIG}::SUCCESS`:
      return merge(state, {
        user: new User({
          ...state.user,
          pricing_plan_config: action?.payload?.data || {
            nocodeapp_pricing_applicable: false,
            nocodeapp_percent_rate: '',
          },
        }),
      });

    case `${UPDATE_I18N_TAGS}::ERROR`:
      return merge(state, {
        user: new User({
          ...state.user,
          configTags: {},
        }),
      });

    case `${FETCH_PRICING_CONFIG}::ERROR`:
      return merge(state, {
        user: new User({
          ...state.user,
          pricing_plan_config: {
            nocodeapp_pricing_applicable: false,
            nocodeapp_percent_rate: '',
          },
        }),
      });

    case UPDATE_USER_FEATURES:
      return onUpdateUserFeatures(state, action.data);

    case UPDATE_HIGHLIGHT_MODE:
      return merge(state, {
        highlightMode: action.payload,
      });

    case `${USER_FETCH}::SUCCESS`:
      return set(state, 'user', action.payload.data);

    case `${USER_FETCH}::ERROR`:
    case `${USER_LOGOUT}::SUCCESS`:
      return set(state, 'user', new User());

    case `${ORG_FETCH}::SUCCESS`:
      return set(state, 'org', updateOrg(action.payload.data));

    case UPDATE_USER_SEGMENT_DATA:
      return merge(state, {
        user_segment_data: action.payload,
      });

    case UPDATE_MERCHANT:
      return onUpdateMerchant(state, action.payload);

    case `${UPDATE_USER_CAMPAIGNS}::SUCCESS`: {
      const campaigns = action.payload?.data?.items?.map((item) => item?.campaign) || [];
      // in other places rzp_user is getting used to update the session so updating with campaigns
      window.rzp_user = {
        ...window.rzp_user,
        campaigns,
      };

      return merge(state, {
        user: new User({
          ...state.user,
          campaigns,
        }),
      });
    }

    case TOGGLE_HELP_WIDGET:
      return merge(state, {
        isHelpWidgetVisible: action.payload,
      });

    default:
      return state;
  }
}

function onUpdateUser(state, data) {
  return merge(state, {
    user: new User({
      ...state.user,
      user: {
        ...state.user.user,
        ...data,
      },
    }),
  });
}

function onUpdateUserFeatures(state, data) {
  return merge(state, {
    user: new User({
      ...state.user,
      features: state.user.features.map((featureData) => {
        if (featureData.feature === data.FEATURE) {
          return {
            ...featureData,
            value: data.isEnabled,
          };
        }

        return featureData;
      }),
    }),
  });
}

function onUpdateMerchant(state, data) {
  return merge(state, {
    user: new User({
      ...state.user,
      merchant: { ...state.user.merchant, ...data },
    }),
  });
}
