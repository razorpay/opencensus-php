import ajax from 'merchant/utils/ajax';
import { set, merge } from 'rzp/utils/immutable';
import { titleCase } from 'rzp/utils/rzp-utils';

const UPDATE_SESSION = 'UPDATE_SESSION';
const USER_FETCH = 'USER_FETCH';
const ORG_FETCH = 'ORG_FETCH';
export const USER_LOGOUT = 'USER_LOGOUT';

export const updateSession = payload => {
  return dispatch => {
    return dispatch({
      type: UPDATE_SESSION,
      payload,
    });
  };
};

export const fetchUser = () => {
  return dispatch => {
    return dispatch({
      type: USER_FETCH,
      payload: ajax({
        url: '/user',
        appendModeInURL: false,
      }),
    });
  };
};

export const fetchOrg = () => {
  return dispatch => {
    return dispatch({
      type: ORG_FETCH,
      payload: ajax({
        url: '/admin/org',
        appendModeInURL: false,
      }),
    });
  };
};

export const switchMerchant = merchantId => {
  return dispatch => {
    return ajax({
      url: `/settings/merchants/switch/${merchantId}`,
      appendModeInURL: false,
    });
  };
};

export const logout = () => {
  return dispatch => {
    return dispatch({
      type: USER_LOGOUT,
      payload: ajax({
        url: '/user/logout',
        appendModeInURL: false,
      }),
    });
  };
};

let initialState = {
  user: null,
  org: {},
  mode: 'test',
  modeFormatted: 'Test',
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
      return set(state, 'user', null);

    case `${ORG_FETCH}::SUCCESS`:
      return set(state, 'org', action.payload.data);

    default:
      return state;
  }
}
