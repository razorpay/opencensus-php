import { set, merge, remove, unshift } from '@libs/shared-utils';

export const createNotificationPayload = (payload) => ({
  hidePrevious: true,
  ...payload,
  id: +new Date(),
});

// Extracted logic for showing notification
export const handleShowNotification = (state, payload) => {
  return merge(state, {
    notifications: unshift(state.notifications, payload),
    hidePrevious: state.notifications.length >= 1 ? payload.hidePrevious : false,
  });
};

// Extracted logic for hiding notification
export const handleHideNotification = (state, payload) => {
  const notifications = remove(
    state.notifications,
    (notification) => notification.id === payload.id,
  );
  return set(state, 'notifications', notifications);
};

export const getShowNotificationState = (state, initialPayload) => {
  const payload = createNotificationPayload(initialPayload);
  return handleShowNotification(state, payload);
};

export const getHideNotificationState = (state, payload) => {
  return handleHideNotification(state, payload);
};
