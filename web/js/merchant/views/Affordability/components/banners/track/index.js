import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

function _track() {
  function sendToSegment(objectName, actionName, properties, toCleverTap = false) {
    analyticsTrack({
      objectName,
      actionName,
      screen: 'Widget Plan Details Screen',
      toCleverTap,
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
        ...properties,
      },
      toLumberjack: true,
    });
  }

  return {
    widgetLiveBanner: () => {
      sendToSegment('widget live banner', 'render', {
        source: 'widget_details_dashboard',
      });
    },
    widgetDisableBanner: () => {
      sendToSegment('widget disabled banner', 'render', {
        source: 'widget_details_dashboard',
      });
    },
    specialOffer: (payload) => {
      sendToSegment('widget discount offer banner', 'render', payload);
    },
    trialPeriod: (payload) => {
      sendToSegment('free trial banner', 'render', payload);
    },
  };
}

export default _track();
