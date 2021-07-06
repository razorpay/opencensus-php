import { setTrackData } from 'common/utils/googleAnalytics';

export const track = setTrackData({
  eventCategory: 'Dashboard - Header',
  eventLabel: 'from Test Mode Banner',
});

export const trackLinkClick = (eventAction) =>
  track({
    eventAction,
  });
