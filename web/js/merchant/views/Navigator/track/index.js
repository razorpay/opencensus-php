import { analyticsTrack } from 'common/utils/analytics';
import { getCommonSegmentProperties } from 'common/utils/rzp-utils';

export const trackOptimizerEvents = ({
  objectName,
  actionName,
  properties = {},
  screen = 'Optimizer',
}) => {
  analyticsTrack({
    objectName,
    actionName,
    properties: {
      ...getCommonSegmentProperties(window.rzp_user, { addUserProperties: true }),
      ...properties,
    },
    screen,
    toLumberjack: true,
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
