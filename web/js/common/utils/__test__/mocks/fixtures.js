export const currencyList = {
  USD: {
    denomination: 100,
    symbol: '$',
    name: 'US Dollar',
  },
  KWD: {
    denomination: 1000,
    symbol: 'د.ك',
    name: 'Kuwaiti Dinar',
  },
  INR: {
    denomination: 100,
    symbol: '₹',
    name: 'Indian Rupee',
  },
  default: {
    format: jest.fn(),
  },
};

export const ZERO_EXPONENT_CURRENCIES = [
  'BIF',
  'CLP',
  'DJF',
  'GNF',
  'ISK',
  'JPY',
  'KMF',
  'KRW',
  'PYG',
  'RWF',
  'UGX',
  'VUV',
  'XAF',
  'XOF',
  'XPF',
];

export const THREE_EXPONENT_CURRENCIES = ['KWD', 'OMR', 'BHD'];

export const TWO_EXPONENT_CURRENCIES = ['INR', 'USD', 'GBP', 'CAD', 'AUD', 'NZD', 'MYR'];

export const getSplitzExperiments = (experimentIds, result = 'on') => {
  return experimentIds.reduce((prev, current) => {
    prev[current] = {
      id: current,
      name: 'variables',
      variables: {
        result,
      },
      experiment_id: current,
      weight: 100,
    };
    return prev;
  }, {});
};
