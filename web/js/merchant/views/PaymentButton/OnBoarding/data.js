import { ORG_CUSTOM_CODE_MAP } from 'merchant/models/User';
// Features
export const FEATURES_DATA = {
  noCodingRequired: {
    icon: '/dist/css/assets/product_onboarding/without_coding.svg',
    title: 'No Coding Required',
    desc: `Your business can go online with zero integration and tech efforts. We build and operate for you.`,
  },
  customBrandedBtn: {
    icon: '/dist/css/assets/product_onboarding/custom_brand.svg',
    title: 'Custom Branded Buttons',
    desc: 'Customize the look and label of your payment button to reflect your brand colours, for seamless customer experience.',
  },
  paymentModesRzp: {
    icon: '/dist/css/assets/product_onboarding/alter_native_payment_option.svg',
    title: 'Multiple Payment Modes',
    desc: 'Payment Buttons pre-integrated with our Payment Gateway. Customers can choose from multiple RBI compliant payment options.',
  },
  paymentModesCurlec: {
    icon: '/dist/css/assets/product_onboarding/alter_native_payment_option.svg',
    title: 'Multiple Payment Modes',
    desc: 'Payment Buttons pre-integrated with our Payment Gateway. Customers can choose from local compliant payment options',
  },
};

// Features Links
export const FEATURES_LINKS = [
  {
    label: 'Know more',
    url: 'https://razorpay.com/docs/payment-button/',
  },
];

export const FEATURES_DATA_ORG = {
  [ORG_CUSTOM_CODE_MAP.RAZORPAY]: [
    FEATURES_DATA.noCodingRequired,
    FEATURES_DATA.customBrandedBtn,
    FEATURES_DATA.paymentModesRzp,
  ],
  [ORG_CUSTOM_CODE_MAP.CURLEC]: [
    FEATURES_DATA.noCodingRequired,
    FEATURES_DATA.customBrandedBtn,
    FEATURES_DATA.paymentModesCurlec,
  ],
};
