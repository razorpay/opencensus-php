import { sendSignInInitiatedEvent, sendSignInSuccessEvent } from '../../js/signInAnalytics';

const accountBlockEvents = {
  trackInitiated: ({ email, method, flow }) => {
    sendSignInInitiatedEvent('account_lock', { emailId: email, method, flow });
  },

  trackSuccess: ({ email, method }) => {
    sendSignInSuccessEvent('account_block', { emailId: email, method });
  },
};
export default accountBlockEvents;
