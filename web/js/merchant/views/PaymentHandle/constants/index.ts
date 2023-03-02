export const SHIMMER_BAR_VARIANTS = {
  SMALL: 'small',
  MEDIUM: 'medium',
  LARGE: 'large',
};

export const ERROR_MAPPING = {
  HANDLE_INVALID: 'Payment Handle does not exists for this merchant. Please create a new one',
};

export const PAYMENT_HANDLE_URL = '/payment-handle';

export const ONBOARDING_CARD_DEFAULT_PROPS = {
  padding: [0],
  height: '600px',
  maxHeight: '600px',
};

export const PAYMENT_INITIAL_VALUE = {
  id: '',
  amount: null,
  currency: '',
  currency_symbol: '',
  expire_by: null,
  times_payable: null,
  times_paid: 0,
  total_amount_paid: 0,
  status: '',
  status_reason: null,
  short_url: '',
  user_id: null,
  user: null,
  receipt: null,
  title: '',
  description: null,
  notes: [],
  support_contact: null,
  support_email: null,
  terms: null,
  type: '',
  payment_page_items: [],
  created_at: 0,
  updated_at: 0,
  slug: '',
  captured_payments_count: 0,
  settings: {
    udf_schema: '',
    version: '',
    theme: '',
  },
};
