const SUPPORTED_GATEWAYS = {
  atom: {
    'Gateway Name': { data_type: 'string', data_value: 'Atom', terminals_key: '' },
    'Payment Methods': {
      data_type: 'array',
      data_value: ['netbanking'],
      min_length: 1,
      terminals_key: '',
    },
    'Product Id': {
      data_type: 'string',
      data_value: 'product id',
      min_length: 1,
      terminals_key: '',
    },
    'Request Hash Key': {
      data_type: 'string',
      data_value: 'request hash key',
      min_length: 1,
      terminals_key: '',
    },
    'Response Hash Key': {
      data_type: 'string',
      data_value: 'response hash key',
      min_length: 1,
      terminals_key: '',
    },
    'Transaction Password': {
      data_type: 'string',
      data_value: 'transaction password',
      min_length: 1,
      terminals_key: '',
    },
    'User Id': { data_type: 'string', data_value: 'user id', min_length: 1, terminals_key: '' },
  },
  cashfree: {
    'App ID': {
      data_type: 'string',
      data_value: 'app id',
      min_length: 1,
      terminals_key: '',
    },
    'App secret Key': {
      data_type: 'string',
      data_value: 'app secret',
      min_length: 1,
      terminals_key: '',
    },
    'Gateway Name': {
      data_type: 'string',
      data_value: 'Cashfree',
      terminals_key: '',
    },
    'Mandatory Methods': {
      data_type: 'array',
      data_value: ['upi'],
      terminals_key: '',
    },
    'Payment Methods': {
      data_type: 'array',
      data_value: ['card', 'upi', 'netbanking', 'wallet'],
      terminals_key: '',
      meta_data: {
        wallet_metadata: {
          wallets: [
            'mobikwik',
            'phonepe',
            'freecharge',
            'olamoney',
            'airtelmoney',
            'paytm',
            'amazonpay',
            'jiomoney',
          ],
        },
      },
    },
    TPV: {
      data_type: 'array',
      data_value: [0, 1, 2],
      terminals_key: '',
    },
    optimizer_route: {
      data_type: 'bool',
      data_value: 'optimizer_route',
      terminals_key: '',
    },
    optimizer_seamless_disabled: {
      data_type: 'bool',
      data_value: 'optimizer_seamless_disabled',
      terminals_key: '',
    },
  },
  ccavenue: {
    'Access code': {
      data_type: 'string',
      data_value: 'access code',
      min_length: 1,
      terminals_key: '',
    },
    'Gateway Name': { data_type: 'string', data_value: 'CCAvenue', terminals_key: '' },
    MID: { data_type: 'string', data_value: 'mid', min_length: 1, terminals_key: '' },
    'Payment Methods': {
      data_type: 'array',
      data_value: ['card', 'netbanking'],
      terminals_key: '',
    },
    'Working Key': {
      data_type: 'string',
      data_value: 'working key',
      min_length: 1,
      terminals_key: '',
    },
  },
  ingenico: {
    'Encryption IV': {
      data_type: 'string',
      data_value: 'encryption iv',
      min_length: 5,
      terminals_key: '',
    },
    'Encryption Key': {
      data_type: 'string',
      data_value: 'encryption key',
      min_length: 5,
      terminals_key: '',
    },
    'Gateway Name': {
      data_type: 'string',
      data_value: 'Ingenico (Tech Process)',
      terminals_key: '',
    },
    'Merchant Code': {
      data_type: 'string',
      data_value: 'merchant code',
      min_length: 5,
      terminals_key: '',
    },
    'Payment Methods': {
      data_type: 'array',
      data_value: ['card', 'netbanking'],
      terminals_key: '',
    },
  },
  pinelabs: {
    'Access code': {
      data_type: 'string',
      data_value: 'access code',
      min_length: 1,
      terminals_key: '',
    },
    'Gateway Name': { data_type: 'string', data_value: 'PineLabs', terminals_key: '' },
    'Merchant ID': {
      data_type: 'string',
      data_value: 'merchant id',
      min_length: 1,
      terminals_key: '',
    },
    'Payment Methods': { data_type: 'array', data_value: ['card'], terminals_key: '' },
    'Secure secret': {
      data_type: 'string',
      data_value: 'secure secret',
      min_length: 1,
      terminals_key: '',
    },
  },
  upi_mindgate: {
    'Gateway Name': { data_type: 'string', data_value: 'HDFC Mindgate UPI', terminals_key: '' },
    'Payment Methods': {
      data_type: 'array',
      data_value: ['upi'],
      min_length: 1,
      terminals_key: '',
    },
    'Response Hash Key': {
      data_type: 'string',
      data_value: 'response hash key',
      min_length: 1,
      terminals_key: '',
    },
    'Store PG Merchant ID': {
      data_type: 'string',
      data_value: 'store pg merchant id',
      min_length: 1,
      terminals_key: '',
    },
    'VPA Assigned': {
      data_type: 'string',
      data_value: 'vpa assigned',
      min_length: 1,
      terminals_key: '',
    },
  },
  getsimpl_optimizer: {
    'Gateway Name': {
      data_type: 'string',
      data_value: 'Simpl',
    },
    TID: {
      data_type: 'string',
      data_value: 'terminal id',
      terminals_key: '',
    },
    'Payment Methods': {
      data_type: 'array',
      data_value: ['paylater'],
      terminals_key: '',
      meta_data: {
        paylater_metadata: {
          can_select_paylaters: true,
          paylaters: ['getsimpl', 'simpl_pay_in_3'],
        },
      },
    },
  },
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
    Recurring: {
      data_type: 'bool',
      data_value: 'recurring',
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
  checkout_dot_com_optimizer: {
    'Gateway Name': {
      data_type: 'string',
      data_value: 'Checkout.com',
      terminals_key: '',
    },
    'Client ID': {
      data_type: 'string',
      data_value: '',
      min_length: 1,
      terminals_key: '',
    },
    'Client Secret': {
      data_type: 'string',
      data_value: '',
      min_length: 1,
      terminals_key: '',
    },
    'Payment Methods': {
      data_type: 'array',
      data_value: ['card'],
      terminals_key: '',
    },
    Scope: { data_type: 'string', data_value: '', min_length: 1, terminals_key: '' },
    'Grant Type': {
      data_type: 'string',
      data_value: '',
      min_length: 1,
      terminals_key: '',
    },
    'Processing Channel ID': {
      data_type: 'string',
      data_value: '',
      min_length: 1,
      terminals_key: '',
    },
  },
  optimizer_razorpay: {
    'Gateway Acquirer': {
      data_type: 'string',
      data_value: [
        {
          name: 'Axis Bank',
          value: 'axis_vas',
        },
        {
          name: 'HDFC Bank',
          value: 'hdfc_vas',
        },
        {
          name: 'ICICI Bank',
          value: 'icici_vas',
        },
      ],
      terminals_key: '',
    },
    'Gateway Name': {
      data_type: 'string',
      data_value: 'Razorpay',
      terminals_key: '',
    },
    Key: {
      data_type: 'string',
      data_value: 'key',
      min_length: 1,
      terminals_key: '',
    },
    'Payment Methods': {
      data_type: 'array',
      data_value: ['card', 'upi', 'netbanking'],
      terminals_key: '',
    },
    Secret: {
      data_type: 'string',
      data_value: 'secret',
      min_length: 1,
      terminals_key: '',
    },
  },
  netbanking_icici: {
    'Gateway Name': {
      data_type: 'string',
      data_value: 'ICICI Netbanking',
    },
    'SP ID': {
      data_type: 'string',
      data_value: 'SP ID',
      min_length: 1,
      max_length: 0,
      terminals_key: '',
    },
    'Payee ID': {
      data_type: 'string',
      data_value: 'Payee ID',
      min_length: 1,
      max_length: 0,
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
  netbanking_hdfc: {
    'Gateway Name': {
      data_type: 'string',
      data_value: 'HDFC Netbanking',
    },
    'Payee ID': {
      data_type: 'string',
      data_value: 'Payee ID',
      min_length: 1,
      max_length: 0,
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

const TERMINAL_PROVIDERS = [
  {
    Provider_name: 'paytm',
    Description: 'ddssccececdec',
    Gateway: 'paytm',
    Gateway_details: {
      INDUSTRY_TYPE_ID: 'dcdcdcscs',
      KEY: '',
      MID: 'dcdcececec',
      'Payment Methods': ['card', 'netbanking', 'upi', 'wallet'],
      WEBSITE: 'wdww',
      wallet_metadata: { wallets: ['paytm'] },
    },
    Currency: ['INR'],
    Gateway_acquirer: 'paytm',
    Terminal_id: 'IPRZ2Vu2SsoN31',
    Status: 'pending',
  },
  {
    Provider_name: 'HDFC upi mindgate',
    Description: 'test',
    Gateway: 'upi_mindgate',
    Gateway_details: {
      'Payment Methods': ['upi'],
      'Response Hash Key': '',
      'Store PG Merchant ID': 's',
      'VPA Assigned': 'v',
    },
    Currency: ['INR'],
    Gateway_acquirer: 'hdfc',
    Terminal_id: 'ItToeGDgUPERxi',
    Status: 'activated',
  },
  {
    Provider_name: 'pinelabs_poc',
    Description: 'PineLabs live creds are here',
    Gateway: 'pinelabs',
    Gateway_details: {
      'Access code': '88d7b25a-1f63-4335-b7fa-dc9235cfb6fe',
      'Merchant ID': '1487',
      'Payment Methods': ['card'],
      'Secure secret': '',
    },
    Currency: ['INR'],
    Gateway_acquirer: 'pinelabs',
    Terminal_id: 'Ivm039qfHI0lgx',
    Status: 'activated',
  },
  {
    Provider_name: 'ingenico_1',
    Description: 'ingenico',
    Gateway: 'ingenico',
    Gateway_details: {
      'Encryption IV': '2418691025AFCDDF',
      'Encryption Key': '',
      'Merchant Code': 'L655163',
      'Payment Methods': ['card', 'netbanking'],
    },
    Currency: ['INR'],
    Gateway_acquirer: '',
    Terminal_id: 'IwhQmEjXH6qA5v',
    Status: 'activated',
  },
  {
    Provider_name: '',
    Description: 'test',
    Gateway: 'payu',
    Gateway_details: {
      Key: 'gtKFFx',
      Salt: 'eCwWELxi',
      'Payment Methods': ['card', 'netbanking'],
    },
    Currency: ['INR'],
    Gateway_acquirer: '',
    Terminal_id: 'IwhQmEjXH6qA5i',
    Status: 'activated',
  },
  {
    Provider_name: 'test_upi_mindgate',
    Description: 'test',
    Gateway: 'upi_mindgate',
    Gateway_details: {
      'Payment Methods': ['upi'],
      Recurring: false,
      'Response Hash Key': '',
      'Store PG Merchant ID': 'ui8h',
      'UPI Features': {
        tpv: 0,
      },
      'VPA Assigned': 'unv8',
    },
    Currency: ['INR'],
    Gateway_acquirer: 'hdfc',
    Terminal_id: 'JGQR17NvqcYQKF',
    Status: 'activated',
    created_at: 1649325583,
    updated_at: 1701181142,
  },
  {
    Provider_name: 'razorpay',
    Description: 'Razorpay provider',
    Gateway: 'razorpay',
    Gateway_details: {
      'Payment Methods': ['card', 'netbanking', 'upi', 'wallet'],
      wallet_metadata: {
        wallets: [
          'phonepe',
          'paypal',
          'olamoney',
          'freecharge',
          'amazonpay',
          'jiomoney',
          'sbibuddy',
          'mpesa',
          'openwallet',
          'payumoney',
          'payzapp',
          'airtelmoney',
          'mobikwik',
          'phonepeswitch',
          'paytm',
        ],
      },
    },
    Currency: ['INR'],
    Gateway_acquirer: 'razorpay',
    Status: '',
  },
];

export { SUPPORTED_GATEWAYS, TERMINAL_PROVIDERS };
