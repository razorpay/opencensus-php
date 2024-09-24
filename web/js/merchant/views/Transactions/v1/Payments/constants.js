export const PAYMENT_STATUS = {
  CAPTURED: 'captured',
  CREATED: 'created',
  AUTHORIZED: 'authorized',
  FAILED: 'failed',
};

export const REFUND_STATUSES = {
  PROCESSING: 'processing',
};

export const FETCH_EZETAP_KEY_NAME = 'ezetap_appkey';

export const ADDRESS =
  '1st Floor, SJR Cyber, 22, Laskar Hosur Road,\nAdugodi, Bangalore, Karnataka, India - 560030';
export const MAIL = 'contact@razorpay.com';
export const WEBSITE = 'https://razorpay.com';
export const INFO = 'This document is electronically generated and does not require a signature';
export const TRANSACTIONSUMMARY = 'TRANSACTION SUMMARY';
export const MERCHANTTITLE = 'Dear Merchant,';
export const merchantBody = (merchantId) =>
  `As requested by you, please find below the details of the bounced transactions on your MID - ${merchantId}`;
export const NOTE = 'Note:';
export const NOTEBODY =
  'The information as above is certified to be true copy based on the debit transaction response file /  report received from the sponsor bank of Razorpay Software \n Solutions Pvt Ltd or NPCI, as applicable. This Transaction Summary is being shared for your reference and informational purposes only';

export const ERRORCODETOCHECK = [
  'BAD_REQUEST_PAYMENT_ACCOUNT_INSUFFICIENT_BALANCE',
  'BAD_REQUEST_TRANSACTION_AMOUNT_LIMIT_EXCEEDED',
  'BAD_REQUEST_PAYMENT_NETBANKING_CANCELLED_BY_USER',
  'BAD_REQUEST_PAYMENT_FAILED',
  'BAD_REQUEST_ACCOUNT_CLOSED',
  'BAD_REQUEST_PAYMENT_INVALID_ACCOUNT',
  'BAD_REQUEST_PAYMENT_FAILED_EXCEEDS_ARRANGEMENT',
  'BAD_REQUEST_PAYMENT_ACCOUNT_WITHDRAWAL_FROZEN',
  'BAD_REQUEST_EMANDATE_DEBIT_NOT_ALLOWED',
  'BAD_REQUEST_PAYMENT_ACCOUNT_MAX_LIMIT_EXCEEDED',
  'BAD_REQUEST_ACCOUNT_HOLDER_EXPIRED',
  'GATEWAY_ERROR_REQUEST_ERROR',
  'BAD_REQUEST_EMANDATE_CANCELLED_INACTIVE',
  'BAD_REQUEST_PAYMENT_KYC_PENDING',
  'GATEWAY_ERROR_MERCHANT_NOT_ENABLED_FOR_STANDING_INSTRUCTION',
  'GATEWAY_ERROR_PREMATURE_SI_EXECUTION',
  'BAD_REQUEST_PAYMENT_CANCELLED_BY_CUSTOMER',
];

export const TITLE =
  'The information in the failed transaction report is based on the debit transaction response file / report received from the sponsor bank or NPCI, as applicable. The above information is being shared for your reference and cannot be construed as a Bounce Memo issued by Banks under the Negotiable Instruments Act, 1881.';
