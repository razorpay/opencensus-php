import { setTrackData } from 'common/utils/googleAnalytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

export const track = setTrackData({
  eventCategory: 'Dashboard - My Account',
});

export const CLICK_ON_CREDITS_TAB = {
  screen: 'Dashboard - My Account',
  actionName: 'Go to - Credits',
  objectName: `Credits`,
  properties: {
    ...getCommonAnalyticsProperties(window.rzp_user),
  },
};

export const CLICK_ON_BALANCES_TAB = {
  screen: 'Dashboard - My Account',
  actionName: 'Go to - Balance',
  objectName: `Balance`,
  properties: {
    ...getCommonAnalyticsProperties(window.rzp_user),
  },
};

/**
 * Track link clicks.
 * @param {String} action
 * @return {Function}
 */
export const trackLinkClick = (action) => (e) =>
  track({
    eventAction: action,
  });
