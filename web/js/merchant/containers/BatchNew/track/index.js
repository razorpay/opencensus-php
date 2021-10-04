import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties, titleCase } from 'common/utils/rzp-utils';

function _track() {
  function sendToSegment(objectName, actionName, screen, properties) {
    analyticsTrack({
      objectName,
      actionName,
      screen,
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
        ...properties,
      },
    });
  }

  return {
    downloadSample: (feature) => {
      sendToSegment('download sample file', 'click', titleCase(feature));
    },
    viewDocumentation: (feature) => {
      sendToSegment('view documentation', 'click', titleCase(feature));
    },
    batchUpload: (feature) => {
      sendToSegment('batch upload', 'click', titleCase(feature));
    },
  };
}

export default _track();
