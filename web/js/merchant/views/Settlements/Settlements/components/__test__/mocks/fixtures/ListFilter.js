const user = {
  isSingleReconEnabled: true,
  isOptimizerEnabled: true,
};

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

export const props = {
  form: 'settlementsListFilter',
  count: 25,
  onSubmit: jest.fn(),
  onSearchAnalytics: jest.fn(),
  onClearAnalytics: jest.fn(),
  user,
  terminalProviders,
};
