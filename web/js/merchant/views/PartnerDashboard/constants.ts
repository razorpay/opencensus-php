export const INVITE_VIEW_TYPE = {
  ACCEPTED: 'Accepted Invites',
  ALL: 'All Invites',
};

export const PRODUCT_TYPE = {
  X: 'banking',
  PG: 'primary',
  POS: 'pos',
  CAPITAL: 'capital',
};

export const PRODUCT_ROUTE_PATH_PREFIX = {
  [PRODUCT_TYPE.X]: '/x',
  [PRODUCT_TYPE.PG]: '', // Avoids extra slash getting added
  [PRODUCT_TYPE.POS]: '/pos',
  [PRODUCT_TYPE.CAPITAL]: '/capital',
};

// Need this map at places where slashes are not expected
export const PRODUCT_ROUTE_PATH_PARAM = {
  [PRODUCT_TYPE.X]: 'x',
  [PRODUCT_TYPE.PG]: '',
  [PRODUCT_TYPE.POS]: 'pos',
  [PRODUCT_TYPE.CAPITAL]: 'capital',
};

export const ORG_NAME = {
  CURLEC: 'Curlec',
  RZP: 'Razorpay',
};
export const PRODUCT_NAME = {
  [PRODUCT_TYPE.X]: 'RazorpayX',
  [PRODUCT_TYPE.PG]: 'Payments',
  [PRODUCT_TYPE.CAPITAL]: 'Capital',
  [PRODUCT_TYPE.POS]: 'POS',
};

export const ADD_NEW_MERCHANT_ELIGIBLE_ROLES = 'owner manager admin partner_agent';

export const ADD_MODE = {
  single: 'single',
  bulk: 'bulk',
  social: 'social',
};

export const ONBOARDING_LABELS = {
  GET_STARTED: 'Get Started',
  GET_STARTED_PENDING: 'Getting Started ...',
};

export const CAPITAL_STATUS = {
  bureau_submission: 'bureau submission',
  income_proof_submission: 'income proof submission',
  pv_pending: 'pv pending',
  pv_processing: 'pv processing',
  stp_processing: 'stp processing',
  offer_acceptance: 'offer acceptance',
  post_offer_docs_collection: 'post offer docs collection',
  post_offer_docs_verification: 'post offer docs verification',
  esign_initiaiton: 'esign initiaiton',
  merchant_esign_pending: 'merchant esign pending',
  merchant_nach_pending: 'merchant nach pending',
  razorpay_esign_pending: 'razorpay esign pending',
  lender_decision: 'lender decision',
  lender_response: 'lender response',
  pre_offer_docs_resubmission: 'pre offer docs resubmission',
  rejection_bucket: 'rejection bucket',
  uw_processing: 'uw processing',
  uw_hold: 'uw hold',
  application_closed: 'application closed',
  go_live: 'go live',
  pre_offer_verification: 'pre offer verification',
  send_to_lender: 'send to lender',
  lender_docs_resubmission: 'lender docs resubmission',
  cpv_pending: 'cpv pending',
  in_process: 'in process',
  application_initiation: 'application initiation',
  application_rejected: 'application rejected',
};

export const ORG_CUSTOM_CODE = {
  CURLEC: 'curlec',
  RZP: 'rzp',
};

export const NOT_AVAILABLE = 'N/A';

export const PARTNER_TYPE = {
  PURE_PLATFORM: 'pure_platform',
  RESELLER: 'reseller',
  AGGREGATOR: 'aggregator',
};

export const PARTNER_TYPE_DISPLAY_NAMES = {
  reseller: 'Reseller',
  aggregator: 'Aggregator',
  pure_platform: 'Platform',
  bank: 'Bank',
  fully_managed: 'Fully Managed',
};

export const COMMISSION_TYPE = {
  REFUND: 'refund',
  PAYMENT: 'payment',
};

// TODO: Move existing website links in PartnerDashboard/ folder to this constant:
export const PARTNERSHIPS_WEBSITE_LINKS = {
  PLATFORM_OAUTH_INTEGRATION: 'https://razorpay.com/docs/partners/platform/oauth/',
  PERFORM_KYC_DOCS_LINK: 'https://razorpay.com/docs/partners/resellers/perform-kyc/',
  PG_KYC_DOCS_LINK: 'https://razorpay.com/docs/payments/kyc',
  CAPITAL_ADD_PARTNERS_KNOW_MORE_URL:
    'https://betasite.razorpay.com/docs/razorpay/add-partners-capital-doc/partners/capital/#track-leads-status',
  PRIVACY_POLICY_URL: 'https://razorpay.com/privacy/',
  TERMS_AND_CONDITION_URL: 'https://razorpay.com/s/terms/partners',
};

export const CREATE_BUREAU_COUNTDOWN_TIME = 30000;

export const SMS_COUNT_MAX_LIMIT = 10;
