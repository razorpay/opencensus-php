import routeSym from 'styles/assets/symbols/route.svg';
import subscriptionsSym from 'styles/assets/symbols/subscriptions.svg';
import smartCollectSym from 'styles/assets/symbols/smartcollect.svg';

export default [
  {
    name: 'Razorpay Routes',
    description: 'For Marketplace, Vendor payouts, Regional splits, etc.',
    link: '/route/payments',
    symbol: routeSym,
  },
  {
    name: 'Razorpay Subscriptions',
    description: 'Subscriptions plans with automated recurring transactions.',
    link: '/subscriptions',
    symbol: subscriptionsSym,
  },
  {
    name: 'Razorpay Smart Collect',
    description: 'Collect payments via direct bank transfers (NEFT/RGTS/IMPS).',
    link: '/virtualaccounts',
    symbol: smartCollectSym,
  },
];
