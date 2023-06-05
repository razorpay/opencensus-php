import { ORG_CUSTOM_CODE_MAP } from 'merchant/models/User';

export const PAPER_NACH_CARD_BANNER_URL =
  'https://razorpay.com/docs/api/recurring-payments/paper-nach/authorization-transaction/#112-create-an-order';
export const UPDATE_PAYMENT_METHOD_URL =
  'https://razorpay.com/docs/subscriptions/payment-retries/#update-the-payment-method-via-our-hosted-page';
export const topEmandateBankCodes = ['SBIN', 'HDFC', 'ICIC', 'UTIB', 'KKBK'];

export const CARD_AFA_MAX_LIMIT = 15000; // Rs
export const CARD_TOKEN_MAX_AMOUNT = 1000000; // Rs
export const MY_CARD_MAX_AMOUNT = 30000; // RM
export const MAX_TOKEN_AMOUNT = 100000000; // in Paisa
export const MAX_TOKEN_AMOUNT_NACH = 1000000000; // in Paisa

export const GATEWAY_MAX_LIMIT = 20000000; // Paisa

export const UPI_AFA_MAX_LIMIT = 500000; // Paisa
export const UPI_MAX_LIMIT_FOR_NON_BFSI = 10000000; // Paisa

export const EMANDATE_MAX_LIMIT = 100000000; // Paisa

export const DEFAULT_NACH_LIMIT = 10000000; // Rs
export const DEFAULT_UPI_LIMIT = 200000; // Rs
export const DEFAULT_EMANDATE_LIMIT = 99999; // Rs

export const tokenStatuses = ['initiated', 'confirmed', 'rejected', 'cancelled', 'paused'];
export const ONBOARDING_SUBSCRIPTIONS_DESCRIPTION = {
  [ORG_CUSTOM_CODE_MAP.RAZORPAY]:
    'Collect recurring payments from customers with Razorpay Subscriptions APIs',
  [ORG_CUSTOM_CODE_MAP.CURLEC]:
    'Collect recurring payments from customers with Curlec Subscriptions APIs',
};

// max amount allowed without AFA authentication
export const CARD_AFA_MAX_AMOUNT = {
  IN: CARD_AFA_MAX_LIMIT,
  MY: MY_CARD_MAX_AMOUNT,
};

// max amount allowed for card payments
export const CARD_MAX_AMOUNT_ALLOWED = {
  IN: CARD_TOKEN_MAX_AMOUNT,
  MY: MY_CARD_MAX_AMOUNT,
};
