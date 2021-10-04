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

  function sendToSegment(objectName, actionName, properties) {
    analyticsTrack({
      objectName,
      actionName,
      screen: 'payment link',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
        ...properties,
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
  };
}

export default _track();
