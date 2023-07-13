export const RZP_LOGO_URL = 'https://cdn.razorpay.com/logo_invert.svg';
export const DASHBOARD_LANDING_URL = '/dashboard';
export const ONBOARDING_STEPS_URL = '/onboarding/steps';
export const EASY_DASHBOARD_NC_LANDING_URL = `${window.EASY_ONBOARDING_URL}/onboarding/needs-clarification`;
export const KYC_URL = '/kyc';
export const ACTIVATION_URL = '/activation';

export const ACTIVATED_MCC_PENDING = 'activated_mcc_pending';

export const ACTIVATION_STATE = {
  L1_DEDUPE_BLOCKED: 'L1_dedupe_blocked',
  L2_DEDUPE_BLOCKED: 'L2_dedupe_blocked',
  REJECTED: 'rejected',
};

export const PromotedReservationState = {
  payment_products: {
    promoted: {
      reservedPos: [3],
    },
  },
  banking_products: {
    promoted: {
      reservedPos: [2, 3],
    },
  },
};

export const SIDEEBAR_PRODUCTS_TITLES = {
  payment_links: 'Payment Links',
  payment_pages: 'Payment Pages',
  payment_handle: 'Razorpay.me Link',
  invoices: 'Invoices',
  payment_button: 'Payment Button',
  affordability: 'Affordability',
  qr_codes: 'QR Codes',
  subscriptions: 'Subscriptions',
  smart_collect: 'Smart Collect',
  route: 'Route',
  checkout_rewards: 'Checkout Rewards',
  magic_checkout: 'Magic Checkout',
  optimizer: 'Optimizer',
  stores: 'Stores',
  bbps: 'BBPS',
  x_banking: 'X Banking',
  x_corporate_cards: 'X Corporate Cards',
  x_payroll: 'X Payroll',
  cash_advance: 'Cash Advance',
  line_of_credit: 'Line of Credit',
  wallet: 'Wallet',
  home: 'Home',
  transactions: 'Transactions',
  settlements: 'Settlements',
  reports: 'Reports',
  my_account: 'Account',
  settings: 'Settings',
  accountsettings: 'Account & Settings',
  internationalPaymentsBtn: 'International Payments',
  customers: 'Customers',
  offers: 'Offers',
  api_keys: 'API Keys and Plugins',
  developers: 'Developers',
  app_store: 'App Store',
  payment_metrics: 'Payment Metrics',
};
