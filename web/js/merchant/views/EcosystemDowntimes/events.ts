import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

export const trackEcosystemDowntimeEvents = (payload) => {
  analyticsTrack({
    ...payload,
    properties: {
      ...(payload?.properties || {}),
      ...getCommonAnalyticsProperties(window.rzp_user),
    },
  });
};

export const instrumentClick = (params = {}) => ({
  objectName: `Ecosystem Health Page - Instrument`,
  actionName: 'Click',
  screen: 'Transactions - Ecosystem Health',
  properties: params,
});

export const ecosystemHealthPageView = (params = {}) => ({
  objectName: `Ecosystem Health Page - List`,
  actionName: 'Viewed',
  screen: 'Transactions - Ecosystem Health',
  properties: params,
});
