import { sendToLumberjack } from 'common/utils/analytics';
import { getCommonSegmentProperties } from 'common/utils/rzp-utils';

export const trackOptimizerAccountEvents = ({
  objectName,
  actionName,
  properties = {},
  screen = 'Optimizer Accounts',
}: {
  objectName: string;
  actionName: string;
  properties?: Record<string, any>;
  screen?: string;
}) => {
  sendToLumberjack({
    eventName: `${objectName} ${actionName}`.split(' ').join('.').toLowerCase(),
    properties: {
      ...getCommonSegmentProperties(window.rzp_user, { addUserProperties: true }),
      ...properties,
      screen,
    },
  });
};
