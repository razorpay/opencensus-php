import ajax from 'merchant/utils/ajax';
import { merchantFetch } from 'merchant/utils/ajax';
import User from 'merchant/models/User';
import { set, merge } from 'common/utils/immutable';
import { titleCase } from 'common/utils/rzp-utils';

const UPDATE_SESSION = 'UPDATE_SESSION';
const UPDATE_USER_ASYNC = 'UPDATE_USER_ASYNC';
const UPDATE_USER = 'UPDATE_USER';
const USER_FETCH = 'USER_FETCH';
const ORG_FETCH = 'ORG_FETCH';
export const USER_LOGOUT = 'USER_LOGOUT';
const SHOW_HIDE_TOUR = 'SHOW_HIDE_TOUR';

export const updateSession = (payload) => {
  return {
    type: UPDATE_SESSION,
    payload,
  };
};

export const fetchUser = () => {
  let user = new User();

  return {
    type: USER_FETCH,
    payload: user.fetch(),
  };
};

export const updateUserFeatures = (FEATURE, enable) => {
  updateUser({
    ...this.props.user,
    features: this.props.user.features.map((featureData) => {
      if (featureData.feature === FEATURE) {
        return {
          ...featureData,
          value: enable,
        };
      }

      return featureData;
    }),
  });
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

let initialState = {
  user: new User(),
  org: {},
  mode: 'test',
  modeFormatted: 'Test',
  isTourVisible: false,
};

export default function (state = initialState, action) {
  switch (action.type) {
    case UPDATE_SESSION:
      return merge(state, {
        ...action.payload,
        modeFormatted: titleCase(action.payload.mode),
      });

    // when action involves async API call
    case `${UPDATE_USER_ASYNC}::SUCCESS`:
      return onUpdateUser(state, action.payload.data);

    case UPDATE_USER:
      return onUpdateUser(state, action.data);

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
