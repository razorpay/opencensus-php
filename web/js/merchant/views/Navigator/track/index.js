import { sendToLumberjack } from 'common/utils/analytics';
import { getCommonSegmentProperties } from 'common/utils/rzp-utils';

export const trackOptimizerEvents = ({
  objectName,
  actionName,
  properties = {},
  screen = 'Optimizer',
}) => {
  sendToLumberjack({
    eventName: `${objectName} ${actionName}`.split(' ').join('.').toLowerCase(),
    properties: {
      ...getCommonSegmentProperties(window.rzp_user, { addUserProperties: true }),
      ...properties,
      screen,
      eventTimestamp: new Date().toISOString(),
    },
  });
};

export const trackAPIResutls = ({ name, properties }) => {
  trackOptimizerEvents({
    objectName: `${name} API`,
    actionName: 'result',
    properties,
    screen: `Optimizer ${name}`,
  });
};
