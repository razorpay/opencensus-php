import { isProductionEnv } from 'common/utils/rzp-utils';

export const WALLET_BASE_PATH = 'wallet/proxy/issuing';

const isProd = isProductionEnv();

// Only for to Production Environment
export const getGCMSBasePath = (mode = 'test') =>
  isProd ? (mode === 'test' ? `gcoms/test` : 'gcoms') : 'gcoms';
export const RESELLER_PROGRAMS_PATH = 'programs';
export const RESELLER_ACCOUNTS_PATH = 'accounts';
export const RESELLER_ORDERS_PATH = 'orders';
export const PROGRAM_TYPES = {
  VOUCHER: {
    id: 'voucher',
    name: 'Voucher',
    color: '#CA58FF',
  },
  CODE: {
    id: 'code',
    name: 'Code',
    color: '#30C5D8',
  },
};

export const ORDERS_STATUS = {
  //For status in filters dropdown
  all: {
    label: 'All',
    value: 'all',
    color: 'neutral',
  },
  draft: {
    label: 'Draft',
    value: 'draft',
    color: 'neutral',
  },
  under_review: {
    label: 'Under Review',
    value: 'under_review',
    color: 'notice',
  },
  processed: {
    label: 'Processed',
    value: 'processed',
    color: 'positive',
  },
  submitted: {
    label: 'Submitted',
    value: 'submitted',
    color: 'information',
  },
  cancelled: {
    label: 'Cancelled',
    value: 'cancelled',
    color: 'negative',
  },
};

export const DELIVERY_STATUS = {
  delivery_in_progress: {
    label: 'Delivery In Progress',
    value: 'delivery_in_progress',
    color: 'notice',
  },
  completed: {
    label: 'Completed',
    value: 'completed',
    color: 'positive',
  },
};

export const DATE_RANGE_PRESETS: [string, number, string][] = [
  ['All Time', -30, 'days'],
  ['Past 7 Days', -7, 'days'],
  ['Past 30 Days', -30, 'days'],
  ['Past 90 Days', -90, 'days'],
];
export const RESELLERS_STATUS = {
  active: {
    label: 'Active',
    value: 'active',
    color: 'positive',
  },
  approval_pending: {
    label: 'Approval Pending',
    value: 'approval_pending',
    color: 'information',
  },
  all: {
    label: 'All',
    value: 'all',
    color: 'neutral',
  },
};
