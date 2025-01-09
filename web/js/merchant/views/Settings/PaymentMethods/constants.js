export const REQUESTED = 'requested';
export const PENDING = 'pending';
export const ACTIVATED = 'activated';
export const REJECTED = 'rejected';
export const ACTION_REQUIRED = 'action_required';
export const ACTIVATED_ACTION_REQUIRED = 'activated_action_required';
export const REQUESTABLE = 'requestable';
export const ACCOUNT_LINKABLE = 'account_linkable';
export const CANCELLED = 'cancelled';
export const GREYED = 'greyed';
export const REINITATED = 'reinitiated';

export const WEBSITE_DETAILS = 'website_details';
export const MERCHANT_DOCUMENTS = 'merchant_documents';
export const MERCHANT_DETAILS = 'merchant_details';
export const ZESTMONEY = 'ZestMoney';
export const GETSIMPL = 'Simpl';
export const FLEXIPAY = 'Flexipay';
export const MAESTRO = 'Maestro';
export const PHONEPE = 'Phonepe';
export const PAYPAL = 'Paypal';
export const TRUSTLY = 'Trustly';
export const POLI = 'POLI';
export const GIROPAY = 'Giropay';
export const SOFORT = 'Sofort';
export const BAJAJ_PAY_WALLET = 'Bajaj Pay Wallet';
export const RECURRING_METHOD_HEADERS = ['Cards Recurring', 'UPI Autopay'];

export const DISABLED_INSTRUMENT = [
  ZESTMONEY,
  GETSIMPL,
  FLEXIPAY,
  MAESTRO,
  PHONEPE,
  PAYPAL,
  TRUSTLY,
  POLI,
  GIROPAY,
  SOFORT,
  BAJAJ_PAY_WALLET,
];

export const DEACTIVATED = 'deactivated';

export const INSTRUMENT_SLUGS = {
  RECURRING: 'recurring',
  NETBANKING: 'netbanking',
  CARDS: 'cards',
  INTERNATIONAL: 'international',
};

export const statusClass = {
  Request: 'btn btn-primary',
  account_linkable: 'btn btn-primary',
  requestable: 'btn btn-primary',
  cancelled: 'btn btn-primary',
  activated: 'activated status',
  updated: 'activated status',
  requested: 'requested status',
  pending: 'pending status',
  rejected: 'rejected status',
  action_required: 'action-required status',
  under_review: 'action-required under-review status',
  activated_action_required: 'activated-action-required status',
  greyed: 'btn btn-primary disabled',
  reinitiated: 'requested status',
};

export const statusPopoverText = {
  activated: 'Payment method active on your checkout',
  requested: 'Payment method has been requested',
  reinitiated: 'Payment method has been reinitiated',
  pending: 'Your request has been forwarded for approval',
  rejected: 'Your request has been rejected',
  action_required: 'Action required on your end to complete the process',
  activated_action_required: 'Payment method active on your checkout',
  under_review: 'Information provided is under review',
  updated: 'Information provided is updated',
};

export const additionalDetailsStatus = {
  open: 'under_review',
  approved: 'under_review',
  rejected: 'rejected',
  failed: 'rejected',
  executed: 'updated',
  closed: 'updated',
};

export const STANDARD_PRICING_URL = 'https://razorpay.com/pricing/';

export const WEBSITE_LINKS_MAPPING = {
  'merchant_business_detail|website_details|pricing': 'Pricing policy link',
  'merchant_business_detail|website_details|terms': 'Terms and condition  link',
  'merchant_business_detail|website_details|privacy': 'Privacy policy link',
  'merchant_business_detail|website_details|contact': 'Contact us link',
  'merchant_business_detail|website_details|refund': 'Refund policy link',
  'merchant_business_detail|website_details|cancellation': 'Cancellation policy link',
  'merchant_business_detail|website_details|about': 'About us link',
  'merchant_business_detail|website_details|shipping': 'Shipping Policy',
};

export const WEBSITE_FIELDS = [
  'merchant_business_detail|website_details|pricing',
  'merchant_business_detail|website_details|terms',
  'merchant_business_detail|website_details|privacy',
  'merchant_business_detail|website_details|contact',
  'merchant_business_detail|website_details|refund',
  'merchant_business_detail|website_details|cancellation',
  'merchant_business_detail|website_details|about',
  'merchant_business_detail|website_details|shipping',
];

export const PLACEHOLDERS = {
  'merchant_business_detail|website_details|pricing': 'https://example.com/pricing',
  'merchant_business_detail|website_details|terms': 'https://example.com/terms',
  'merchant_business_detail|website_details|privacy': 'https://example.com/privacy',
  'merchant_business_detail|website_details|contact': 'https://example.com/contact',
  'merchant_business_detail|website_details|refund': 'https://example.com/refund',
  'merchant_business_detail|website_details|cancellation': 'https://example.com/cancellation',
  'merchant_business_detail|website_details|about': 'https://example.com/about',
};
export const CC_EMI_SEPARATE_INSTRUMENT = ['credit.sbi', 'credit.hdfc', 'credit.amex'];

export const FIELD_STATUS = {
  missing: 'Missing/Unverified Details',
  verified: 'Verified Details',
};

export const DETAILS_TYPE = {
  business: 'Business Details',
  website: 'Website Details',
};
