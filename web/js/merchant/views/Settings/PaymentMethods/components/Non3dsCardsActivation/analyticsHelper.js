// analytics
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

export const logAnalytics = (event, others = {}) => {
  const { actionName, ...properties } = others;

  const analytics = {
    objectName: event,
    screen: 'settings',
    actionName,
    properties: {
      location: 'Payment Methods',
      ...properties,
      ...getCommonAnalyticsProperties(window.rzp_user),
    },
  };

  analyticsTrack(analytics);
};
