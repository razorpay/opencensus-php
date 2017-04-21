import { set, merge, remove, unshift } from 'rzp/utils/immutable';

const NOTIFICATION_SHOW = 'NOTIFICATION_SHOW';
const NOTIFICATION_HIDE = 'NOTIFICATION_HIDE';

export const showNotification = payload => {
  return dispatch => {
    return dispatch({
      type: NOTIFICATION_SHOW,
      payload: {
        ...payload,
        id: +new Date(),
      },
    });
  };
};

export const hideNotification = payload => {
  return dispatch => {
    return dispatch({
      type: NOTIFICATION_HIDE,
      payload,
    });
  };
};

let initialState = {
  notifications: [],
};

export default (state = initialState, action) => {
  switch (action.type) {
    case NOTIFICATION_SHOW:
      return merge(state, {
        notifications: unshift(state.notifications, action.payload),
        hidePrevious: state.notifications.length >= 1
          ? action.payload.hidePrevious
          : false,
      });

    case NOTIFICATION_HIDE:
      let notifications = remove(
        state.notifications,
        notification => notification.id === action.payload.id
      );
      return set(state, 'notifications', notifications);

    default:
      return state;
  }
};
