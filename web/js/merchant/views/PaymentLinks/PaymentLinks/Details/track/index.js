import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

function _track() {
  const lumberjackTrack = () => {};

  function sendToLumberjack(eventName, data) {
    lumberjackTrack(
      window.rzpQ.paymentLinks().interaction(eventName, {
        data,
      }),
    );
  }

  function sendToSegment(objectName, actionName, properties) {
    analyticsTrack({
      objectName,
      actionName,
      screen: 'Create Payment Link',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
        ...properties,
      },
    });
  }

  return {
    resendStart: () => {
      sendToSegment('payment link resend start', 'click');
      sendToLumberjack('pl.resend.start', {
        origin: 'dashboard',
      });
    },
    resendClose: () => {
      sendToSegment('payment link resend close', 'click');
      sendToLumberjack('pl.resend.close', {
        origin: 'dashboard',
      });
    },
    resendIssue: () => {
      sendToSegment('merchant resend', 'click');
      sendToLumberjack('pl.resend.issue', {
        origin: 'dashboard',
      });
    },
    resendSuccess: (close) => {
      const property = {
        origin: 'dashboard',
        close,
      };
      sendToSegment('resend success toast', 'click', property);
      sendToLumberjack('pl.resend.issue.success', property);
    },
  };
}

export default _track();
