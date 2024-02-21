export const BATCH_TYPES = {
  CREATE_GCMS_EMAIL_DELIVERY: 'gcms_upload_bulk_emails',
};

export const BATCH_UPLOAD_INFO_MESSAGES = [
  'Order ID and Program ID can be obtained from the GCMS orders page on the dashboard.',
  'Email should be unique for each gift card.',
  'Denominations should be in paisa.',
  'Make sure the emails passed in the file are equal to the number of processed gift cards.',
];

export const DISPLAY_MESSAGES = {
  process: 'The file is being processed. Please wait as this may take some time.',
  success: 'The file has been processed successfully.',
  error: 'There was an error while processing the file. Please try again after some time.',
  exceed: 'The file size exceeds the maximum size limit. Please upload a smaller file.',
};
