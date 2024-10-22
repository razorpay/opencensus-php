import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

function _track() {
  const section = 'Checkout Demo';
  const subSection = 'checkout preview';

  function sendToSegment(
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

  return {
    togglePreview: (preview) => {
      sendToSegment('toggle preview', 'click', { preview }, section, subSection);
    },
  };
}

export default _track();
