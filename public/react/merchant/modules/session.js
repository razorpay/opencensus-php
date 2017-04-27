import ajax from 'merchant/utils/ajax';
import { set, merge } from 'rzp/utils/immutable';
import { titleCase } from 'rzp/utils/rzp-utils';

const UPDATE_SESSION = 'UPDATE_SESSION';
const USER_FETCH = 'USER_FETCH';

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

    default:
      return state;
  }
}
