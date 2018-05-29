import { stringifyQueryParamsWithPipe } from 'rzp/utils/rzp-utils';

import { setTrackData } from 'rzp/utils/googleAnalytics';

export default pageTitle => {
  const track = setTrackData({ eventCategory: pageTitle });

  return {
    trackGoToLinks: toText => {
      track({
        eventAction: `Go to - ${toText}`,
      });
    },

    trackSampleFileDownload: () => {
      track({
        eventAction: 'Download - Batch Sample File',
      });
    },

    trackDetails: (action, batchId) => {
      track({
        eventAction: `${action} Details - Batch`,
        eventLabel: `batch_id=${batchId}`,
      });
    },

    trackSearchFilters: params => {
      const label = stringifyQueryParamsWithPipe(params);
      track({
        eventAction: 'Search - Batches',
        eventLabel: label,
      });
    },

    trackUploadBatchFile: () => {
      track({
        eventAction: 'Upload - Batch File',
      });
    },

    trackSendAllLinks: params => {
      const label = stringifyQueryParamsWithPipe(params);
      track({
        eventAction: 'Send - All Payment Links ',
        eventLabel: label,
      });
    },

    trackSeeAllLinks: batchId => () => {
      track({
        eventAction: 'See - All Payment Links',
        eventLabel: `batch_id=${batchId}`,
      });
    },

    trackDownloadErrorReport: () => {
      track({
        eventAction: 'Download - Validation Error Report',
      });
    },

    trackDownloadProcessedBatchReport: () => {
      track({
        eventAction: 'Download - Processed Batch Report ',
      });
    },

    trackUploadBatch: action => {
      track({
        eventAction: `${action} Form - Create Batch`,
      });
    },
  };
};
