export const CAROUSEL_IMG_1 = require('assets/optimizer/opti-carousel1.png');
export const CAROUSEL_IMG_2 = require('assets/optimizer/opti-carousel2.png');
export const CAROUSEL_IMG_3 = require('assets/optimizer/opti-carousel3.png');
export const ONBOARDING_SUCCESS = require('assets/optimizer/onboarding-success.svg');

export const LEARN_MORE_LINK = 'https://razorpay.com/docs/payments/optimizer/';
export const SAVE_GATEWAY_DOC_LINK =
  'https://razorpay.com/docs/payments/optimizer/add-payment-providers/';
export const OPTI_BLOG =
  'https://razorpay.com/blog/housing-com-revolutionizes-its-payment-processing-across-multiple-payment-gateways-with-razorpay-optimizer/';

export const INFO_POINTS = [
  'One click integration with 15+ PGs',
  'Boost success rates by as much as 10%',
  'Single dashboard to manage all PGs',
];

export const POPULAR_GATEWAYS = [
  {
    label: 'PayU',
    value: 'payu',
  },
  {
    label: 'Paytm',
    value: 'paytm',
  },
  {
    label: 'Cashfree',
    value: 'cashfree',
  },
];

const PROVIDER_KEYS = {
  GATEWAY_NAME: 'Gateway Name',
  GATEWAY_ACQUIRER: 'Gateway Acquirer',
  SEAMLESS_KEY: 'optimizer_seamless_disabled',
  SODEXO: 'Sodexo',
  RECURRING: 'Recurring',
  MANDATORY_METHODS: 'Mandatory Methods',
  PAYMENT_METHODS: 'Payment Methods',
  TPV: 'TPV',
  // paytm
  ENABLE_AUTO_DEBIT: 'ENABLE_AUTO_DEBIT',
  WEBSITE: 'WEBSITE',
  CLIENT_KEY: 'CLIENT_KEY',
  CLIENT_SECRET: 'CLIENT_SECRET',
};

export const SKIP_INPUT_FOR_PROVIDER_KEYS = [
  PROVIDER_KEYS.GATEWAY_NAME,
  PROVIDER_KEYS.GATEWAY_ACQUIRER,
  PROVIDER_KEYS.SODEXO,
  PROVIDER_KEYS.SEAMLESS_KEY,
  PROVIDER_KEYS.MANDATORY_METHODS,
  PROVIDER_KEYS.RECURRING,
  PROVIDER_KEYS.PAYMENT_METHODS,
  PROVIDER_KEYS.ENABLE_AUTO_DEBIT,
  PROVIDER_KEYS.WEBSITE,
  PROVIDER_KEYS.CLIENT_KEY,
  PROVIDER_KEYS.CLIENT_SECRET,
  PROVIDER_KEYS.TPV,
];

// Find details of gateways
export const FIND_DETAILS_SUPPORTED_ON_GATEWAYS = ['payu', 'paytm', 'cashfree'];
const LOGO_PATH = 'static/assets/merchant-dash/provider-dashboard';
const getLogoPath = (gateway: string, extension: string = 'gif'): string =>
  `${window.cdnBaseUrl}/${LOGO_PATH}/${gateway}.${extension}`;

export const GIF_ASSETS = {
  payu: getLogoPath('payu'),
  paytm: getLogoPath('paytm'),
  cashfree: getLogoPath('cashfree'),
};
export const GATEWAY_DETAILS_MAP = {
  payu: ["Click on 'Payment Gateway' in left panel", "Scroll down to 'Key Salt Details' section"],
  paytm: [
    "Click 'Developer Settings' in the left panel",
    "Click 'API Keys'",
    "Click 'Production API Details' tab",
  ],
  cashfree: [
    'Open the Payment Gateway view from Cashfree Home page',
    "Click on 'Developers' in the left panel",
    "Click on 'API Keys'",
  ],
};
