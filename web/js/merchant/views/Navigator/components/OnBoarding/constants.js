import { CLICK_KNOW_MORE } from 'merchant/views/Navigator/components/OnBoarding/track';
import { trackOptimizerEvents } from 'merchant/views/Navigator/track';

// Left image URL
export const IMG_URL = 'https://razorpay.com/assets/optimizer/banner-illustration.png';

// Features
export const FEATURES_DATA = [
  {
    icon: 'https://razorpay.com/assets/optimizer/fold-5/card-2.png',
    title: 'Add multiple gateways in a single click',
    desc: 'Save on transaction costs by routing across gateways by payment method, value and any other factor',
  },
  {
    icon: 'https://razorpay.com/assets/optimizer/fold-5/card-3.png',
    title: 'AI-Powered Routing',
    desc: 'Automatically route transactions to the payment gateway with the highest success rate',
  },
  {
    icon: 'https://razorpay.com/assets/optimizer/fold-5/card-4.png',
    title: 'Single Data Source',
    desc: 'Get all your settlement reports and success rate data from all your payment gateways in a single click',
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
