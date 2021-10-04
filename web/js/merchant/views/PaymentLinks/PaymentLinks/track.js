import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties, titleCase } from 'common/utils/rzp-utils';

const FAILED = 'FAILED';

function _track() {
  let lumberjackTrack = () => {};

  function sendToLumberjack(eventName, event, data) {
    if (event === FAILED) {
      lumberjackTrack(
        window.rzpQ.paymentLinks().failed(eventName, {
          data,
        }),
      );
      return;
    }
    // default case: success
    lumberjackTrack(
      window.rzpQ.paymentLinks().success(eventName, {
        data,
      }),
    );
  }

  function sendToSegment(objectName, actionName, screen, properties) {
    analyticsTrack({
      objectName,
      actionName,
      screen,
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
        ...properties,
      },
    });
  }

  return {
    onNeedHelp: (featureName) => {
      sendToLumberjack(`${featureName}.need_help.clicked`);
      sendToSegment(`${titleCase(featureName)} need help`, 'clicked', `payment link`);
    },

    onDocumentClick: (featureName) => {
      sendToLumberjack(`${featureName}.documentation.clicked`);
      sendToSegment(`${titleCase(featureName)} doucmentation`, 'clicked', `payment link`);
    },

    init(_lumberjackTrack) {
      lumberjackTrack = _lumberjackTrack;
    },
  };
}

export default _track();
