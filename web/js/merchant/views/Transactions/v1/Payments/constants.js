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
  'insufficient_funds',
  'transaction_limit_exceeded',
  'payment_cancelled',
  'payment_failed',
  'bank_account_invalid',
  'debit_instrument_blocked',
  'transaction_limit_exceeded',
  'mandate_not_active',
  'debit_instrument_blocked',
  'payment_cancelled',
];

export const TITLE =
  'The information in the failed transaction report is based on the debit transaction response file / report received from the sponsor bank or NPCI, as applicable. The above information is being shared for your reference and cannot be construed as a Bounce Memo issued by Banks under the Negotiable Instruments Act, 1881.';
