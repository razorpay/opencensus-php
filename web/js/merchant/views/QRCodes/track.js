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
