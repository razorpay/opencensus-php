import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

export default function sendToSegment(
  objectName,
  actionName,
  properties,
  section,
  subSection,
  toCleverTap = false,
) {
  analyticsTrack({
    objectName,
    actionName,
    toCleverTap,
    properties: {
      ...getCommonAnalyticsProperties(window.rzp_user),
      ...properties,
      section,
      subSection,
    },
  });
}
