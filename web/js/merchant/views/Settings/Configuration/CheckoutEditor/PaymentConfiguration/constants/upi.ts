import { UpiAppConfiguration } from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/types';

const googlePayUpiConfig: UpiAppConfiguration = {
  app_name: 'Google Pay',
  code: 'google_pay',
};

const phonepeUpiConfig: UpiAppConfiguration = {
  code: 'phonepe',
  app_name: 'PhonePe',
};

const paytmUpiConfig: UpiAppConfiguration = {
  app_name: 'PayTM UPI',
  code: 'paytm',
};

const bhimUpiConfig: UpiAppConfiguration = {
  code: 'bhim',
  app_name: 'Bhim',
};

const credUpiConfig: UpiAppConfiguration = {
  app_name: 'CRED',
  code: 'cred',
};

export const UPI_APPS = [
  googlePayUpiConfig,
  phonepeUpiConfig,
  paytmUpiConfig,
  bhimUpiConfig,
  credUpiConfig,
];
