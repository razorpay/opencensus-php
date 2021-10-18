import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

function analytics() {
  let lumberjackTrack = () => {};

  function sendToLumberjack(event, options) {
    lumberjackTrack(
      window.rzpQ.subscription().interaction(event, {
        options,
      }),
    );
  }

  function sendToSegment(objectName, actionName, options) {
    analyticsTrack({
      objectName,
      actionName,
      screen: options.screen || 'subscriptions',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
        ...options,
      },
    });
  }

  return {
    init: function init(_lumberjack) {
      lumberjackTrack = _lumberjack;
    },
    track: function track(eventLabel, options, eventAction = 'click') {
      const segmentLabel = eventLabel.split(/_|\./).join(' ');
      sendToLumberjack(eventLabel, options);
      sendToSegment(segmentLabel, eventAction, options);
    },
  };
}

export default analytics();
