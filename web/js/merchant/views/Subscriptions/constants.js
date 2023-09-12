import { ORG_CUSTOM_CODE_MAP } from 'merchant/models/User';

export const PAPER_NACH_CARD_BANNER_URL =
  'https://razorpay.com/docs/api/recurring-payments/paper-nach/authorization-transaction/#112-create-an-order';
export const UPDATE_PAYMENT_METHOD_URL =
  'https://razorpay.com/docs/subscriptions/payment-retries/#update-the-payment-method-via-our-hosted-page';
export const topEmandateBankCodes = ['SBIN', 'HDFC', 'ICIC', 'UTIB', 'KKBK'];

export const CARD_AFA_MAX_LIMIT = 15000; // Rs
export const CARD_TOKEN_MAX_AMOUNT = 1000000; // Rs
export const MY_CARD_MAX_AMOUNT = 30000; // RM
export const MAX_TOKEN_AMOUNT = 100000000; // in Paisa
export const MAX_TOKEN_AMOUNT_NACH = 1000000000; // in Paisa

export const GATEWAY_MAX_LIMIT = 20000000; // Paisa

export const UPI_AFA_MAX_LIMIT = 1500000; // Paisa
export const UPI_MAX_LIMIT_FOR_NON_BFSI = 10000000; // Paisa

export const EMANDATE_MAX_LIMIT = 100000000; // Paisa

export const DEFAULT_NACH_LIMIT = 10000000; // Rs
export const DEFAULT_UPI_LIMIT = 200000; // Rs
export const DEFAULT_EMANDATE_LIMIT = 99999; // Rs

export const tokenStatuses = ['initiated', 'confirmed', 'rejected', 'cancelled', 'paused'];
export const ONBOARDING_SUBSCRIPTIONS_DESCRIPTION = {
  [ORG_CUSTOM_CODE_MAP.RAZORPAY]:
    'Collect recurring payments from customers with Razorpay Subscriptions APIs',
  [ORG_CUSTOM_CODE_MAP.CURLEC]:
    'Collect recurring payments from customers with Curlec Subscriptions APIs',
};

// max amount allowed without AFA authentication
export const CARD_AFA_MAX_AMOUNT = {
  IN: CARD_AFA_MAX_LIMIT,
  MY: MY_CARD_MAX_AMOUNT,
};

// max amount allowed for card payments
export const CARD_MAX_AMOUNT_ALLOWED = {
  IN: CARD_TOKEN_MAX_AMOUNT,
  MY: MY_CARD_MAX_AMOUNT,
};

export const FREQUENCY = {
  AS_PRESENTED: 'as_presented',
  DAILY: 'daily',
  WEEKLY: 'weekly',
  FORTNIGHTLY: 'fortnightly',
  MONTHLY: 'monthly',
  BIMONTHLY: 'bimonthly',
  QUARTERLY: 'quarterly',
  HALF_YEARLY: 'half_yearly',
  YEARLY: 'yearly',
};

export const CARD_FREQUENCY = [
  FREQUENCY.AS_PRESENTED,
  FREQUENCY.WEEKLY,
  FREQUENCY.MONTHLY,
  FREQUENCY.YEARLY,
];
export const DEBIT_TYPES = {
  BEFORE: 'before',
  AFTER: 'after',
  ON: 'on',
};
export const BILLING_FREQUENCY = [
  { name: FREQUENCY.AS_PRESENTED, label: 'As and when presented' },
  { name: FREQUENCY.DAILY, label: 'Daily' },
  { name: FREQUENCY.WEEKLY, label: 'Weekly' },
  { name: FREQUENCY.FORTNIGHTLY, label: 'Fortnightly' },
  { name: FREQUENCY.MONTHLY, label: 'Monthly' },
  { name: FREQUENCY.BIMONTHLY, label: 'Bimonthly' },
  { name: FREQUENCY.QUARTERLY, label: 'Quarterly' },
  { name: FREQUENCY.HALF_YEARLY, label: 'Half Yearly' },
  { name: FREQUENCY.YEARLY, label: 'Yearly' },
];

export const FREQUENCY_DESC_MAP = {
  as_presented: 'You can charge the customer anytime',
  daily: 'You can charge the customer daily',
  weekly: 'You can charge the customer once a week',
  fortnightly: 'You can charge the customer fortnightly',
  monthly: 'You can charge the customer once a month',
  bimonthly: 'You can charge the customer bimonthly',
  quarterly: 'You can charge the customer once a quarter',
  half_yearly: 'You can charge the customer half yearly',
  yearly: 'You can charge the customer once a year',
};

export const RECURRING_TYPE = [
  { name: DEBIT_TYPES.BEFORE, label: 'Before' },
  { name: DEBIT_TYPES.AFTER, label: 'After' },
  { name: DEBIT_TYPES.On, label: 'On' },
];
