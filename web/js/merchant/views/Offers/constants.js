export const MAX_DISCOUNT = 2147483647;

export const PAYMENT_NETWORK_MAP = {
  VISA: 'Visa',
  RUPAY: 'RuPay',
  MC: 'MasterCard',
  DICL: 'Diners Club',
  MAES: 'Maestro',
  AMEX: 'American Express',
};

export const OFFER_TYPES = {
  Instant: 'instant',
  Cashback: 'deferred',
  AlreadyDiscounted: 'already_discounted',
};

export const OFFER_TYPE_LABELS = {
  [OFFER_TYPES.Instant]: 'Instant',
  [OFFER_TYPES.Cashback]: 'Cashback',
  [OFFER_TYPES.AlreadyDiscounted]: 'Already Discounted',
};

export const OFFER_TYPES_OPTIONS = [
  { label: '--Please select--', name: '' },
  { label: OFFER_TYPE_LABELS[OFFER_TYPES.Instant], name: OFFER_TYPES.Instant },
  { label: OFFER_TYPE_LABELS[OFFER_TYPES.Cashback], name: OFFER_TYPES.Cashback },
  { label: OFFER_TYPE_LABELS[OFFER_TYPES.AlreadyDiscounted], name: OFFER_TYPES.AlreadyDiscounted },
];

export const ISSUERS = {
  HDFC: 'HDFC Bank',
  HSBC: 'HSBC Bank',
  ICIC: 'ICICI Bank',
  INDB: 'INDUSIND Bank',
  KKBK: 'Kotak Mahindra Bank',
  RATN: 'RBL',
  SCBL: 'Standard Chartered Bank',
  AMEX: 'American Express',
  UTIB: 'Axis Bank',
  YESB: 'Yes Bank',
  CITI: 'Citi Bank',
  SBIN: 'State Bank of India',
  BARB: 'Bank of Baroda Bank',
  IBKL: 'IDBI Bank',
  AUBL: 'AU small finance bank',
  paytm: 'Paytm',
  payzapp: 'PAYZAPP',
  mobikwik: 'MOBIKWIK',
  payumoney: 'PayU Money',
  olamoney: 'OLA Money',
  airtelmoney: 'Airtel Money',
  amazonpay: 'Amazon Pay',
  freecharge: 'Freecharge',
  jiomoney: 'JIO Money',
  sbibuddy: 'SBI buddy',
  openwallet: 'OPEN WALLET',
  mpesa: 'M PESA',
  phonepe: 'Phone Pe',
  paypal: 'Paypal',
  zestmoney: 'Zestmoney',
  walnut369: 'Axio',
  earlysalary: 'Earlysalary',
  hdfc: 'HDFC Bank',
  icic: 'ICICI Bank',
  kkbk: 'Kotak Bank',
  fdrl: 'Federal Bank',
  idfb: 'IDFC First Bank',
  hcin: 'Home Credit Bank',
  barb: 'Bank of Baroda',
};

export const BANK_MAP = {
  HDFC: 'HDFC Bank',
  HSBC: 'HSBC Bank',
  ICIC: 'ICICI Bank',
  INDB: 'INDUSIND Bank',
  KKBK: 'Kotak Mahindra Bank',
  RATN: 'Ratnakar Bank ',
  SCBL: 'Standard Chartered Bank',
  UTIB: 'Axis Bank',
  YESB: 'Yes Bank',
  CITI: 'Citi Bank',
  SBIN: 'State Bank of India',
  BARB: 'Bank of Baroda Bank',
  AMEX: 'American Express',
  IBKL: 'IDBI Bank',
  AUBL: 'AU small finance bank',
  ESFB: 'Equitas Small Finance Bank',
  DCBL: 'DCB Bank',
  STCB: 'SBM Bank India',
};

