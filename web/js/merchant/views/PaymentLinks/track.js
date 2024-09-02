import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

function _track() {
  let lumberjackTrack = () => {};

  function sendToLumberjack(event, data) {
    lumberjackTrack(
      window.rzpQ.paymentLinks().interaction(event, {
        data,
      }),
    );
  }

  const default_screen = 'payment link';
  const screen_name = 'Payment Links Tour';

  function sendToSegment(objectName, actionName, properties, screen = default_screen) {
    analyticsTrack({
      objectName,
      actionName,
      screen,
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
        ...properties,
        section: screen_name,
      },
    });
  }

  return {
    onBoardingSuccess: () => {
      sendToLumberjack('payment_links.onboarding.start.success');
      sendToSegment('payment links onboarding start', 'click');
    },

    onOnBoardinglandingNext: () => {
      sendToSegment('onboarding introduction next success', 'click');
    },

    init(_lumberjackTrack) {
      lumberjackTrack = _lumberjackTrack;
    },

    tourPageRendered: () => {
      sendToSegment('Tour page', 'rendered', {}, screen_name);
    },

    readMoreClickedOnTourPage: () => {
      sendToSegment('read more', 'clicked', {}, screen_name);
    },

    getStartedClickedOnTourPage: () => {
      sendToSegment('get started', 'clicked', {}, screen_name);
    },

    skipClickOnTourPage: () => {
      sendToSegment('skip section', 'clicked', {}, screen_name);
    },
  };
}

export default _track();
