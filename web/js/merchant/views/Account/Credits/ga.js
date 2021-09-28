import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

export const clickHistoryCreditsGA = (creditType) => {
  return {
    screen: 'Dashboard - My Account (Credits)',
    actionName: 'View History',
    objectName: `View History - ${creditType}`,
    properties: {
      ...getCommonAnalyticsProperties(window.rzp_user),
    },
  };
};

export const CLICK_KNOW_MORE_MANAGE_ALERTS = {
  screen: 'Dashboard - My Account (Credits)',
  actionName: 'Know more',
  objectName: `Know more - Manage Alerts`,
  properties: {
    ...getCommonAnalyticsProperties(window.rzp_user),
  },
};

export const CLICK_ON_MANAGE_ALERTS = {
  screen: 'Dashboard - My Account (Credits)',
  actionName: 'Click - Manage Alerts',
  objectName: `Manage Alerts`,
  properties: {
    ...getCommonAnalyticsProperties(window.rzp_user),
  },
};

export const CLICK_CANCEL_MANAGE_ALERTS = {
  screen: 'Dashboard - My Account (Credits)',
  objectName: `Manage Alerts`,
  actionName: 'Cancel',
  properties: {
    ...getCommonAnalyticsProperties(window.rzp_user),
  },
};

export const CLICK_SAVE_MANAGE_ALERTS = {
  screen: 'Dashboard - My Account (Credits)',
  actionName: 'Save',
  objectName: `Amount Limit | Fee Limit | Refund Limit`,
  properties: {
    ...getCommonAnalyticsProperties(window.rzp_user),
  },
};

export const CLICK_ADD_FEE_CREDITS = {
  screen: 'Dashboard - My Account (Credits)',
  actionName: 'Click - Add Fee Credits',
  objectName: `Add Fee Credits`,
  properties: {
    ...getCommonAnalyticsProperties(window.rzp_user),
  },
};

export const CLICK_ADD_REFUND_CREDITS = {
  screen: 'Dashboard - My Account (Credits)',
  actionName: 'Click - Add Refund Credits',
  objectName: `Add Refund Credits`,
  properties: {
    ...getCommonAnalyticsProperties(window.rzp_user),
  },
};

export const FEE_CREDITS_ADDED = {
  screen: 'Dashboard - My Account (Credits)',
  actionName: 'Fee Credits Added',
  objectName: 'Fee Credits Added',
  properties: {
    ...getCommonAnalyticsProperties(window.rzp_user),
  },
};

export const REFUND_CREDITS_ADDED = {
  screen: 'Dashboard - My Account (Credits)',
  actionName: 'Refund Credits Added',
  objectName: 'Refund Credits Added',
  properties: {
    ...getCommonAnalyticsProperties(window.rzp_user),
  },
};

export const FEE_CREDITS_FAILED = {
  screen: 'Dashboard - My Account (Credits)',
  actionName: 'Fee Credits Failed',
  objectName: 'Fee Credits Failed',
  properties: {
    ...getCommonAnalyticsProperties(window.rzp_user),
  },
};

export const REFUND_CREDITS_FAILED = {
  screen: 'Dashboard - My Account (Credits)',
  actionName: 'Refund Credits Failed',
  objectName: 'Refund Credits Failed',
  properties: {
    ...getCommonAnalyticsProperties(window.rzp_user),
  },
};

export const OPEN_DOCUMENTATION = {
  screen: 'Dashboard - My Account (Credits)',
  objectName: 'documentation',
  actionName: 'clicked',
  properties: {
    location: 'credits',
    ...getCommonAnalyticsProperties(window.rzp_user),
  },
};
