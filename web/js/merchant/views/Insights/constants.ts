import moment from 'moment';
import { INSIGHTS_SUPERSET_CONFIGS_KEYS } from 'merchant/views/Insights/configs/INSIGHTS_SUPERSET_CONFIGS_KEYS';
import { InsightsApiResponse } from 'merchant/components/SidebarV2/Products/Insights/types';
import { SparklesIcon, LayoutIcon } from '@razorpay/blade/components';

export const INSIGHTS_DASHBOARDS = [
  {
    name: 'Success rate',
    link: 'success-rate',
    experiment_name: 'insights_success_rate_experiment',
  },
  {
    name: 'Checkout',
    link: 'checkout',
    experiment_name: 'insights_checkout_experiment',
  },
];

export const SUCCESS_RATE_TABS = [
  { name: 'Overview', link: 'overview' },
  { name: 'UPI', link: 'upi' },
  { name: 'Card', link: 'card' },
  { name: 'Netbanking', link: 'netbanking' },
  { name: 'Wallet', link: 'wallet' },
  { name: 'Emandate', link: 'emandate' },
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
  Card: INSIGHTS_SUPERSET_CONFIGS_KEYS.INSIGHTS_SUCCESS_RATE_SUPERSET_CARDS_ID,
  Netbanking: INSIGHTS_SUPERSET_CONFIGS_KEYS.INSIGHTS_SUCCESS_RATE_SUPERSET_NETBANKING_ID,
  Wallet: INSIGHTS_SUPERSET_CONFIGS_KEYS.INSIGHTS_SUCCESS_RATE_SUPERSET_WALLETS_ID,
  Magic: INSIGHTS_SUPERSET_CONFIGS_KEYS.INSIGHTS_CHECKOUT_SUPERSET_MAGIC_ID,
  MagicX: INSIGHTS_SUPERSET_CONFIGS_KEYS.INSIGHTS_CHECKOUT_SUPERSET_MAGICX_ID,
  Emandate: INSIGHTS_SUPERSET_CONFIGS_KEYS.INSIGHTS_CHECKOUT_SUPERSET_EMANDATE_ID,
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
  Card: 'https://razorpay.com/docs/payments/insights/card',
  Netbanking: 'https://razorpay.com/docs/payments/insights/netbanking',
  Wallet: 'https://razorpay.com/docs/payments/insights/wallet',
  FAQs: 'https://razorpay.com/docs/payments/insights/faqs',
  Glossary: 'https://razorpay.com/docs/payments/insights/glossary',
  Magic: 'https://razorpay.com/docs/payments/insights/checkout-analytics/magic',
  MagicX: 'https://razorpay.com/docs/payments/insights/checkout-analytics/magicx',
  Emandate: 'https://razorpay.com/docs/payments/insights/emandate',
};

export const dashboardHeights: { [key: string]: string } = {
  Overview: '2150px',
  UPI: '1950px',
  Card: '4300px',
  Netbanking: '2950px',
  Wallet: '2150px',
  Magic: '2400px',
  MagicX: '1650px',
  Emandate: '2000px',
};

export const FALLBACK_INSIGHTS_DATA: InsightsApiResponse = {
  'success-rate': [{"overview":"700"}],
};

export const getFallbackInsightsOnError = () => {
  try {
    return [
      {
        title: 'Success Rate',
        href: 'insights/success-rate/overview',
        icon: LayoutIcon,
        items: [
          {
            title: 'Overview',
            href: 'insights/success-rate/overview',
            icon: SparklesIcon,
            metricCount: null
          }
        ]
      }
    ];
  } catch (error) {
    console.error('Error creating simple fallback insights:', error);
    return [];
  }
};