export const WALLET_MAP = {
  paytm: 'Paytm',
  payzapp: 'PAYZAPP',
  mobikwik: 'MOBIKWIK',
  payumoney: 'PayU Money',
  olamoney: 'OLA Money',
  airtelmoney: 'Airtel Money',
  amazonpay: 'Amazon Pay',
  freecharge: 'Freecharge',
  jiomoney: 'JIO Money',
  sbibuddy: 'SBI Buddy',
  openwallet: 'OPEN',
  mpesa: 'M PESA',
  phonepe: 'Phone Pe',
  paypal: 'Paypal',
};

export const PAYMENT_METHODS = {
  Card: 'card',
  NetBanking: 'netbanking',
  Wallet: 'wallet',
  UPI: 'upi',
  EMI: 'emi',
  PayLater: 'paylater',
  CardLessEmi: 'cardless_emi',
  // Emandate: 'emandate'
};

// TODO: Rename to all caps
export const PaymentMethodsOptions = [
  { label: '--Select Payment method--', name: '' },
  { label: 'Card', name: PAYMENT_METHODS.Card },
  { label: 'Net Banking', name: PAYMENT_METHODS.NetBanking },
  { label: 'Wallet', name: PAYMENT_METHODS.Wallet },
  { label: 'UPI', name: PAYMENT_METHODS.UPI },
  { label: 'EMI', name: PAYMENT_METHODS.EMI },
  { label: 'Pay Later', name: PAYMENT_METHODS.PayLater },
  { label: 'Cardless EMI', name: PAYMENT_METHODS.CardLessEmi },
];

export const SUBSCRIPTION_OFFERS_PAYMENT_METHODS = {
  Card: 'card',
  UPI: 'upi',
  // Emandate: 'emandate'
};

export const SUBSCRIPTION_OFFERS_PAYMENT_METHODS_OPTIONS = [
  { label: '--Select Payment method--', name: '' },
  { label: 'Card', name: PAYMENT_METHODS.Card },
  { label: 'UPI', name: PAYMENT_METHODS.UPI },
  // { label: 'eMandates (NetBanking)', name: PAYMENT_METHODS.Emandate }, we need this in future
];

export const SUBSCRIPTION_OFFERS_PAYMENT_NETWORKS_OPTIONS = [
  { label: '--Select Network--', name: '' },
  { label: 'Visa', name: 'VISA' },
  { label: 'MasterCard', name: 'MC' },
  { label: 'American Express', name: 'AMEX' },
];

export const SUBSCRIPTION_OFFERS_PAYMENT_DC_ISSUERS_OPTIONS = [
  { label: '--Select Issuers--', name: '' },
  { label: 'ICICI Bank', name: 'ICIC' },
  { label: 'Kotak Mahindra Bank', name: 'KKBK' },
  { label: 'Citi Bank', name: 'CITI' },
];

export const PaymentIssuersOptions = [
  { label: '--Select Issuers--', name: '' },
  { label: 'HDFC Bank', name: 'HDFC' },
  { label: 'HSBC Bank', name: 'HSBC' },
  { label: 'ICICI Bank', name: 'ICIC' },
  { label: 'INDUSIND Bank', name: 'INDB' },
  { label: 'Kotak Mahindra Bank', name: 'KKBK' },
  { label: 'Ratnakar Bank', name: 'RATN' },
  { label: 'Standard Chartered Bank', name: 'SCBL' },
  { label: 'Axis Bank', name: 'UTIB' },
  { label: 'Yes Bank', name: 'YESB' },
  { label: 'Citi Bank', name: 'CITI' },
  { label: 'State Bank of India', name: 'SBIN' },
  { label: 'Bank of Baroda Bank', name: 'BARB' },
  { label: 'IDBI Bank', name: 'IBKL' },
  { label: 'AU small finance bank', name: 'AUBL' },
  { label: 'IDFC First bank', name: 'IDFB' },
  { label: 'Equitas Small Finance Bank', name: 'ESFB' },
  { label: 'DCB Bank', name: 'DCBL' },
  { label: 'SBM Bank India', name: 'STCB' },
];

