import ajax, { merchantFetch } from 'merchantLA/utils/ajax';
import User from 'merchantLA/models/User';
import { merge, set } from 'common/utils/immutable';
import { titleCase } from 'common/utils/rzp-utils';

const UPDATE_SESSION = 'UPDATE_SESSION';
const USER_FETCH = 'USER_FETCH';
const ORG_FETCH = 'ORG_FETCH';
export const USER_LOGOUT = 'USER_LOGOUT';
const SHOW_HIDE_TOUR = 'SHOW_HIDE_TOUR';
const UPDATE_USER_TAGS = 'UPDATE_USER_TAGS';

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

export const fetchOrg = () => {
  return {
    type: ORG_FETCH,
    payload: ajax({
      url: '/org',
      appendModeInURL: false,
    }),
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

export const fetchFeaturesAjax = () => {
  const params = {
    url: `merchants/me/features`,
  };

  return merchantFetch(params);
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

export const showOrHideTour = (toShowTour) => {
  return {
    type: SHOW_HIDE_TOUR,
    toShowTour,
  };
};

const initialState = {
  user: new User(),
  org: {},
  mode: 'test',
  modeFormatted: 'Test',
  isTourVisible: false,
};

export default function sessionReducer(state = initialState, action) {
  switch (action.type) {
    case UPDATE_SESSION:
      return merge(state, {
        ...action.payload,
        modeFormatted: titleCase(action.payload.mode),
      });

    case `${USER_FETCH}::SUCCESS`:
      return set(state, 'user', new User(action.payload.data));

    case `${USER_FETCH}::ERROR`:
    case `${USER_LOGOUT}::SUCCESS`:
      return set(state, 'user', new User());

    case `${ORG_FETCH}::SUCCESS`:
      return set(state, 'org', action.payload.data);

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
      });

    case 'SHOW_HIDE_TOUR':
      return set(state, 'isTourVisible', action.toShowTour);

    default:
      return state;
  }
}
