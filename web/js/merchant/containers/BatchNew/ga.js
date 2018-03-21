import { stringifyQueryParamsWithPipe } from 'rzp/utils/rzp-utils';

import { setTrackData } from 'rzp/utils/googleAnalytics';

const pageTitle = 'Dashboard - Payment Links';

export const track = setTrackData({ eventCategory: pageTitle });

export const trackGoToLinks = toText => {
  track({
    eventAction: `Go to - ${toText}`,
  });
};

export const trackSampleFileDownload = () => {
  track({
    eventAction: 'Download - Batch Sample File',
  });
};

export const trackDetails = (action, batchId) => {
  track({
    eventAction: `${action} Details - Batch`,
    eventLabel: `batch_id=${batchId}`,
  });
};

export const trackSearchFilters = params => {
  const label = stringifyQueryParamsWithPipe(params);
  track({
    eventAction: 'Search - Batches',
    eventLabel: label,
  });
};

export const trackUploadBatchFile = () => {
  track({
    eventAction: 'Upload - Batch File',
  });
};

export const trackSendAllLinks = params => {
  const label = stringifyQueryParamsWithPipe(params);
  track({
    eventAction: 'Send - All Payment Links ',
    eventLabel: label,
  });
};

export const trackSeeAllLinks = batchId => {
  track({
    eventAction: 'See - All Payment Links',
    eventLabel: `batch_id=${batchId}`,
  });
};

export const trackDownloadErrorReport = () => {
  track({
    eventAction: 'Download - Validation Error Report',
  });
};

export const trackDownloadProcessedBatchReport = () => {
  track({
    eventAction: 'Download - Processed Batch Report ',
  });
};

export const trackUploadBatch = action => {
  track({
    eventAction: `${action} Form - Create Batch`,
  });
};
