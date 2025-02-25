import {
  sendSignInInitiatedEvent,
  sendSignInFailureEvent,
  sendSignInSuccessEvent,
} from '../../js/signInAnalytics';

const forgotPasswordEvents = {
  trackSubmitInitiate: () => {
    sendSignInInitiatedEvent('send_password_reset_link', { action: 'send reset link' });
  },

  trackSubmitSuccess: () => {
    sendSignInSuccessEvent('send_password_reset_link', { action: 'send reset link' });
  },

  trackSubmitError: ({ error }) => {
    sendSignInFailureEvent('send_password_reset_link', { action: 'send reset link', error });
  },
};
export default forgotPasswordEvents;
