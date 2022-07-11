import { analyticsTrack, getDeviceSource } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

const OBJECT_NAME = 'Self Serve';
const IS_LUMBER_JACK = true;
const InitiateAction = 'Initiated';
const SuccessAction = 'Success';

const instrumentAnalytics = ({ toLumberjack = false, action, selfServeAction, page, screen }) => {
  analyticsTrack({
    objectName: OBJECT_NAME,
    actionName: action,
    screen,
    properties: {
      selfServeAction,
      page,
      screen,
      source: getDeviceSource(),
      ...getCommonAnalyticsProperties(window.rzp_user),
    },
    toLumberjack,
  });
};

export const selfServeTrackInitiate = (properties) =>
  instrumentAnalytics({ toLumberjack: IS_LUMBER_JACK, action: InitiateAction, ...properties });

export const selfServeTrackSuccess = (properties) =>
  instrumentAnalytics({ toLumberjack: IS_LUMBER_JACK, action: SuccessAction, ...properties });
