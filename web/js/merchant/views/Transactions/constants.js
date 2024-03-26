export const FIXED_COLUMNS_TRANSACTIONS_V1 = [
  'Payment ID',
  'Razorpay Order ID',
  'Amount',
  'Created At',
  'Status',
];
export const FIXED_COLUMNS_TRANSACTIONS_V2 = [
  'Payment ID',
  'Bank RRN',
  'Amount',
  'Created on',
  'Status',
];
export const OPTIONAL_COLUMNS_TRANSACTIONS_V1 = ['Email', 'Contact'];
export const OPTIONAL_COLUMNS_TRANSACTIONS_V2 = ['Customer detail'];
export const COLUMNS = {
  EMAIL: 'Email',
  CONTACT: 'Contact',
  CUSTOMER_DETAIL: 'Customer detail',
};
export const ERROR_MESSAGES = {
  SAVE_PREFERENCES: 'Failed to save column preferences',
  FETCH_PREFERENCES: 'Failed to fetch merchant column preferences',
  FETCH_COLUMNS: 'Failed to fetch notes columns list',
};
