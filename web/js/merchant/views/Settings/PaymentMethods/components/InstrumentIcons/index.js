import Pluxee from 'assets/payment-methods/pluxee.png';

const instrumentIconEnum = {
  airtelmoney: 'airtelmoney',
  amazonpay: 'amazonpay',
  amex: 'amex',
  diners: 'diners',
  earlysalary: 'earlysalary',
  epaylater: 'epaylater',
  freecharge: 'freecharge',
  icici: 'icici',
  instacred: 'instacred',
  jiomoney: 'jiomoney',
  maestro: 'maestro',
  masterCard: 'masterCard',
  mobikwik: 'mobikwik',
  mpesa: 'mpesa',
  olamoney: 'olamoney',
  paypal: 'paypal',
  paytm: 'paytm',
  payumoney: 'payumoney',
  payzapp: 'payzapp',
  phonepe: 'phonepe',
  rupay: 'rupay',
  sbibuddy: 'sbibuddy',
  getsimpl: 'simpl',
  visa: 'visa',
  zestmoney: 'zestmoney',
  itzcash: 'itzcash',
  paycash: 'paycash',
  citibankrewards: 'citibankrewards',
  sezzle: 'sezzle',
  walnut369: 'walnut369',
  trustly: 'trustly',
  poli: 'poli',
  giropay: 'giropay',
  sofort: 'sofort',
  bajajpay: 'bajajpay',
  sodexo: Pluxee,
};

function getIconFn(iconName) {
  if (Object.keys(instrumentIconEnum).includes(iconName)) {
    return instrumentIconEnum[iconName];
  }
  return '';
}

export const getIcon = (iconName) => {
  const iconFn = getIconFn(iconName);

  if (iconFn?.includes('https://')) return iconFn; // sodexo is served via 'assets/payment-methods/'

  return iconName.includes('https://')
    ? iconName
    : `https://cdn.razorpay.com/static/assets/instrument-request/${iconFn}.png`;
};
