export const PAYMENT_NETWORK_CODE = {
  AMEX: 'AMEX',
  DICL: 'DICL',
  JCB: 'JCB',
  MAES: 'MAES',
  MC: 'MC',
  RUPAY: 'RUPAY',
  VISA: 'VISA',
  UNP: 'UNP',
  BAJAJ: 'BAJAJ',
} as const;

export const API_NETWORK_CODES_MAP = {
  AMEX: 'amex',
  DICL: 'diners',
  JCB: 'jcb',
  MAES: 'maestro',
  MC: 'mastercard',
  RUPAY: 'rupay',
  VISA: 'visa',
  UNP: 'unionpay',
  BAJAJ: 'bajaj',
} as const;

export const IIN_CARD_NETWORK_MAP = {
  'American Express': 'amex',
  Amex: 'amex',
  'Diners Club': 'diners',
  Maestro: 'maestro',
  MasterCard: 'mastercard',
  RuPay: 'rupay',
  Visa: 'visa',
  'Bajaj Finserv': 'bajaj',
  'Union Pay': 'unionpay',
  unknown: 'unknown',
};

export const CARD_NETWORK_MAP = {
  Amex: API_NETWORK_CODES_MAP.AMEX,
  'Diners Club': API_NETWORK_CODES_MAP.DICL,
  JCB: 'default', // load default icon for JCB
  Maestro: API_NETWORK_CODES_MAP.MAES,
  MasterCard: API_NETWORK_CODES_MAP.MC,
  RuPay: API_NETWORK_CODES_MAP.RUPAY,
  Visa: API_NETWORK_CODES_MAP.VISA,
  UnionPay: API_NETWORK_CODES_MAP.UNP,
} as const;

export const PAYMENT_NETWORK = {
  [PAYMENT_NETWORK_CODE.AMEX]: 'Amex',
  [PAYMENT_NETWORK_CODE.DICL]: 'Diners Club',
  [PAYMENT_NETWORK_CODE.JCB]: 'JCB',
  [PAYMENT_NETWORK_CODE.MAES]: 'Maestro',
  [PAYMENT_NETWORK_CODE.MC]: 'MasterCard',
  [PAYMENT_NETWORK_CODE.RUPAY]: 'RuPay',
  [PAYMENT_NETWORK_CODE.VISA]: 'Visa',
  [PAYMENT_NETWORK_CODE.UNP]: 'UnionPay',
} as const;
