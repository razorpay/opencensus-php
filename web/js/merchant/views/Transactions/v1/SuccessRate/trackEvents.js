import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { getUser } from 'merchant/store';
import { trackOptimizerEvents } from 'merchant/views/Navigator/track';

export const trackSuccessRateEvents = (payload) => {
  const user = getUser();
  const version = 'v2';
  if (payload) {
    payload.properties = {
      ...payload.properties,
      version,
    };
  }
  user.isOptimizerEnabled
    ? trackOptimizerEvents(payload)
    : analyticsTrack({
        ...payload,
        properties: {
          ...(payload?.properties || {}),
          ...getCommonAnalyticsProperties(window.rzp_user),
          version,
        },
      });
};

export const visitSuccessRate = (params) => ({
  objectName: 'transactions tab',
  actionName: 'clicked',
  screen: 'transactions',
  properties: params,
});

export const filterSuccessRate = (filter) => ({
  screen: 'Transactions - Success Rate',
  actionName: 'Click',
  objectName: `Success rate search filter`,
  properties: {
    filter,
  },
});

export const clearFilterSuccessRate = (filter) => ({
  screen: 'Transactions - Success Rate',
  actionName: 'Click',
  objectName: `Success rate clear filter`,
  properties: {
    filter,
  },
});

export const methodTabClick = (params) => ({
  screen: 'Transactions - Success Rate',
  actionName: 'Click',
  objectName: `Success rate method tab`,
  properties: params,
});

export const methodTagsClick = (params) => ({
  screen: 'Transactions - Success Rate',
  actionName: 'Click',
  objectName: 'Success rate method tag',
  properties: params,
});

export const methodIntervalClick = (params) => ({
  screen: 'Transactions - Success Rate',
  actionName: 'Click',
  objectName: `Success rate method interval`,
  properties: params,
});

export const methodFailureReasonClick = (params) => ({
  screen: 'Transactions - Success Rate',
  actionName: 'Click',
  objectName: `Success rate method failure reason tab`,
  properties: params,
});

export const methodDropdownChange = (params) => ({
  objectName: `Success rate dropdown filter`,
  actionName: 'Change',
  screen: 'Transactions - Success Rate',
  properties: params,
});

export const needHelpFaq = (params) => ({
  objectName: `Success rate - Need help?`,
  actionName: 'Click',
  screen: 'Transactions - Success Rate',
  properties: params,
});

export const downloadSRGraphReport = (params) => ({
  objectName: `Success rate - Download SR Report`,
  actionName: 'Click',
  screen: 'Transactions - Success Rate',
  properties: params,
});
