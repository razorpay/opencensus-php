import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

interface TrackEvent {
  objectName: string;
  actionName?: string;
  screen?: string;
  properties?: Record<string, unknown>;
}

export const trackEvent = ({
  objectName,
  actionName = 'Clicked',
  screen = 'Risk and Fraud',
  properties,
}: TrackEvent): void => {
  analyticsTrack({
    objectName,
    actionName,
    screen,
    properties: {
      page: 'Risk and Fraud',
      ...properties,
      ...getCommonAnalyticsProperties(window.rzp_user, { addUserProperties: true }),
    },
  });
};
