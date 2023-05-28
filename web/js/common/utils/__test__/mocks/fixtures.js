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
