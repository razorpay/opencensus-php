export const SUPPORTED_GATEWAYS = {
  paytm: {
    CLIENT_KEY: {
      data_type: 'string',
      data_value: 'Client Key',
      terminals_key: '',
    },
    CLIENT_SECRET: {
      data_type: 'string',
      data_value: 'Client Secret',
      terminals_key: '',
    },
    ENABLE_AUTO_DEBIT: {
      data_type: 'bool',
      data_value: 'enable_auto_debit',
      terminals_key: '',
    },
    'Gateway Name': {
      data_type: 'string',
      data_value: 'PayTm',
      terminals_key: '',
    },
    INDUSTRY_TYPE_ID: {
      data_type: 'string',
      data_value: 'industry type id',
      terminals_key: '',
    },
    KEY: {
      data_type: 'string',
      data_value: 'key',
      min_length: 1,
      terminals_key: '',
    },
    MID: {
      data_type: 'string',
      data_value: 'mid',
      min_length: 1,
      terminals_key: '',
    },
    'Payment Methods': {
      data_type: 'array',
      data_value: ['card', 'upi', 'netbanking', 'wallet'],
      terminals_key: '',
      meta_data: {
        wallet_metadata: {
          wallets: ['paytm'],
        },
      },
    },
    WEBSITE: {
      data_type: 'string',
      data_value: 'website',
      terminals_key: '',
    },
    optimizer_seamless_disabled: {
      data_type: 'bool',
      data_value: 'optimizer_seamless_disabled',
      terminals_key: '',
    },
  },
  payu: {
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
    Salt: {
      data_type: 'string',
      data_value: 'payu salt',
      min_length: 8,
      terminals_key: '',
    },
  },
  netbanking_axis: {
    'Gateway Name': {
      data_type: 'string',
      data_value: 'Axis Netbanking',
      terminals_key: '',
    },
    'Merchant Id': {
      data_type: 'string',
      data_value: 'payee id',
      min_length: 1,
      terminals_key: '',
    },
    'Payment Methods': {
      data_type: 'array',
      data_value: ['netbanking'],
      terminals_key: '',
    },
    TPV: {
      data_type: 'array',
      data_value: [0, 1, 2],
      terminals_key: '',
    },
  },
};

export const TPV_OPTIONS = ['Non TPV', 'TPV Only', 'Both (TPV and Non TPV)'];
