import { sendToLumberjack } from 'common/utils/analytics';
import { getCommonSegmentProperties } from 'common/utils/rzp-utils';

export const trackOptimizerEvents = ({
  objectName,
  actionName,
  properties = {},
  screen = 'Optimizer',
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

export const trackAPIResults = ({
  name,
  properties,
}: {
  name: string;
  properties: Record<string, any>;
}) => {
  trackOptimizerEvents({
    objectName: `${name} API`,
    actionName: 'result',
    properties,
    screen: `Optimizer ${name}`,
  });
};
