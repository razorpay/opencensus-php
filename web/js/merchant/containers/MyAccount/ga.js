import { setTrackData } from 'rzp/utils/googleAnalytics';

export const track = setTrackData({
  eventCategory: 'Dashboard - My Account',
});

/**
 * Track link clicks.
 * @param {String} action
 * @return {Function}
 */
export const trackLinkClick = action => e =>
  track({
    eventAction: action,
  });
