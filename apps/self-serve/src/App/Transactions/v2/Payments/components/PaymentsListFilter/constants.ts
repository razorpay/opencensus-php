import { COUNTRY_CODES } from '@dashboard/shared-utils/constants';
import {
  ALL_LABEL,
  ALL_VALUE,
  durationOptionsMap,
} from 'apps/self-serve/src/App/Transactions/v2/common/constants';
import { generateOptions } from 'apps/self-serve/src/App/Transactions/v2/common/utils';

export const paymentDurationOptionsMap = {
  ...durationOptionsMap,
};
export const paymentDurationSectionOptions = generateOptions(paymentDurationOptionsMap);
export const paymentDurationSectionName = 'Duration';
export const paymentDurationOptions = [
  {
    section: {
      name: paymentDurationSectionName,
      options: paymentDurationSectionOptions,
    },
  },
];

export const paymentMethodOptionsMap: Record<string, string> = {
  [ALL_VALUE]: ALL_LABEL,
  card: 'Card',
  wallet: 'Wallet',
  cod: 'Cash on Delivery',
  bank_transfer: 'Bank Transfer',
  upi: 'UPI',
  emi: 'EMI',
  offline: 'Offline',
  emandate: 'eMANDATE',
  netbanking: 'Netbanking',
  intl_bank_transfer: 'International Bank Transfer',
  paylater: 'Pay Later',
  app: 'CRED pay',
  aeps: 'AePS',
  fpx: 'FPX',
  transfer: 'Route transfer',
  cardless_emi: 'Cardless EMI',
  nach: 'eNach',
};
export const paymentMethodSectionOptions = generateOptions(paymentMethodOptionsMap);
export const paymentMethodSectionName = 'Payment method';
export const paymentMethodOptions = [
  {
    section: {
      name: paymentMethodSectionName,
      options: paymentMethodSectionOptions,
    },
  },
];

export const statusOptionsMap = {
  [ALL_VALUE]: ALL_LABEL,
  created: 'Created',
  authenticated: 'Authenticated',
  authorized: 'Authorized',
  captured: 'Captured',
  refunded: 'Refunded',
  failed: 'Failed',
};
export const statusSectionOptions = generateOptions(statusOptionsMap);
export const statusSectionName = 'Status';
export const statusOptions = [
  {
    section: {
      name: statusSectionName,
      options: statusSectionOptions,
    },
  },
];

export const searchByOptionsMap = {
  email: 'Email',
  contact: 'Mobile number',
  order_id: 'Order ID',
  id: 'Payment ID',
  notes: 'Notes',
};
export const searchBySectionOptions = generateOptions(searchByOptionsMap);
export const searchBySectionName = 'Search by';
export const searchByOptions = [
  {
    section: {
      name: searchBySectionName,
      options: searchBySectionOptions,
    },
  },
];

export const countryCodeSectionOptions = COUNTRY_CODES.map(({ dial_code, name }) => ({
  title: `${dial_code} ${name}`,
  value: dial_code,
}));
export const countryCodeCodeSectionName = 'Country code';
export const countryCodeOptions = [
  {
    section: {
      name: countryCodeCodeSectionName,
      options: countryCodeSectionOptions,
    },
  },
];

export const paymentChannelOptionsMap = {
  [ALL_VALUE]: ALL_LABEL,
  in_person: 'In Person',
  online: 'Online',
};
export const paymentChannelOptions = generateOptions(paymentChannelOptionsMap);
