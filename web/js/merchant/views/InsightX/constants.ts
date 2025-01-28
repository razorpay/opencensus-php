import { generatePresets } from 'common/ui/DateRangePickerV2';
import moment from 'moment';

export const INSIGHTX_TABS = [
  { name: 'Overview' },
  { name: 'UPI' },
  { name: 'Cards' },
  { name: 'Netbanking' },
  { name: 'Wallets' },
];

// based on name of subTabs in success rate
export const SUPERSET_DASHBOARD_IDS = {
  Overview: `${window.INSIGHT_X_SUPERSET_OVERVIEW_ID}`,
  UPI: `${window.INSIGHT_X_SUPERSET_UPI_ID}`,
  Cards: `${window.INSIGHT_X_SUPERSET_CARDS_ID}`,
  Netbanking: `${window.INSIGHT_X_SUPERSET_NETBANKING_ID}`,
  Wallets: `${window.INSIGHT_X_SUPERSET_WALLETS_ID}`,
};

export const insightxDateRangePresets: { label; value }[] = [
  {
    label: 'Custom Range',
    value: () => [moment().startOf('day').toDate(), moment().endOf('day').toDate()],
  },
  {
    label: 'Past 7 Days',
    value: () => [
      moment().subtract(7, 'days').startOf('day').toDate(),
      moment().endOf('day').toDate(),
    ],
  },
  {
    label: 'Past 30 Days',
    value: () => [
      moment().subtract(30, 'days').startOf('day').toDate(),
      moment().endOf('day').toDate(),
    ],
  },
];

export const DOCUMENTATION_ROUTES = {
  Overview: 'https://razorpay.com/docs/payments/insightx',
  UPI: 'https://razorpay.com/docs/payments/insightx/upi',
  Cards: 'https://razorpay.com/docs/payments/insightx/cards',
  Netbanking: 'https://razorpay.com/docs/payments/insightx/netbanking',
  Wallets: 'https://razorpay.com/docs/payments/insightx/wallets',
  FAQs: 'https://razorpay.com/docs/payments/insightx/faqs',
  Glossary: 'https://razorpay.com/docs/payments/insightx/glossary',
};

export const SUPERSET_URL = `${window.INSIGHT_X_SUPERSET_URL}`;

export const dashboardHeights: { [key: string]: string } = {
  Overview: '2100px',
  UPI: '1900px',
  Cards: '3100px',
  Netbanking: '2100px',
  Wallets: '2100px',
};
