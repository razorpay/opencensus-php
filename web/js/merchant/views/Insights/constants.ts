import moment from 'moment';
import { INSIGHTS_SUPERSET_CONFIGS_KEYS } from 'merchant/views/Insights/configs/INSIGHTS_SUPERSET_CONFIGS_KEYS';

export const INSIGHTS_DASHBOARDS = [
  {
    name: 'Success rate',
    link: 'success-rate',
    experiment_name: 'insights_success_rate_experiment',
    isEnabled: (experiments) => experiments.isInsightsSuccessRateEnabled,
  },
  {
    name: 'Checkout',
    link: 'checkout',
    experiment_name: 'insights_checkout_experiment',
    isEnabled: (experiments) => experiments.isInsightsCheckoutEnabled,
  },
];

export const SUCCESS_RATE_TABS = [
  { name: 'Overview', link: 'overview' },
  { name: 'UPI', link: 'upi' },
  { name: 'Cards', link: 'cards' },
  { name: 'Netbanking', link: 'netbanking' },
  { name: 'Wallets', link: 'wallets' },
];

export const CHECKOUT_TABS = [{ name: 'Magic', link: 'magic' }];

export const getDashboardTabs = (dashboardLink: string) => {
  switch (dashboardLink) {
    case 'success-rate':
      return SUCCESS_RATE_TABS;
    case 'checkout':
      return CHECKOUT_TABS;
    default:
      return [];
  }
};

export const SUPERSET_DASHBOARD_IDS = {
  Overview: INSIGHTS_SUPERSET_CONFIGS_KEYS.INSIGHTS_SUCCESS_RATE_SUPERSET_OVERVIEW_ID,
  UPI: INSIGHTS_SUPERSET_CONFIGS_KEYS.INSIGHTS_SUCCESS_RATE_SUPERSET_UPI_ID,
  Cards: INSIGHTS_SUPERSET_CONFIGS_KEYS.INSIGHTS_SUCCESS_RATE_SUPERSET_CARDS_ID,
  Netbanking: INSIGHTS_SUPERSET_CONFIGS_KEYS.INSIGHTS_SUCCESS_RATE_SUPERSET_NETBANKING_ID,
  Wallets: INSIGHTS_SUPERSET_CONFIGS_KEYS.INSIGHTS_SUCCESS_RATE_SUPERSET_WALLETS_ID,
  Magic: INSIGHTS_SUPERSET_CONFIGS_KEYS.INSIGHTS_CHECKOUT_SUPERSET_MAGIC_ID,
  MagicX: INSIGHTS_SUPERSET_CONFIGS_KEYS.INSIGHTS_CHECKOUT_SUPERSET_MAGICX_ID,
};

export const SUPERSET_URL = INSIGHTS_SUPERSET_CONFIGS_KEYS.INSIGHTS_SUPERSET_URL;


const startOfDay = moment().clone().startOf('day').toDate();
const endOfDay = moment().clone().endOf('day').toDate();
const last7DaysStartOfDay = moment().clone().subtract(7, 'days').startOf('day').toDate();
const last30DaysStartOfDay = moment().clone().subtract(30, 'days').startOf('day').toDate();

export const insightsDateRangePresets: { label; value }[] = [
  {
    label: 'Custom Range',
    value: () => [startOfDay, endOfDay],
  },
  {
    label: 'Past 7 Days',
    value: () => [last7DaysStartOfDay, endOfDay],
  },
  {
    label: 'Past 30 Days',
    value: () => [last30DaysStartOfDay, endOfDay],
  },
];

export const DOCUMENTATION_ROUTES = {
  Overview: 'https://razorpay.com/docs/payments/insights',
  UPI: 'https://razorpay.com/docs/payments/insights/upi',
  Cards: 'https://razorpay.com/docs/payments/insights/cards',
  Netbanking: 'https://razorpay.com/docs/payments/insights/netbanking',
  Wallets: 'https://razorpay.com/docs/payments/insights/wallets',
  FAQs: 'https://razorpay.com/docs/payments/insights/faqs',
  Glossary: 'https://razorpay.com/docs/payments/insights/glossary',
  Magic: 'https://razorpay.com/docs/payments/insights/checkout-analytics/magic',
  MagicX: 'https://razorpay.com/docs/payments/insights/checkout-analytics/magicx',
};

export const dashboardHeights: { [key: string]: string } = {
  Overview: '2100px',
  UPI: '1900px',
  Cards: '3100px',
  Netbanking: '2100px',
  Wallets: '2100px',
  Magic: '2400px',
  MagicX: '1650px',
};
