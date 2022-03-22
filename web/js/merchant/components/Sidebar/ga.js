import { setTrackData } from 'common/utils/googleAnalytics';
import { analyticsTrack } from 'common/utils/analytics';

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
  });
};
