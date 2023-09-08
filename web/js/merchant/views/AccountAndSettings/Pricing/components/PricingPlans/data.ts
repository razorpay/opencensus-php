const STATUS_DATA = {
  LIVE: {
    label: 'LIVE',
    variant: 'positive',
  },
  IN_PROGRESS: {
    label: 'IN PROGRESS',
    variant: 'notice',
  },
  PAYMENT_PROCESSING: {
    label: 'PAYMENT PROCESSING',
    variant: 'information',
  },
} as const;
const PRICING_PLAN_STATUS = {
  active: 'active',
  approved: 'approved',
  created: 'created',
  pending: 'pending',
  processed: 'processed',
  processing: 'processing',
};
const defaultErrorMessage = 'Error in fetching details. Please check later';

export { STATUS_DATA, defaultErrorMessage, PRICING_PLAN_STATUS };