export const PaymentNetworksOptions = [
  { label: '--Select Network--', name: '' },
  { label: 'Visa', name: 'VISA' },
  { label: 'RuPay', name: 'RUPAY' },
  { label: 'MasterCard', name: 'MC' },
  { label: 'Diners Club', name: 'DICL' },
  { label: 'Maestro', name: 'MAES' },
  { label: 'American Express', name: 'AMEX' },
];

export const WalletIssuersOptions = [
  { label: '--Select Issuers--', name: '' },
  { label: 'Paytm', name: 'paytm' },
  { label: 'PAYZAPP', name: 'payzapp' },
  { label: 'MOBIKWIK', name: 'mobikwik' },
  { label: 'PayU Money', name: 'payumoney' },
  { label: 'OLA Money', name: 'olamoney' },
  { label: 'Airtel Money', name: 'airtelmoney' },
  { label: 'Amazon Pay', name: 'amazonpay' },
  { label: 'Freecharge', name: 'freecharge' },
  { label: 'JIO Money', name: 'jiomoney' },
  { label: 'SBI buddy', name: 'sbibuddy' },
  { label: 'OPEN WALLET', name: 'openwallet' },
  { label: 'M PESA', name: 'mpesa' },
  { label: 'Phone Pe', name: 'phonepe' },
  { label: 'Paypal', name: 'paypal' },
  { label: 'Bajaj Pay', name: 'bajajpay' },
];

export const CardLessEmiIssuersOptions = [
  { label: '--Select Issuers--', name: '', minAmount: 0 },
  { label: 'Zestmoney', name: 'zestmoney', minAmount: 99 },
  { label: 'Axio', name: 'walnut369', minAmount: 900 },
  { label: 'Earlysalary', name: 'earlysalary', minAmount: 3000 },
  { label: 'HDFC Bank', name: 'hdfc', minAmount: 5000 },
  { label: 'ICICI Bank', name: 'icic', minAmount: 7000 },
  { label: 'Kotak Bank', name: 'kkbk', minAmount: 3000 },
  { label: 'Federal Bank', name: 'fdrl', minAmount: 5000 },
  { label: 'IDFC First Bank', name: 'idfb', minAmount: 5000 },
  { label: 'Home Credit Bank', name: 'hcin', minAmount: 500 },
  { label: 'Bank of Baroda', name: 'barb', minAmount: 5000 },
];

export const DISCOUNT_TYPES = {
  FLAT: 'flat',
  PERCENT: 'percent',
  NO_COST_EMI: 'no_cost_emi',
};

export const CARD_TYPES = {
  CREDIT: 'credit',
  DEBIT: 'debit',
};

export const APPLICABLE_ON_OPTIONS = [
  { label: 'Plan and Addon Amount', name: 'both' },
  { label: 'Plan Amount', name: 'plan' },
  { label: 'Addon Amount', name: 'addon' },
];

export const REDEMPTION_TYPE_OPTIONS = [
  { label: 'Single Use', name: 'single' },
  { label: 'Limited Number of Cycles', name: 'cycle' },
  { label: 'Forever', name: 'forever' },
];

export const CREDIT_DEBIT_CARDS_OPTIONS = [
  { label: 'Both Credit and Debit Cards', name: '' },
  { label: 'Credit Card', name: 'credit' },
  { label: 'Debit Card', name: 'debit' },
];

export const EMI_CARDS_OPTIONS = [
  { label: 'Credit Card', name: 'credit' },
  { label: 'Debit Card', name: 'debit' },
];

export const EMI_DEBIT_CARD_BANK_OPTIONS = [
  { label: '--Select Issuers--', name: '' },
  { label: 'HDFC Bank', name: 'HDFC' },
  { label: 'INDUSIND Bank', name: 'INDB' },
  { label: 'KOTAK Bank', name: 'KKBK' },
];

// card network on which offer disable CTA shall be deactivated
export const OFFER_DISABLE_CTA_NETWORKS = ['BAJAJ'];
