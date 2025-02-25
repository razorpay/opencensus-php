import trackEvents from '../../js/analytics';
import {
  sendSignInFailureEvent,
  sendSignInInitiatedEvent,
  sendSignInSuccessEvent,
} from '../../js/signInAnalytics';

const twoFactorPasswordAuthEvents = {
  trackPageLoad: () => {
    sendSignInSuccessEvent('display_2fa_password');
  },

  trackInitiate: () => {
    trackEvents.prometheus({ type: 'new_login', label: 'login_initiate_2fa' });
    sendSignInInitiatedEvent('2fa_password');
  },

  trackSuccess: () => {
    trackEvents.prometheus({ type: 'new_login', label: 'login_success_2fa' });
    sendSignInSuccessEvent('2fa_password');
  },

  trackFailure: (error) => {
    sendSignInFailureEvent('2fa_password', { error });
  },
};
export default twoFactorPasswordAuthEvents;
