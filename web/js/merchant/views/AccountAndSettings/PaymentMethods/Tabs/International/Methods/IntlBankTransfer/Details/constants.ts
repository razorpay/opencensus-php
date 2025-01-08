export const RAZORPAY_SUPPORT_LINK = 'https://razorpay.com/support/#request';

export const ACCOUNT_DETAIL_FIELD_MAPPING = [
  { label: 'Routing Code', key: 'routing_code' },
  { label: 'Routing Type', key: 'routing_type' },
  { label: 'Account Number', key: 'account_number' },
  { label: 'Beneficiary Name', key: 'beneficiary_name' },
  { label: 'Beneficiary Bank Name', key: 'bank_name' },
  { label: 'Beneficiary Address', key: 'bank_address' },
];

export const DEACTIVATED = 'deactivated';

export const ACCOUNTS_STATUS = {
  ACTIVATED: 'activated',
  DEACTIVATED: 'deactivated',
} as const;
