import { setTrackData } from 'rzp/utils/googleAnalytics';

const track = setTrackData({
  eventCategory: 'Dashboard - Side Nav',
  eventLabel: 'from Sidebar Banner',
});

export const trackGoToActivation = goToForm =>
  track({
    eventAction: `Go To - ${goToForm || 'Activation form'}`,
  });

export const trackGoToConfig = showInstantActivation =>
  track({
    eventAction: `Go To - Config${
      showInstantActivation ? ' | Instant Activation' : ''
    }`,
  });
