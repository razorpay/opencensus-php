import errorService from '@razorpay/universe-cli/errorService';
import { Teams, Ranks } from 'common/new-ui/ErrorBoundary';
import loadScript from 'merchant/views/Growth/StreaksReferralIncentiveProgram/LoadScript';

export const loadCustomerGluSdk = (): void => {
  if (window.glu) return;

  window['gluConfig'] = {
    writeKey: window.STREAKS_REWARDS,
    userIdentification: {
      userId: window.rzp_user?.current,
    },
    userAttributes: {
      gluAttributes: {}, //add CG reserved user properties here (key-value pair)
      customAttributes: {}, //add all your custom user properties here (key-value pair)
    },
    onLoadError: function (error) {
      errorService.captureError(error, {
        tags: {
          team: Teams.PLATFORM_GROWTH,
        },
        rank: Ranks.P2,
      });
    },
  };
  loadScript();
};
