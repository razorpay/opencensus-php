import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

export function trackLJ(event, options) {
  // console.log(`qr.${event}`, options)
  window.rzpQ.qrCode().interaction(`qr.${event}`, options);
}

export function trackSegment({ event, screen, actionName, options = {} }) {
  if (!event) return;
  // console.log({
  //   objectName: event.replace(/_/g, '.'),
  //   actionName: 'clicked',
  //   screen: screen,
  //   properties: {
  //     ...getCommonAnalyticsProperties(window.rzp_user),
  //     ...options,
  //   },
  // });

  analyticsTrack({
    objectName: event.replace(/_/g, '.'),
    actionName: 'clicked',
    screen: screen,
    properties: {
      ...getCommonAnalyticsProperties(window.rzp_user),
      ...options,
    },
  });
}
