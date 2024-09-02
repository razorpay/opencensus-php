import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

function analytics() {
  const default_screen = 'subscriptions';

  let lumberjackTrack = () => {};

  function sendToLumberjack(event, options) {
    lumberjackTrack(
      window.rzpQ.interaction(event, {
        options,
      }),
    );
  }

  function sendToSegment(objectName, actionName, options) {
    analyticsTrack({
      objectName,
      actionName,
      screen: options?.screen || default_screen,
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
        ...options,
        section: default_screen,
      },
    });
  }

  return {
    init: function init(_lumberjack) {
      lumberjackTrack = _lumberjack;
    },
    track: function track(eventLabel, options = {}, eventAction = 'click') {
      const newOptions = {
        mode: 'dashboard',
        ...options,
      };
      const segmentLabel = eventLabel.split(/_|\./).join(' ');
      sendToLumberjack(eventLabel, newOptions);
      sendToSegment(segmentLabel, eventAction, { ...newOptions });
    },

    tourPageRendered: () => {
      sendToSegment('Tour page', 'rendered');
    },

    readMoreClickedOnTourPage: () => {
      sendToSegment('read more', 'clicked');
    },
  };
}

export default analytics();
