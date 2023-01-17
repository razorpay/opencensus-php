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

  const payload = {
    source: 'widget_details_dashboard',
  };

  return {
    offerNudge: () => {
      sendToSegment('offer nudge', 'render', payload);
    },
    paymentOptionNudge: () => {
      sendToSegment('payment options nudge', 'render', payload);
    },
    widgetBenefitsRender: () => {
      sendToSegment('widget benefits', 'render', payload);
    },
    planDetailsRender: () => {
      sendToSegment('widget plan details', 'render', payload);
    },
    customizeWidget: () => {
      sendToSegment('view widget customization guide', 'click', payload);
    },
    disableWidget: () => {
      sendToSegment('widget disable', 'click', payload);
    },
    disableWidgetConfirmRender: () => {
      sendToSegment('widget disable confirm', 'render', payload);
    },
    disableWidgetConfirm: () => {
      sendToSegment('widget disable confirm', 'click', payload);
    },
    requestHelp: () => {
      sendToSegment('request help', 'click', payload);
    },
    nudge: (type) => {
      sendToSegment(`${type} nudge`, 'click', payload);
    },
  };
}

export default _track();
