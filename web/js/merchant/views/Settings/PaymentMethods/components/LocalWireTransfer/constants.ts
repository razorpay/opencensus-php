import { DetailFieldType } from 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/types';

export const DETAIL_FIELDS: Array<DetailFieldType> = [
  { label: 'Routing Code', key: 'Routing Code' },
  { label: 'Routing Type', key: 'Routing Type' },
  { label: 'Account Number', key: 'Account Number' },
  { label: 'Beneficiary Name', key: 'Beneficiary Name' },
  { label: 'Beneficiary Bank Name', key: 'Bank Name' },
  { label: 'Beneficiary Address', key: 'Bank Address' },
];

export const ACTIVATED = 'activated';
export const DEACTIVATED = 'deactivated';
export const VA_USD = 'USD';
export const VA_SWIFT = 'SWIFT';
export const RAZORPAY_SUPPORT_LINK = 'https://razorpay.com/support/#request';
