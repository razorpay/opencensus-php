import ajax from 'merchant/utils/ajax';
import User from 'merchant/models/User';
import { set, merge } from 'rzp/utils/immutable';
import { titleCase } from 'rzp/utils/rzp-utils';

const UPDATE_SESSION = 'UPDATE_SESSION';
const USER_FETCH = 'USER_FETCH';
const ORG_FETCH = 'ORG_FETCH';
export const USER_LOGOUT = 'USER_LOGOUT';
const SHOW_HIDE_TOUR = 'SHOW_HIDE_TOUR';

export const updateSession = payload => {
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

export const fetchOrg = () => {
  return {
    type: ORG_FETCH,
    payload: ajax({
      url: '/admin/org',
      appendModeInURL: false,
    }),
  };
};

export const switchMerchant = merchantId => {
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
      url: '/user/logout',
      appendModeInURL: false,
    }),
  };
};

export const submitFeedback = data => {
  return () => {
    return ajax({
      url: '/sendfeedback',
      method: 'post',
      appendModeInURL: false,
      data,
    });
  };
};

export const showOrHideTour = toShowTour => {
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

export default function(state = initialState, action) {
  switch (action.type) {
    case UPDATE_SESSION:
      return merge(state, {
        ...action.payload,
        modeFormatted: titleCase(action.payload.mode),
      });

    case `${USER_FETCH}::SUCCESS`:
      return set(state, 'user', action.payload.data);

    case `${USER_FETCH}::ERROR`:
    case `${USER_LOGOUT}::SUCCESS`:
      return set(state, 'user', new User());

    case `${ORG_FETCH}::SUCCESS`:
      return set(state, 'org', action.payload.data);

    case 'SHOW_HIDE_TOUR':
      return set(state, 'isTourVisible', action.toShowTour);

    default:
      return state;
  }
}
