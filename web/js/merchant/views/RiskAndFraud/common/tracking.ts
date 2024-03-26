import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

import { RiskFraudPagesMap } from './constant';

export const track = ({
  objectName,
  actionName = 'Clicked',
  screen = 'Transactions',
  properties,
}) => {
  analyticsTrack({
    objectName,
    actionName,
    screen,
    properties: {
      version: 'v2',
      page: 'Transactions',
      ...properties,
      ...getCommonAnalyticsProperties(window.rzp_user, { addUserProperties: true }),
    },
  });
};

export const trackTabClick = (pathname) => () =>
  track({
    objectName: 'Transactions Tab',
    properties: {
      tabName: RiskFraudPagesMap[pathname],
    },
  });
