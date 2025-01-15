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
  Overview: '27f23cb9-e01f-4376-9556-79630ef0d1af',
  UPI: '5cab776a-cb63-4e0d-a76d-0e41804646ea',
  Cards: '4fbb7298-9002-4b4b-a793-2e1c62051341',
  Netbanking: '13524b78-5b48-40a5-8556-43ec496f6fa3',
  Wallets: 'b02bb061-eeed-4971-a4af-dae7ee01e697',
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
