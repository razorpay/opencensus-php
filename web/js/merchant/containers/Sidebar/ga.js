import { setTrackData } from 'rzp/utils/googleAnalytics';

const track = setTrackData({
  eventCategory: 'Dashboard - Side Nav',
  eventLabel: 'from Sidebar Banner',
});

export const trackGoToActivation = () =>
  track({
    eventAction: 'Go To - Activation form',
  });

export const trackGoToConfig = () =>
  track({
    eventAction: 'Go To - Config',
  });
