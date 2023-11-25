import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { Track } from './types';

export const track = ({
  objectName,
  actionName = 'Clicked',
  screen = 'Account n Settings',
  properties,
}: Track) => {
  analyticsTrack({
    objectName,
    actionName,
    screen,
    properties: {
      version: 'v2',
      ...getCommonAnalyticsProperties(window.rzp_user, { addUserProperties: true }),
      ...properties,
    },
  });
};
