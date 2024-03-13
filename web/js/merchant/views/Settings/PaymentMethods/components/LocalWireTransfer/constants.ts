import { DetailFieldType } from 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/types';

export const DETAIL_FIELDS: Array<DetailFieldType> = [
  { label: 'Routing Code', key: 'Routing Code' },
  { label: 'Routing Type', key: 'Routing Type' },
  { label: 'Account Number', key: 'Account Number' },
  { label: 'Beneficiary Name', key: 'Beneficiary Name' },
  { label: 'Beneficiary Bank Name', key: 'Bank Name' },
  { label: 'Beneficiary Address', key: 'Bank Address' },
];

export const VIRTUAL_ACCOUNTS = {
  USD: 'USD',
  SWIFT: 'SWIFT',
  GBP: 'GBP',
  EUR: 'EUR',
} as const;

export const ACTIVATED = 'activated';
export const DEACTIVATED = 'deactivated';
export const VA_USD = 'USD';
export const VA_SWIFT = 'SWIFT';
export const RAZORPAY_SUPPORT_LINK = 'https://razorpay.com/support/#request';
export const B2B_EXPORTS_TNC_LINK = 'https://razorpay.com/terms/local-bank-transfer';

export const REQUEST_ACCOUNT_TYPE = {
  [VIRTUAL_ACCOUNTS.USD]: 'localBankTransfer',
  [VIRTUAL_ACCOUNTS.SWIFT]: 'intBankTransfer',
  [VIRTUAL_ACCOUNTS.GBP]: 'localBankTransferGBP',
  [VIRTUAL_ACCOUNTS.EUR]: 'localBankTransferEUR',
};

const USD_ACCOUNT_ACTIVATION_INFO = [
  "The minimum transaction supported under this flow is 150 USD. Currently, we can't settle any transaction lesser than 150 USD.",
  'Submission of Invoice and billing address is required for every transaction to initiate settlement. (In the case of physical goods AWB copy needs to be uploaded.)',
  'Refunds are not supported for these transactions.',
  'ODS is not supported for this flow. On enabling this flow, your On-demand and instant settlements will be disabled.',
];

const SWIFT_ACCOUNT_ACTIVATION_INFO = [
  ...USD_ACCOUNT_ACTIVATION_INFO,
  'We do not support MYR, PHP, IDR, BHD, BGN, CNY, ILS, KWD, OMR, QAR, RUB, THB, UGX, BGN, and MXN currencies.',
];

export const ACTIVATION_POPUP_CONTENT = {
  [VIRTUAL_ACCOUNTS.USD]: {
    title: 'Request for USD Currency Bank Account',
    description:
      'You will be able to accept USD payments via ACH bank transfer with your local currency bank account.',
    faqs: USD_ACCOUNT_ACTIVATION_INFO,
  },
  [VIRTUAL_ACCOUNTS.SWIFT]: {
    title: 'Request for International Bank Account',
    description:
      'You will be able to accept SWIFT payments via International bank transfer with your International bank account.',
    faqs: SWIFT_ACCOUNT_ACTIVATION_INFO,
  },
  [VIRTUAL_ACCOUNTS.GBP]: {
    title: 'Request for GBP Currency Bank Account',
    description:
      'You will be able to accept GBP payments via FPS bank transfer with your local currency bank account.',
    faqs: USD_ACCOUNT_ACTIVATION_INFO,
  },
  [VIRTUAL_ACCOUNTS.EUR]: {
    title: 'Request for EUR Currency Bank Account',
    description:
      'You will be able to accept EUR payments via SEPA bank transfer with your local currency bank account.',
    faqs: USD_ACCOUNT_ACTIVATION_INFO,
  },
};

export const MCC_CODE_NOT_ELIGIBLE_ERROR = 'we do not support ach and swift account for the mcc';

export const DISABLE_REQUEST_TOOLTIP =
  "Please go to the 'More Intl Payment methods' section and submit your KYC details to initiate this request";
