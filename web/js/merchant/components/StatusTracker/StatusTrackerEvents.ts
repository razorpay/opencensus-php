import errorService from '@razorpay/universe-utils/errorService';

import { Teams, Ranks } from 'common/new-ui/ErrorBoundary';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonSegmentProperties } from 'common/utils/rzp-utils';

type TrackerParameter = {
  objectName: string;
  actionName: string;
  screen: string;
  properties?: Record<string, any>;
};
export const trackSegmentEvent = ({
  objectName,
  actionName,
  screen,
  properties = {},
}: TrackerParameter): void => {
  try {
    analyticsTrack({
      objectName,
      actionName,
      screen,
      properties: {
        ...getCommonSegmentProperties(),
        ...properties,
      },
    });
  } catch (error) {
    errorService.captureError(error, {
      tags: {
        team: Teams.PLATFORM_GROWTH,
      },
      rank: Ranks.P2,
    });
  }
};
