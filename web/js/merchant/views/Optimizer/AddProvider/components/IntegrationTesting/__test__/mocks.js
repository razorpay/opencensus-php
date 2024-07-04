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
