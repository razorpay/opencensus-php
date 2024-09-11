import { User } from '@dashboard/shared-utils/typings';
import { analyticsTrack } from '@dashboard/shared-utils/analytics';
import { getCommonSegmentProperties } from '@dashboard/shared-utils/rzp-utils';
import { ANALYTICS_EVENTS, EventProperties, ANALYTICS_ACTIONS } from './types';

interface TrackEventsProps {
  eventName: ANALYTICS_EVENTS;
  action: ANALYTICS_ACTIONS;
  properties: EventProperties;
}

declare global {
  interface Window {
    rzp_user: User;
  }
}

const trackEvent = ({ eventName, action, properties }: TrackEventsProps): void => {
  analyticsTrack({
    objectName: eventName,
    actionName: action,
    properties: {
      ...getCommonSegmentProperties(window.rzp_user, { addUserProperties: true }),
      ...properties,
    },
    screen: window.location.pathname.split('/').pop(),
    toLumberjack: true,
  });
};

export default trackEvent;
