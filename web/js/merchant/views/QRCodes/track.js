import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties, humanize } from 'common/utils/rzp-utils';

export function trackLJ(event, options) {
  window.rzpQ.qrCode().interaction(`${event}`, options);
}

export function trackSegment({ event, screen, actionName, options = {} }) {
  if (!event) return;
  const segmentEventName = String(event).replace(/\./g, ' ');
  analyticsTrack({
    objectName: humanize(segmentEventName),
    actionName: actionName || 'clicked',
    screen,
    properties: {
      ...getCommonAnalyticsProperties(window.rzp_user),
      ...options,
    },
  });
}
function _track() {
  const default_screen = 'QR Code Page';

  function sendToSegment(objectName, actionName, properties, toCleverTap = false) {
    analyticsTrack({
      objectName,
      actionName,
      screen: default_screen,
      toCleverTap,
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
        ...properties,
        section: default_screen,
      },
    });
  }

  return {
    tourPageRendered: () => {
      sendToSegment('Tour page', 'rendered');
    },

    readMoreClickedOnTourPage: () => {
      sendToSegment('read more', 'clicked');
    },

    skipClickOnTourPage: () => {
      sendToSegment('skip section', 'clicked');
    },
  };
}

export default _track();
