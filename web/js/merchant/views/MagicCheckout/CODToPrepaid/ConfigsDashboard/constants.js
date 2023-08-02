import {
  getConversionString,
  getDiscountString,
  getExpiryTimeString,
  getConvertOrderOnString,
} from 'merchant/views/MagicCheckout/CODToPrepaid/ConfigsDashboard/utils';

export const CONVERSION_LEVEL = [
  { label: 'Select type of orders', name: '' },
  { label: 'All COD orders', name: 'all' },
  { label: 'High RTO risk orders', name: 'high' },
  { label: 'Medium RTO risk orders', name: 'medium' },
  { label: 'High and Medium RTO risk orders', name: 'highMedium' },
];

export const VALIDITY_OPTIONS = [
  { label: 'Select validity', name: '' },
  { label: '15 minutes', name: 900 },
  { label: '30 minutes', name: 1800 },
  { label: 'Custom time', name: 'custom' },
];

export const CONVERT_ON_OPTIONS = [{ label: 'WhatsApp message', name: 'whatsapp' }];

export const DISCOUNT_OPTIONS = [
  {
    label: 'Flat discount',
    value: 'flat',
  },
  {
    label: 'Percentage discount',
    value: 'percentage',
  },
];

export const NOTIFICATION_MSGS = {
  error: 'Something went wrong, please try again after sometime.',
  configSuccess: 'Configs saved successfully.',
  disableSuccess: 'Prepay COD disabled successfully.',
  credentialsModalClose: 'COD to Prepaid conversion not enabled. Please provide API credentials.',
};

export const DISCOUNT_INFO =
  'Discount will be combined with any other discounts that may already be applied to the order.';

export const MAX_HOURS = 48;
export const MAX_MINS = 59;
export const MIN_TIME = 5;

export const VALIDATION_MSGS = {
  discount: 'Discount value should be less than minimum order value.',
  duration: {
    minutesError: `Please enter a value between 0 and ${MAX_MINS}`,
    hoursError: `Max. allowed validity is ${MAX_HOURS}hrs`,
    maxTimeError: `Max. allowed validity is ${MAX_HOURS}hrs`,
    minTimeError: `Min. allowed validity is ${MIN_TIME} mins`,
  },
  lessThanZero: 'Invalid Value',
  required: 'Required',
  mustBeGreaterThanZero: 'Value must be greater than 0',
};

export const SAVED_CONFIGS = [
  {
    title: 'Enable conversion for',
    getText: (prepayCODConfigs) => getConversionString(prepayCODConfigs),
    key: 'riskCategory',
  },
  {
    title: 'Discount',
    getText: (prepayCODConfigs) => getDiscountString(prepayCODConfigs),
    key: 'discount',
  },
  {
    title: 'Expire after',
    getText: (prepayCODConfigs) => getExpiryTimeString(prepayCODConfigs),
    key: 'expiryTime',
  },
  {
    title: 'Convert order on',
    getText: (prepayCODConfigs) => getConvertOrderOnString(prepayCODConfigs),
    key: 'conversionPlatform',
  },
];

export const POPOVER_INFO_TEXT = {
  prepayCODToggle:
    'Nudge your customers to pay for COD orders upfront before shipping to increase prepaid orders and decrease RTOs.',
  riskCategory:
    'Selectively filter the order for which you want to enable COD to Prepaid conversion.',
  discount:
    'Add a flat or percentage discount on top of the total COD order amount. Adding discount increases probability of conversion by upto 30%.',
  linkValidity:
    'Order conversion will expire for the customer after validity. Please set order conversion validity to less than your average shipping start time for an order. Customers should not be able to convert COD to prepaid after order is shipped.',
  conversionPlatform: 'Offer order conversion to customer via WhatsApp message.',
};

export const SAVE_CONFIGS_CONFIRMATION_TEXTS = {
  header: 'Enable conversion',
  desc: 'Once you enable conversion we will be able to send conversion links to customers to pay upfront for COD orders.',
  affirmativeLabel: 'Enable',
  abortLabel: 'Don’t enable!',
};

export const DISABLE_PREPAY_COD_CONFIRMATION_TEXTS = {
  header: 'Disable conversion?',
  desc: 'If you disable conversion, your customers will not be able to pay upfront to convert COD orders to prepaid orders.',
  affirmativeLabel: 'Disable',
  abortLabel: 'Don’t disable!',
};

export const DISCOUNT_TYPE = {
  maxDiscount: 'maxDiscount',
  minOrderValue: 'minOrderValue',
  percentage: 'percentage',
  flat: 'flat',
  zero: 'zero',
};

export const CREDENTIALS_MODAL_DESC =
  'Magic Checkout requires WooCommerce API credentials to convert COD orders to prepaid orders.';
