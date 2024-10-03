import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

export const createNewAccountTrack = () => {
  analyticsTrack({
    objectName: 'Create A New Account Button',
    actionName: 'Clicked',
    screen: '',
    properties: {
      version: 'v2',
      session_id: window.session_id,
      ...getCommonAnalyticsProperties(window.rzp_user, { addUserProperties: true }),
    },
  });
};
