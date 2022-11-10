import { getCommonSegmentProperties } from 'common/utils/rzp-utils';
import analyticsService from '@razorpay/commander-services/analytics';
import errorService from '@razorpay/universe-utils/errorService';
import { Teams, Ranks } from 'common/new-ui/ErrorBoundary';

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
    analyticsService.track({
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
