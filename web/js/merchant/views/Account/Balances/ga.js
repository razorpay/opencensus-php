import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

export const CLICK_ADD_FUNDS_ON_CURRENT_BALANCE = {
  screen: 'Dashboard - My Account (Balance)',
  actionName: 'Click - Current Balance Add Funds',
  objectName: `Add Funds`,
  properties: {
    ...getCommonAnalyticsProperties(window.rzp_user),
  },
};

export const CLICK_ADD_FUNDS_ON_RESERVE_BALANCE = {
  screen: 'Dashboard - My Account (Balance)',
  actionName: 'Click - Reserve Balance Add Funds',
  objectName: `Add Funds`,
  properties: {
    ...getCommonAnalyticsProperties(window.rzp_user),
  },
};

export const CURRENT_BALANCE_SUCCESS = {
  screen: 'Dashboard - My Account (Balance)',
  actionName: 'Current Balance Added',
  properties: {
    ...getCommonAnalyticsProperties(window.rzp_user),
  },
};

export const RESERVE_BALANCE_SUCCESS = {
  screen: 'Dashboard - My Account (Balance)',
  actionName: 'Reserve Balance Added',
  properties: {
    ...getCommonAnalyticsProperties(window.rzp_user),
  },
};

export const CURRENT_BALANCE_FAILURE = {
  screen: 'Dashboard - My Account (Balance)',
  actionName: 'Current Balance Failed',
  properties: {
    ...getCommonAnalyticsProperties(window.rzp_user),
  },
};

export const RESERVE_BALANCE_FAILURE = {
  screen: 'Dashboard - My Account (Balance)',
  actionName: 'Reserve Balance Failed',
  properties: {
    ...getCommonAnalyticsProperties(window.rzp_user),
  },
};

export const OPEN_DOCUMENTATION = {
  screen: 'Dashboard - My Account (Balance)',
  actionName: 'Click - Know More',
  objectName: `Know more`,
  properties: {
    ...getCommonAnalyticsProperties(window.rzp_user),
  },
};
