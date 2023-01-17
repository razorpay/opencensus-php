import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

function _track() {
  function sendToSegment(objectName, actionName, properties, toCleverTap = false) {
    analyticsTrack({
      objectName,
      actionName,
      screen: 'Widget Onboarding Screen',
      toCleverTap,
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
        ...properties,
      },
      toLumberjack: true,
    });
  }

  return {
    productDetailsRender: () => {
      sendToSegment('product details', 'render');
    },
    continue: () => {
      sendToSegment('product continue', 'click');
    },
    widgetTabClicked: () => {
      sendToSegment('widget tab', 'click', null);
    },
    widgetTabRender: (payload) => {
      sendToSegment('widget tab', 'render', payload);
    },
    websitePlatformRender: (platforms) => {
      sendToSegment('website platform', 'render', { platforms });
    },
    platformSelect: (platform) => {
      sendToSegment('website platform', 'click', { platform });
    },
    setupPage: (platform) => {
      sendToSegment('widget setup page', 'render', { platform });
    },
    enableWidget: (source) => {
      sendToSegment('enable widget', 'click', { source });
    },
    enableConfirmRender: (source) => {
      sendToSegment('enable widget confirm', 'render', { source });
    },
    enableConfirm: (source) => {
      sendToSegment('enable widget confirm', 'click', { source });
    },
    setupGuide: (source) => {
      sendToSegment('view setup guide', 'click', { source });
    },
  };
}

export default _track();
