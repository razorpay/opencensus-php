import ajax, { merchantFetch } from 'merchant/utils/ajax';
import User from 'merchant/models/User';
import { set, merge } from 'common/utils/immutable';
import { titleCase } from 'common/utils/rzp-utils';

const UPDATE_SESSION = 'UPDATE_SESSION';
const UPDATE_USER_ASYNC = 'UPDATE_USER_ASYNC';
const UPDATE_USER = 'UPDATE_USER';
const UPDATE_USER_FEATURES = 'UPDATE_USER_FEATURES';
const USER_FETCH = 'USER_FETCH';
const ORG_FETCH = 'ORG_FETCH';
export const USER_LOGOUT = 'USER_LOGOUT';
const SHOW_HIDE_TOUR = 'SHOW_HIDE_TOUR';

const UPDATE_HIGHLIGHT_MODE = 'UPDATE_HIGHLIGHT_MODE';

export const updateSession = (payload) => {
  return {
    type: UPDATE_SESSION,
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

const initialState = {
  user: new User(),
  org: {},
  mode: 'test',
  partnerMode: 'test',
  modeFormatted: 'Test',
  partnerModeFormatted: 'Test',
  highlightMode: true,
  isTourVisible: false,
  isUsingPartnerMode: false,
};

export default function sessionReducer(state = initialState, action) {
  switch (action.type) {
    case UPDATE_SESSION:
      return merge(state, {
        ...action.payload,
        modeFormatted: titleCase(action.payload.mode || state.mode),
        partnerModeFormatted: titleCase(action.payload.partnerMode || state.partnerMode),
      });

    // when action involves async API call
    case `${UPDATE_USER_ASYNC}::SUCCESS`:
      return onUpdateUser(state, action.payload.data);

    case UPDATE_USER:
      return onUpdateUser(state, action.data);

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
      return set(state, 'org', action.payload.data);

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
