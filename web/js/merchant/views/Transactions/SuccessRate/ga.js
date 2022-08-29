import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

export const filterSuccessRate = (filter) => ({
  screen: 'Transactions - Success Rate',
  actionName: 'Click - Filter success rate',
  objectName: `Search`,
  properties: {
    filter,
    ...getCommonAnalyticsProperties(window.rzp_user),
  },
});

export const clearFilterSuccessRate = (filter) => ({
  screen: 'Transactions - Success Rate',
  actionName: 'Click - Clear filter success rate',
  objectName: `Clear`,
  properties: {
    filter,
    ...getCommonAnalyticsProperties(window.rzp_user),
  },
});

export const methodTabClick = (params) => ({
  screen: 'Transactions - Success Rate',
  actionName: 'Click - Method tab',
  objectName: `Success rate method tab`,
  properties: {
    ...params,
    ...getCommonAnalyticsProperties(window.rzp_user),
  },
});

export const methodTagsClick = (params) => ({
  screen: 'Transactions - Success Rate',
  actionName: 'Click - Method tag',
  objectName: `Sucess rate method tag`,
  properties: {
    ...params,
    ...getCommonAnalyticsProperties(window.rzp_user),
  },
});

export const methodIntervalClick = (params) => ({
  screen: 'Transactions - Success Rate',
  actionName: 'Click - Method interval',
  objectName: `Sucess rate method interval`,
  properties: {
    ...params,
    ...getCommonAnalyticsProperties(window.rzp_user),
  },
});

export const methodFailureReasonClick = (params) => ({
  screen: 'Transactions - Success Rate',
  actionName: 'Click - Method failure reason',
  objectName: `Sucess rate method failure reason tab`,
  properties: {
    ...params,
    ...getCommonAnalyticsProperties(window.rzp_user),
  },
});
