import { setTrackData } from 'rzp/utils/googleAnalytics';

export const track = setTrackData({
  eventCategory: 'LA Dashboard - Header',
  eventLabel: 'from Test Mode Banner',
});

export const trackLinkClick = eventAction =>
  track({
    eventAction,
  });
