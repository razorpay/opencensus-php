import trackEvents from '../../js/analytics';
import {
  sendSignInFailureEvent,
  sendSignInInitiatedEvent,
  sendSignInSuccessEvent,
} from '../../js/signInAnalytics';

const twoFactorAuthEvents = {
  trackInitiate: ({ email }) => {
    trackEvents.prometheus({ type: 'new_login', label: 'login_initiate_2fa' });
    sendSignInInitiatedEvent('2fa_otp', { emailId: email });
  },
  trackSuccess: ({ email }) => {
    trackEvents.prometheus({ type: 'new_login', label: 'login_success_2fa' });
    sendSignInSuccessEvent('2fa_otp', { emailId: email });
  },

  trackFailure: ({ email, error }) => {
    sendSignInFailureEvent('2fa_otp', { emailId: email, error });
  },

  trackResendOtpInitiate: ({ email }) => {
    sendSignInInitiatedEvent('2fa_resend_otp', { emailId: email });
  },

  trackResendOtpSuccess: ({ email }) => {
    sendSignInSuccessEvent('2fa_resend_otp', { emailId: email });
  },

  trackResendOtpFailure: ({ email, error }) => {
    sendSignInFailureEvent('2fa_resend_otp', { emailId: email, error });
  },
};
export default twoFactorAuthEvents;
