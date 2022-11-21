const terminalProviders = [
  {
    Provider_name: 'Payu',
    Description: 'testing edit',
    Gateway: 'payu',
    Gateway_details: {
      Key: 'ujik90',
      'Payment Methods': ['card'],
      Salt: '',
    },
    Currency: ['INR'],
    Gateway_acquirer: 'payu',
    Terminal_id: 'HdvEjdKKJMBX89',
  },
  {
    Provider_name: 'razorpay',
    Description: 'Razorpay provider',
    Gateway: 'razorpay',
    Gateway_details: {
      'Payment Methods': ['card', 'netbanking', 'upi', 'wallet'],
      wallet_metadata: {
        wallets: [
          'jiomoney',
          'mobikwik',
          'amazonpay',
          'openwallet',
          'razorpaywallet',
          'payumoney',
          'payzapp',
          'phonepe',
          'paypal',
          'paytm',
          'freecharge',
          'sbibuddy',
          'olamoney',
          'mpesa',
          'phonepeswitch',
          'airtelmoney',
        ],
      },
    },
    Currency: ['INR'],
    Gateway_acquirer: 'razorpay',
  },
];

const user = {
  isSingleReconEnabled: true,
  isOptimizerEnabled: true,
};

const settlements = [
  {
    id: 'setl_K1QFNZ3mxXK0A2',
    entity: 'settlement',
    amount: 0,
    status: 'PROCESSED',
    fees: 0,
    tax: 0,
    settled_by: 'Razorpay',
    optimizer_provider: 'Razorpay',
    created_at: 1659586906,
  },
];

export const props = {
  settlements,
  showBreakup: jest.fn(),
  isLoading: false,
  user,
  terminalProviders,
};
