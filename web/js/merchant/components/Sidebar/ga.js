import { setTrackData } from 'common/utils/googleAnalytics';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { getKycAnalyticsProperties } from 'merchant/views/RazorpayXWidget/helpers';

const track = setTrackData({
  eventCategory: 'Dashboard - Side Nav',
  eventLabel: 'from Sidebar Banner',
});

export const trackGoToActivation = (goToForm) =>
  track({
    eventAction: `Go To - ${goToForm || 'Activation form'}`,
  });

export const trackGoToConfig = (showInstantActivation) =>
  track({
    eventAction: `Go To - Config${showInstantActivation ? ' | Instant Activation' : ''}`,
  });

export const trackViewedBankingNavBar = () => {
  analyticsTrack({
    objectName: 'Banking Left Nav Bar PG',
    actionName: 'Viewed',
    screen: 'home page',
    properties: {
      ...getCommonAnalyticsProperties(window.rzp_user),
      ...getKycAnalyticsProperties(),
    },
  });
};
