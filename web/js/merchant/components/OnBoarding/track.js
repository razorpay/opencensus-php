import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties, titleCase } from 'common/utils/rzp-utils';

const FAILED = 'FAILED';

function _track() {
  let lumberjackTrack = () => {};

  function sendToLumberjack(eventName, event, data) {
    if (event === FAILED) {
      lumberjackTrack(
        window.rzpQ.productOnboarding().failed(eventName, {
          data,
        }),
      );
      return;
    }
    // default case: success
    lumberjackTrack(
      window.rzpQ.productOnboarding().success(eventName, {
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
    onBoardingSuccess: (featureName) => {
      sendToLumberjack(`${featureName}.onboarding.start`);
      sendToSegment(
        `${titleCase(featureName)} onboarding start`,
        'click',
        `${featureName} onboarding screen`,
      );
    },

    introductionNextSuccess: (featureName, eventType) => {
      sendToLumberjack(`${featureName}.onboarding.introduction_next`);
      sendToSegment(
        `${titleCase(featureName)} onboarding introduction next`,
        eventType || 'click',
        `${featureName} onboarding screen`,
      );
    },

    onFeatureBack: (featureName, eventType) => {
      sendToLumberjack(`${featureName}.onboarding.features_back`);
      sendToSegment(
        `${titleCase(featureName)} onboarding features back success`,
        eventType || 'click',
        `${featureName} onboarding screen`,
      );
    },

    featuresHyperlink: (featureName, eventType) => {
      sendToLumberjack(`${featureName}.onboarding.features_hyperlink`);
      sendToSegment(
        `${titleCase(featureName)} onboarding features hyperlink`,
        eventType || 'click',
        `${featureName} onboarding screen`,
      );
    },

    onBoardingInitiated: (featureName, eventType) => {
      sendToLumberjack(`${featureName}.onboarding.get_started.initiated`);
      sendToSegment(
        `${titleCase(featureName)} onboarding get started initiated`,
        eventType || 'initiated',
        `${featureName} onboarding screen`,
      );
    },

    onBoardingGetSuccess: (featureName, eventType) => {
      sendToLumberjack(`${featureName}.onboarding.get_started`);
      sendToSegment(
        `${titleCase(featureName)} onboarding get started success`,
        eventType || 'click',
        `${featureName} onboarding screen`,
      );
    },

    onBoardingGetFailed: (featureName, eventType) => {
      sendToLumberjack(`${featureName}.onboarding.get_started`, FAILED);
      sendToSegment(
        `${titleCase(featureName)} onboarding get started failure`,
        eventType || 'click',
        `${featureName} onboarding screen`,
      );
    },

    init(_lumberjackTrack) {
      lumberjackTrack = _lumberjackTrack;
    },
  };
}

export default _track();
