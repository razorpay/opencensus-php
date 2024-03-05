import { set, merge, remove, unshift } from '../immutable';

const NOTIFICATION_SHOW = 'NOTIFICATION_SHOW';
const NOTIFICATION_HIDE = 'NOTIFICATION_HIDE';

export const showNotification = (payload) => {
  return {
    type: NOTIFICATION_SHOW,
    payload: {
      hidePrevious: true,
      ...payload,
      id: +new Date(),
    },
  };
};

export const hideNotification = (payload) => {
  return {
    type: NOTIFICATION_HIDE,
    payload,
  };
};

const initialState = {
  notifications: [],
};

export default (state = initialState, action) => {
  switch (action.type) {
    case NOTIFICATION_SHOW:
      return merge(state, {
        notifications: unshift(state.notifications, action.payload),
        hidePrevious: state.notifications.length >= 1 ? action.payload.hidePrevious : false,
      });

    case NOTIFICATION_HIDE:
      return set(
        state,
        'notifications',
        remove(state.notifications, (notification) => notification.id === action.payload.id),
      );
    default:
      return state;
  }
};
