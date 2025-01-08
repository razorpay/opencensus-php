import { PaymentMethodsFields } from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/typings/section';
import { ROUTES_INFO } from 'merchant/views/AccountAndSettings/typings/routes';

export const PaymentMethodsTabsRoutesConfig = {
  [PaymentMethodsFields.CARDS]: ROUTES_INFO.CARDS,
  [PaymentMethodsFields.UPI]: ROUTES_INFO.UPI_QR,
  [PaymentMethodsFields.NETBANKING]: ROUTES_INFO.NETBANKING,
  [PaymentMethodsFields.EMI]: ROUTES_INFO.EMI,
  [PaymentMethodsFields.WALLET]: ROUTES_INFO.WALLET,
  [PaymentMethodsFields.PAYLATER]: ROUTES_INFO.PAY_LATER,
  [PaymentMethodsFields.INTERNATIONAL]: ROUTES_INFO.INTERNATIONAL_PAYMENTS,
  [PaymentMethodsFields.MEAL_CARD]: ROUTES_INFO.MEAL_CARD,
};

export const PAYMENT_METHOD_DOCS = {
  razorpay_docs:
    'https://razorpay.com/docs/payment-gateway/dashboard-guide/settings/payment-methods/',
  curlec_docs: 'https://curlec.com/docs/payments/payment-methods/',
};

export const INTERNATIONAL_METHOD_DOC =
  'https://razorpay.com/docs/payments/payments/international-payments/';
