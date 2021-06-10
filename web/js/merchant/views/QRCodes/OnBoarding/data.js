import { getCustomURL } from 'merchant/components/DocsLink';

// Features
export const FEATURES_DATA = [
  {
    icon: '/dist/css/assets/qr_code/features/unique_business.svg',
    title: 'QR Codes unique to your business',
    desc:
      'Create QR codes for multiple customers, collect single or multiple payments, set expiry limits and much more.',
  },
  {
    icon: '/dist/css/assets/qr_code/features/track_payments.svg',
    title: 'Easy Tracking and Reconciliation',
    desc: 'Track payments instantly and generate reports in real-time.',
  },
  {
    icon: '/dist/css/assets/qr_code/features/unlimited_qr_codes.svg',
    title: 'Unlimited QR codes at no cost',
    desc: 'Generate as many QR codes as you need, absolutely free.',
  },
];

// Features Links
export const FEATURES_LINKS = [
  {
    label: 'Know more',
    url: getCustomURL('https://razorpay.com/docs/qr-codes/'),
  },
];
