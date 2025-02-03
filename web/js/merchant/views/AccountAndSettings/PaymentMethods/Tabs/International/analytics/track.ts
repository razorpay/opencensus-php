import { analyticsTrack } from 'common/utils/analytics';
import { getCommonSegmentProperties } from 'common/utils/rzp-utils';

export type CommonProperties = Record<string, string | number | boolean>;

export type AnalyticsProperties = {
  objectName: string;
  subSection?: string;
} & CommonProperties;

export type AnalyticsAction =
  | 'render'
  | 'page-view'
  | 'closed'
  | 'clicked'
  | 'response'
  | 'request'
  | 'success';

export const track = (actionName: AnalyticsAction, properties: AnalyticsProperties): void => {
  analyticsTrack({
    screen: 'Account & Settings',
    properties: {
      section: 'international payments settings',
      ...getCommonSegmentProperties(window.rzp_user),
      ...properties,
    },
    actionName,
    objectName: properties.objectName,
  });
};

export const trackPageView = (properties: CommonProperties) => {
  track('page-view', { objectName: 'international payments settings', ...properties });
};
