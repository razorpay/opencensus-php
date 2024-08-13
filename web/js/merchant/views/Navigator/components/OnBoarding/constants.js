import { CLICK_KNOW_MORE } from 'merchant/views/Navigator/components/OnBoarding/track';
import { trackOptimizerEvents } from 'merchant/views/Navigator/track';

// Left image URL
// export const IMG_URL = 'https://razorpay.com/assets/optimizer/banner-illustration.png';
export const IMG_URL = require('assets/optimizer/banner-illustration.png');
export const BRANDS_IMG_URL = require('assets/optimizer/brands.png');
export const SURVEY_IMG_URL = require('assets/optimizer/survey-illustration.png');

// Features
export const FEATURES_DATA = [
  {
    icon: 'https://razorpay.com/assets/optimizer/fold-5/card-2.png',
    title: 'Save Lakhs in Integration costs',
    desc: 'No need to spend months integrating with new payment gateways. With Optimizer you can add new payment gateways with just a few clicks!',
  },
  {
    icon: 'https://razorpay.com/assets/optimizer/fold-5/card-3.png',
    title: 'Boost Revenue with our AI powered routing',
    desc: 'Use our AI powered smart router to route payments to the best performing PG in real time. See your payment success rates go up by as much as 10%',
  },
  {
    icon: 'https://razorpay.com/assets/optimizer/fold-5/card-4.png',
    title: 'Save time and effort with our one stop dashboard',
    desc: 'Now manage refunds, reconciliation and analytics across all your payment gateways from the Optimizer dashboard',
  },
];

// Features Links
export const FEATURES_LINKS = [
  {
    label: 'Know more',
    url: 'https://razorpay.com/docs/payments/optimizer/',
    onClick: () => trackOptimizerEvents(CLICK_KNOW_MORE),
  },
];

export const MORE_FEATURES_LINK = {
  label: 'Know more',
  url: 'https://razorpay.com/optimizer-intelligent-payments-routing/',
};

export const GATEWAYS_OPTIONS = [
  { label: 'Razorpay', name: 'razrorpay' },
  { label: 'Paytm', name: 'paytm' },
  { label: 'PayU', name: 'payu' },
  { label: 'Cashfree', name: 'cashfree' },
  { label: 'CCAvenue', name: 'ccavenue' },
  { label: 'BillDesk', name: 'billdesk' },
  { label: 'Others', name: 'others' },
];

export const HAVE_MULTIPLE_GATEWAYS_OPTIONS = [
  { label: 'Yes', value: 'yes' },
  { label: 'No, but planning on having multiple payment gateways', value: 'no' },
];

export const POINTS = [
  { title: 'Get one click Integration with 15+ top payment gateways' },
  { title: 'Boost payments success rates by 10% with AI powered routing' },
  { title: 'Save 15+ hours with automated reconciliation across all PGs' },
];
