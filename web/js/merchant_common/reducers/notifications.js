// This utility is used to show and hide notifications
// This is directly updating zustand store for notification actions

import { useStore } from 'shell/commonStore';

import {
  getShowNotificationState,
  getHideNotificationState,
} from 'merchant/commonStore/stateActions/notifications';

const NOTIFICATION_SHOW = 'NOTIFICATION_SHOW';
const NOTIFICATION_HIDE = 'NOTIFICATION_HIDE';

const ZUSTAND_STORE_KEY = 'notifications';

// Keeping the action response as it as to support
// existing redux flow in order to prevent any breaking changes

export const showNotification = (payload) => {
  useStore.setState((state) => {
    return {
      [ZUSTAND_STORE_KEY]: getShowNotificationState(state[ZUSTAND_STORE_KEY], payload),
    };
  });
  return {
    type: NOTIFICATION_SHOW,
    payload,
  };
};

export const hideNotification = (payload) => {
  useStore.setState((state) => {
    return {
      [ZUSTAND_STORE_KEY]: getHideNotificationState(state[ZUSTAND_STORE_KEY], payload),
    };
  });
  return {
    type: NOTIFICATION_HIDE,
    payload,
  };
};

// deprecated not in use anymore
// TODO: remove this in future, keeping this for reference for now
// export default (state = notificationsInitialState, action) => {
//   switch (action.type) {
//     case NOTIFICATION_SHOW:
//       return handleShowNotification(state, action);

//     case NOTIFICATION_HIDE:
//       return handleHideNotification(state, action);

//     default:
//       return state;
//   }
// };
