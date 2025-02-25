import { analyticsTrack, getCommonSegmentProperties } from '@libs/shared-utils';
import { ANALYTICS_EVENTS, EventProperties, ANALYTICS_ACTIONS } from './types';

interface TrackEventsProps {
  eventName: ANALYTICS_EVENTS;
  action: ANALYTICS_ACTIONS;
  properties: EventProperties;
}

const trackEvent = ({ eventName, action, properties }: TrackEventsProps): void => {
  analyticsTrack({
    objectName: eventName,
    actionName: action,
    properties: {
      ...getCommonSegmentProperties(window.rzp_user, { addUserProperties: true }),
      ...properties,
    },
    screen: window.location.pathname.split('/').pop() || "",
    toLumberjack: true,
  });
};

export default trackEvent;
