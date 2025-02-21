import { paylaterConfigType } from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/types';

export const paylaterConfig: Record<keyof typeof paylaterProviders, paylaterConfigType> = {
  epaylater: {
    name: 'ePayLater',
    display_name: 'ePayLater',
    value: 'epaylater',
  },
  getsimpl: {
    name: 'Simpl',
    display_name: 'Simpl Pay In 3',
    value: 'getsimpl',
  },
  icic: {
    name: 'ICICI Bank PayLater',
    display_name: 'ICICI',
    value: 'icic',
  },
  hdfc: {
    name: 'FlexiPay by HDFC Bank',
    display_name: 'FlexiPay',
    value: 'hdfc',
  },
  lazypay: {
    name: 'LazyPay',
    display_name: 'LazyPay',
    value: 'lazypay',
  },
  kkbk: {
    name: 'kkbk',
    display_name: 'Kotak Mahindra Bank',
    value: 'kkbk',
  },
  amazonpay: {
    name: 'Amazon Pay Later',
    display_name: 'Amazon Pay Later',
    value: 'amazonpay',
  },
  paypal: {
    name: 'Paypal',
    display_name: 'Paypal',
    value: 'paypal',
  },
  rzpx_postpaid: {
    name: 'rzpx_postpaid',
    display_name: 'RazorpayX Postpaid',
    value: 'rzpx_postpaid',
  },
} as const;

export const paylaterProviders = {
  getsimpl: 'getsimpl',
  lazypay: 'lazypay',
  icic: 'icic',
  hdfc: 'hdfc',
  epaylater: 'epaylater',
  kkbk: 'kkbk',
  paypal: 'paypal',
  amazonpay: 'amazonpay',
  rzpx_postpaid: 'rzpx_postpaid',
} as const;

export const paylaterOrder = [
  paylaterProviders.getsimpl,
  paylaterProviders.lazypay,
  paylaterProviders.icic,
  paylaterProviders.hdfc,
  paylaterProviders.epaylater,
  paylaterProviders.kkbk,
  paylaterProviders.paypal,
  paylaterProviders.amazonpay,
  paylaterProviders.rzpx_postpaid,
] as const;
