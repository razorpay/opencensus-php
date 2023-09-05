import { analyticsTrackWithUserInfo, getDeviceSource } from 'common/utils/analytics';

const OBJECT_NAME = 'Self Serve';
const IS_LUMBER_JACK = true;
const InitiateAction = 'Initiated';
const SuccessAction = 'Success';

const instrumentAnalytics = ({
  toLumberjack = false,
  action,
  selfServeAction,
  page,
  screen,
  version,
  props = {},
}) => {
  analyticsTrackWithUserInfo({
    objectName: OBJECT_NAME,
    actionName: action,
    screen,
    properties: {
      selfServeAction,
      page,
      screen,
      source: getDeviceSource(),
      version,
      ...props,
    },
    toLumberjack,
  });
};

export const selfServeTrackInitiate = (properties) =>
  instrumentAnalytics({ toLumberjack: IS_LUMBER_JACK, action: InitiateAction, ...properties });

export const selfServeTrackSuccess = (properties) =>
  instrumentAnalytics({ toLumberjack: IS_LUMBER_JACK, action: SuccessAction, ...properties });
