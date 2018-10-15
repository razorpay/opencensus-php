import store from 'merchant/store';

const user = store.getState().getUser();

let prefix = '';
if (user.isOrgRZP) {
  prefix = 'Razorpay ';
}

export default [
  {
    name: prefix + 'Route',
    description: 'For Marketplace, Vendor payouts, Regional splits, etc.',
    link: '/route/payments',
    symbol: 'route',
    help: 'https://razorpay.com/route',
  },
  {
    name: prefix + 'Subscriptions',
    description: 'Subscriptions plans with automated recurring transactions.',
    link: '/subscriptions',
    symbol: 'sub',
    help: 'https://razorpay.com/subscriptions',
  },
  {
    name: prefix + 'Smart Collect',
    description: 'Collect payments via direct bank transfers (NEFT/RGTS/IMPS).',
    link: '/virtualaccounts',
    symbol: 'sc',
    help: 'https://razorpay.com/smartcollect',
  },
];
