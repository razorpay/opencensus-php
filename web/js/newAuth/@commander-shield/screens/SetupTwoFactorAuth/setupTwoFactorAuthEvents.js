import {
  sendSignInFailureEvent,
  sendSignInInitiatedEvent,
  sendSignInSuccessEvent,
} from '../../js/signInAnalytics';

const setupTwoFactorAuthEvents = {
  trackPageLoad: ({ email }) => {
    sendSignInSuccessEvent('display_2fa_change_mobile_number', { emailId: email });
  },

  trackSetupTwoFactorInitiate: ({ email }) => {
    sendSignInInitiatedEvent('2fa_change_mobile_number', { emailId: email });
  },

  trackSetupTwoFactorSuccess: ({ email }) => {
    sendSignInSuccessEvent('2fa_change_mobile_number', { emailId: email });
  },

  trackSetupTwoFactorFailure: ({ email, error }) => {
    sendSignInFailureEvent('2fa_change_mobile_number', { emailId: email, error });
  },
};
export default setupTwoFactorAuthEvents;
