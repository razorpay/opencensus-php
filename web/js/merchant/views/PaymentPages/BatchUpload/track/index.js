import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties, humanize } from 'common/utils/rzp-utils';

const commonProp = {
  mode: 'batch',
};

function _track() {
  function sendToSegment(objectName, actionName, properties) {
    return analyticsTrack({
      objectName,
      actionName,
      screen: 'Create Batch Payment Page',
      toLumberjack: true,
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
        ...commonProp,
        ...properties,
      },
    });
  }

  return {
    donwloadSampleInModal: () => {
      sendToSegment('Payment Page create sample modal', 'clicked');
    },

    onDocumentClickInModal: () => {
      sendToSegment('Payment Page create docs modal', 'clicked');
    },

    uploadClicked: () => {
      sendToSegment('Payment Page create dropupload modal', 'clicked');
    },

    fileUploadError: () => {
      sendToSegment('Payment Page create dropupload modal error', 'clicked');
    },

    fileUploadSuccess: () => {
      sendToSegment('Payment Page create dropupload modal success', 'clicked');
    },

    onFileNameTrack: () => {
      sendToSegment('Payment Page batch create name', 'clicked');
    },

    onSmSNotify: () => {
      sendToSegment('Payment Page batch create sms', 'clicked');
    },

    onEmailNotify: () => {
      sendToSegment('Payment Page batch create email', 'clicked');
    },

    onPreview: () => {
      sendToSegment('Payment Page batch create preview table', 'onMouseEnter');
    },

    abandonBatchModal: () => {
      sendToSegment('Payment Page batch create abandon', 'clicked');
    },

    createBatch: () => {
      sendToSegment('Payment Page batch create issue', 'clicked');
    },

    successModalClose: () => {
      sendToSegment('Payment Page batch create close success', 'clicked');
    },

    batchDetailViewOnLoad: () => {
      sendToSegment('Payment Page batch detail view load', 'clicked');
    },

    onDetailViewUnMount: () => {
      sendToSegment('Payment Page batch detail close', 'clicked');
    },

    onDetailsView: () => {
      sendToSegment('Payment Page batch detail view load', 'clicked');
    },

    viewAllClick: () => {
      sendToSegment('Payment Page batch detail view all', 'clicked');
    },

    batchIdChange: () => {
      sendToSegment('Payment Page batch search batch id change', 'clicked');
    },

    batchSearchCount: () => {
      sendToSegment('Payment Page batch search count', 'clicked');
    },

    onSearchAnalytics: () => {
      sendToSegment('Payment Page batch search confirm', 'clicked');
    },

    onClearAnalytics: () => {
      sendToSegment('Payment Page batch search clear', 'clicked');
    },

    onPagination: (page, type) => {
      const prop = {
        page,
      };
      sendToSegment(`Payment Page batch browse ${humanize(type)}`, 'clicked', prop);
    },
  };
}

export default _track();
