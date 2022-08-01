import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

export const trackBankAccountDetailsChange = ({
  objectName,
  actionName,
  screen = 'My Account',
  properties = {},
}) => {
  analyticsTrack({
    objectName,
    actionName,
    screen,
    properties: {
      ...getCommonAnalyticsProperties(window.rzp_user),
      ...properties,
    },
  });
};

export const getResponseTime = (startedAt) =>
  `${((new Date().getTime() - startedAt.getTime()) / 1000).toFixed(2)}s`;
