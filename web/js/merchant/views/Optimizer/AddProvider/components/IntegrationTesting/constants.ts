import { IntegrationStep } from 'merchant/views/Optimizer/AddProvider/types';

export const INTEGRATION_TESTING_STEPS: IntegrationStep[] = [
  {
    title: 'Payment testing',
    value: 'payment_testing',
    active: true,
    success: false,
    failed: false,
    blocked: false,
  },
  {
    title: 'Refund testing',
    value: 'refund_testing',
    active: false,
    success: false,
    failed: false,
    blocked: false,
  },
  {
    title: 'Integration audit summary',
    value: 'integration_audit_summary',
    active: false,
    success: false,
    failed: false,
    blocked: false,
  },
  {
    title: 'Provider settings',
    value: 'provider_settings',
    active: false,
    success: false,
    failed: false,
    blocked: false,
  },
];

export const getRefundDocLink = (gateway: string, integrationType: string): string => {
  const DEFAULT_URL = 'https://razorpay.com/docs/payments/optimizer';
  switch (`${gateway}_${integrationType}`) {
    case 'payu_instant':
      return `${DEFAULT_URL}/payu-instant/`;
    case 'payu_s2s':
      return `${DEFAULT_URL}/payu/`;
    case 'cashfree_instant':
      return `${DEFAULT_URL}/cashfree-instant/`;
    case 'cashfree_s2s':
      return `${DEFAULT_URL}/cashfree/`;
    case 'paytm_instant':
      return `${DEFAULT_URL}/paytm-instant/`;
    case 'paytm_s2s':
      return `${DEFAULT_URL}/paytm-s2s/`;
    default:
      return DEFAULT_URL;
  }
};

export const getRefundFailureText = (gateway: string): string => {
  let refundFailureText = `We were unable to initiate a refund at this time. You can choose to take your integration live and process refunds from your ${gateway} dashboard.`;
  if (gateway === 'paytm') {
    refundFailureText =
      'Refunds are currently disabled on your Paytm account. Please reach out to the Paytm support team to enable refunds via API for your Paytm account.';
  }
  return refundFailureText;
};

export const WALLETS_MAP = {
  paytm: 'Paytm',
  payzapp: 'PayZapp',
  mobikwik: 'MobiKwik',
  payumoney: 'PayUMoney',
  olamoney: 'OlaMoney',
  airtelmoney: 'AirtelMoney',
  amazonpay: 'Amazon Pay',
  freecharge: 'Freecharge',
  jiomoney: 'JioMoney',
  sbibuddy: 'SBI Buddy',
  openwallet: 'OPEN',
  mpesa: 'M PESA',
  phonepe: 'PhonePe',
  paypal: 'Paypal',
};

export const CARD_TYPES = {
  debit_type: 'Debit Card',
  credit_type: 'Credit Card',
};

export const CARD_NETWORKS = {
  VISA: 'Visa Cards',
  MC: 'Mastercard',
  RUPAY: 'Rupay Cards',
  MAES: 'Maestro Cards',
  AMEX: 'American Express Cards',
  DICL: 'Diners Club Cards',
};

export const AUDIT_TYPES = {
  payment: 'payment',
  refund: 'refund',
};
