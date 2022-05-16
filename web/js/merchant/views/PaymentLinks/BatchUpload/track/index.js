import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties, humanize } from 'common/utils/rzp-utils';

const commonProp = {
  mode: 'batch',
};

function _track() {
  let lumberjackTrack = () => {};

  function sendToLumberjack(eventName, data) {
    lumberjackTrack(
      window.rzpQ.paymentLinks().interaction(eventName, {
        ...commonProp,
        data,
      }),
    );
  }

  function sendToSegment(objectName, actionName, properties) {
    analyticsTrack({
      objectName,
      actionName,
      screen: 'Create Payment Link',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
        ...commonProp,
        ...properties,
      },
    });
  }

  return {
    donwloadSampleInModal: () => {
      sendToLumberjack('pl_batch.create.sample');
      sendToSegment('payment link create sample modal', 'clicked');
    },

    onDocumentClickInModal: () => {
      sendToLumberjack('pl_batch.create.docs');
      sendToSegment('payment link create docs modal', 'clicked');
    },

    uploadClicked: () => {
      sendToLumberjack('pl_batch.create.dropupload');
      sendToSegment('payment link create dropupload modal', 'clicked');
    },

    fileUploadError: () => {
      sendToLumberjack('pl_batch.create.preerror');
      sendToSegment('payment link create dropupload modal error', 'clicked');
    },

    fileUploadSuccess: () => {
      sendToLumberjack('pl_batch.create.modal.success');
      sendToSegment('payment link create dropupload modal success', 'clicked');
    },

    onFileNameTrack: () => {
      sendToLumberjack('pl_batch.create.name');
      sendToSegment('payment link batch create name', 'clicked');
    },

    onSmSNotify: () => {
      sendToLumberjack('pl_batch.create.sms');
      sendToSegment('payment link batch create sms', 'clicked');
    },

    onEmailNotify: () => {
      sendToLumberjack('pl_batch.create.email');
      sendToSegment('payment link batch create email', 'clicked');
    },

    onPreview: () => {
      sendToLumberjack('pl_batch.create.preview');
      sendToSegment('payment link batch create preview table', 'onMouseEnter');
    },

    abandonBatchModal: () => {
      sendToLumberjack('pl_batch.create.abandon');
      sendToSegment('payment link batch create abandon', 'clicked');
    },

    createBatch: () => {
      sendToLumberjack('pl_batch.create_issue');
      sendToSegment('payment link batch create issue', 'clicked');
    },

    successModalClose: () => {
      sendToLumberjack('pl_batch.create.closesuccessx');
      sendToSegment('payment link batch create close success', 'clicked');
    },

    batchDetailViewOnLoad: () => {
      sendToLumberjack('pl_batch.update.detailsview');
      sendToSegment('payment link batch detail view load', 'clicked');
    },

    onDetailViewUnMount: () => {
      sendToLumberjack('pl_batch.update.closedetails');
      sendToSegment('payment link batch detail close', 'clicked');
    },

    onDetailsView: () => {
      sendToLumberjack('pl_batch.update.detailsview');
      sendToSegment('payment link batch detail view load', 'clicked');
    },

    viewAllClick: () => {
      sendToLumberjack('pl_batch.update.viewall');
      sendToSegment('payment link batch detail view all', 'clicked');
    },

    batchIdChange: () => {
      sendToLumberjack('pl_batch.search.id');
      sendToSegment('payment link batch search batch id change', 'clicked');
    },

    batchSearchCount: () => {
      sendToLumberjack('pl_batch.search.count');
      sendToSegment('payment link batch search count', 'clicked');
    },

    onSearchAnalytics: () => {
      sendToLumberjack('pl_batch.search.confirm');
      sendToSegment('payment link batch search confirm', 'clicked');
    },

    onClearAnalytics: () => {
      sendToLumberjack('pl_batch.search.clear');
      sendToSegment('payment link batch search clear', 'clicked');
    },

    onPagination: (page, type) => {
      const prop = {
        page,
      };
      sendToLumberjack(`pl_batch.browse.${type}`, prop);
      sendToSegment(`payment link batch browse ${humanize(type)}`, 'clicked', prop);
    },

    init(_lumberjackTrack) {
      lumberjackTrack = _lumberjackTrack;
    },
  };
}

export default _track();
