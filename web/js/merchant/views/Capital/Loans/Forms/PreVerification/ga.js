const track = (data) => {
  window.rzpAnalytics?.({
    eventCategory: 'LOS - Bank Statement',
    ...data,
  });
};

let merchantId = null;

export const setMerchantId = (id) => {
  merchantId = id;
};

export const trackOptionChange = (isNetbanking = false) => {
  track({
    eventAction: `Click Radio Button | ${isNetbanking ? 'Netbanking' : 'Manual Upload'}`,
    eventLabel: `${isNetbanking ? 'Use Netbanking' : 'Manual Upload'} | ${merchantId}`,
  });
};

export const trackStatementUploadSuccess = () => {
  track({
    eventAction: 'Statement Upload | Success',
    eventLabel: `Statement Uploaded | ${merchantId}`,
  });
};

export const trackFileRemoval = () => {
  track({
    eventAction: 'Statement | Removed',
    eventLabel: 'Statement | Removed',
  });
};

export const trackTotalFilesCount = (totalFiles) => {
  track({
    eventAction: 'Statement | Multiple files',
    eventLabel: `Statment | ${totalFiles} | ${merchantId}`,
  });
};

export const trackManualFileUploadFailure = () => {
  track({
    eventAction: 'Statement | Upload failed',
    eventLabel: `Statment Upload failed ${merchantId}`,
  });
};

export const trackToastClick = (dismissed = false) => {
  track({
    eventAction: 'Toast Message',
    eventLabel: `Toast Message | ${dismissed ? 'Click close CTA' : 'Click Understood'}`,
  });
};

export const trackNativeSubmission = () => {
  track({
    eventAction: 'Click CTA | Submit all files',
    eventLabel: `Submit files | ${merchantId}`,
  });
};

export const trackNetbankingSubmission = () => {
  track({
    eventAction: 'Click Continue with Netbanking CTA',
    eventLabel: `Netbanking | ${merchantId}`,
  });
};

export const trackNetbankingModalLoad = () => {
  track({
    eventAction: 'Perfios Connect Modal | Impression',
    eventLabel: `Modal seen by | ${merchantId}`,
  });
};

export const trackNetbankingModalSubmission = () => {
  track({
    eventAction: 'Perfios Connect Modal | Click Continue',
    eventLabel: `Continue to Perfios | ${merchantId}`,
  });
};

export const trackingNetbankingSuccess = () => {
  track({
    eventAction: 'Statment Uploaded via Perfios',
    eventLabel: `Statement Uploaded | ${merchantId}`,
  });
};

export const trackingNetbankingFailure = () => {
  track({
    eventAction: 'Upload Failed via Perfios',
    eventLabel: `Statement Failed | ${merchantId}`,
  });
};

export const trackNetbankingRetry = () => {
  track({
    eventAction: 'Merchant clicks Retry CTA',
    eventLabel: `Retry CTA  | ${merchantId}`,
  });
};

export const trackPreverificationCompleteLoad = () => {
  track({
    eventAction: 'Go back to bank statement flow',
    eventLabel: 'Received bank statment screen',
  });
};

export const trackFilesDrop = () => {
  track({
    eventAction: 'Drag & Drop files',
    eventLabel: 'Drop files from system folder',
  });
};
