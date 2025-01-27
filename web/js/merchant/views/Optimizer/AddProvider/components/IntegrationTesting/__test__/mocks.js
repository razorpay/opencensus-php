export const RAZORPAY_COVERAGE = [
  {
    method: 'card',
    enabled: true,
    card: {
      debitType: {
        network: ['MC', 'MAES', 'RUPAY', 'VISA'],
      },
      creditType: {
        network: ['VISA', 'MC', 'RUPAY', 'DICL'],
      },
    },
  },
  {
    method: 'upi',
    enabled: true,
    upi: {
      intent: true,
      collect: true,
    },
  },
  {
    method: 'netbanking',
    enabled: true,
    netbanking: {
      banks: ['HDFC', 'SBIN', 'ICIC', 'PUNB_R'],
    },
  },
  {
    method: 'wallet',
    enabled: true,
    wallets: {
      phonepe: true,
      jiomoney: true,
      olamoney: true,
    },
  },
  {
    method: 'emi',
    enabled: true,
  },
  {
    method: 'e-mandate',
    enabled: true,
  },
  {
    method: 'sodexo',
    enabled: true,
  },
];

export const GATEWAY_COVERAGE = [
  {
    method: 'card',
    enabled: true,
    card: {
      debitType: {
        network: ['MC', 'MAES', 'RUPAY', 'VISA'],
      },
      creditType: {
        network: ['VISA', 'MC', 'RUPAY', 'DICL'],
      },
    },
  },
  {
    method: 'upi',
    enabled: true,
    upi: {
      intent: true,
      collect: true,
    },
  },
  {
    method: 'netbanking',
    enabled: true,
    netbanking: {
      banks: ['HDFC', 'SBIN', 'ICIC'],
    },
  },
  {
    method: 'wallet',
    enabled: true,
    wallets: {
      phonepe: true,
      jiomoney: true,
      olamoney: true,
    },
  },
  {
    method: 'emi',
    enabled: true,
  },
  {
    method: 'e-mandate',
    enabled: true,
  },
  {
    method: 'sodexo',
    enabled: true,
  },
];

export const PAYTM_GATEWAY_COVERAGE = [
  {
    enabled: true,
    method: 'upi',
    upi: {
      collect: true,
      intent: true,
    },
  },
  {
    enabled: true,
    method: 'netbanking',
    netbanking: {
      banks: ['SBIN', 'KKBK', 'CNRB', 'PUNB_R', 'IOBA', 'IDIB', 'UBIN', 'YESB'],
    },
  },
  {
    method: 'wallet',
    wallets: {},
  },
  {
    card: {
      creditType: {},
      debitType: {},
    },
    enabled: true,
    method: 'card',
  },
];

export const PROVIDER_SETTING_GATEWAY_META = {
  'Gateway Name': {
    data_type: 'string',
    data_value: 'PayU',
    terminals_key: '',
  },
  Key: {
    data_type: 'string',
    data_value: 'payu key',
    min_length: 6,
    terminals_key: '',
  },
  'Payment Methods': {
    data_type: 'array',
    data_value: ['card', 'upi', 'netbanking', 'emi', 'wallet', 'emandate'],
    terminals_key: '',
    meta_data: {
      wallet_metadata: {
        wallets: [
          'itzcash',
          'airtelmoney',
          'freecharge',
          'oxigen',
          'payzapp',
          'amexeasyclick',
          'olamoney',
          'paycash',
          'jiomoney',
          'citibankrewards',
          'amazonpay',
          'paytm',
          'phonepe',
        ],
      },
    },
  },
  Recurring: {
    data_type: 'bool',
    data_value: 'recurring',
    terminals_key: '',
  },
  Salt: {
    data_type: 'string',
    data_value: 'payu salt',
    min_length: 8,
    terminals_key: '',
  },
  Sodexo: {
    data_type: 'bool',
    data_value: 'sodexo',
    terminals_key: '',
  },
  optimizer_seamless_disabled: {
    data_type: 'bool',
    data_value: 'optimizer_seamless_disabled',
    terminals_key: '',
  },
};
