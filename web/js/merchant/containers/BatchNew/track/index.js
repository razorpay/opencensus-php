import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties, titleCase } from 'common/utils/rzp-utils';

function _track() {
  // setting screen to default value incase it's not passed from callee
  function sendToSegment(objectName, actionName, screen = 'Batch uploads', properties) {
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
  // TODO: batch type missing in few products. Check and add those batch type align with products
  return {
    downloadSample: (feature) => {
      sendToSegment('download sample file', 'click', feature ? titleCase(feature) : 'dashboard');
    },
    viewDocumentation: (feature) => {
      sendToSegment('view documentation', 'click', feature ? titleCase(feature) : 'dashboard');
    },
    batchUpload: (feature) => {
      sendToSegment('batch upload', 'click', feature ? titleCase(feature) : 'dashboard');
    },
  };
}

export default _track();
