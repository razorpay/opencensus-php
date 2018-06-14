import { setTrackData } from 'rzp/utils/googleAnalytics';

const eventCategory = 'Dashboard - Activation Form';

export const track = setTrackData({
  eventCategory,
});

/**
 * Track tab changes.
 * @param {String} tab
 */
export const trackTabChange = tab =>
  track({
    eventAction: 'Go to - Activation Tab',
    eventLabel: tab,
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
