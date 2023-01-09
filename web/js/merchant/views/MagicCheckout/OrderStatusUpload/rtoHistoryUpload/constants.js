export const DISPLAY_MESSAGES = {
  process: 'The file is being processed. Please wait as this may take some time.',
  success: 'The file has been processed successfully.',
  error: 'There was an error while processing the file. Please try again after some time.',
  exceed: 'The file size exceeds the maximum size limit. Please upload a smaller file.',
};

export const SHIPPING_PROVIDERS = [
  { label: 'Select Provider', name: '' },
  { label: 'Shiprocket', name: 'shiprocket' },
  { label: 'Delhivery', name: 'delhivery' },
  { label: 'Pickrr', name: 'pickrr' },
  { label: 'Other providers', name: 'generic' },
];

export const SAMPLE_FILE_URL =
  'https://cdn.razorpay.com/static/assets/magic-checkout/sample_order_history_file.csv';

export const NOTIFICATION_MESSAGES = {
  success: 'Order history file uploaded successfully.',
  error: {
    time_expired: "Order history can't be uploaded after 30 days.",
    default: 'Order history file upload unsuccessful. Please Try again.',
  },
};

export const SHIPPING_PROVIDERS_MAPPING = {
  shiprocket: 'Shiprocket',
  pickrr: 'Pickrr',
  delhivery: 'Delhivery',
  generic: 'Other Providers',
};
