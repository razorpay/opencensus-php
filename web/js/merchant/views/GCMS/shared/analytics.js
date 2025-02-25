import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

const SESSION_ID = window?.localStorage.getItem('analytics_session_id') ?? 'not available';
const VALIDITY = window?.localStorage.getItem('_uetvid_exp') ?? 'not available';
export const track = ({ properties, ...args }) => {
  try {
    analyticsTrack({
      ...args,
      properties: {
        location: 'GCMS',
        sessionId: SESSION_ID,
        validity: VALIDITY,
        ...getCommonAnalyticsProperties(window.rzp_user),
        ...properties,
      },
    });
    return null;
  } catch (error) {
    throw new Error(error?.response?.errors?.[0]);
  }
};
