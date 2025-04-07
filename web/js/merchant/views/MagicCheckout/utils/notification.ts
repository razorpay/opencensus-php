import { showNotification } from '@libs/web-nexus/merchant/reducers/notifications';

export const notifySuccess = (message) => {
  showNotification({
    type: 'success',
    message,
  });
};
export const notifyError = (message) => {
  showNotification({
    type: 'error',
    message: message,
  });
};
