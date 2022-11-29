import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

const SCREEN = 'Home Page';

export const trackCTAClick = (cta, props) => {
  analyticsTrack({
    objectName: `Dashboard ${cta}`,
    actionName: 'Clicked',
    screen: SCREEN,
    properties: {
      ...getCommonAnalyticsProperties(window?.rzp_user),
      ...props,
    },
  });
};
