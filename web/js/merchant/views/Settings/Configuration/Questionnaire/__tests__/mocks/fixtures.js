export const CURRENCY_LIST = {
  INR: {
    code: '356',
    denomination: 100,
    min_value: 100,
    min_auth_value: 100,
    symbol: '₹',
    name: 'Indian Rupee',
  },
  USD: {
    code: '357',
    denomination: 100,
    min_value: 100,
    min_auth_value: 100,
    symbol: '$',
    name: 'US Dollar',
  },
};

export const purposeCodeList = [
  {
    purposeGroup: 'Mock Group',
    codes: [
      { purposeCode: 'Mock Code 1', description: 'Mock Description' },
      { purposeCode: 'Mock Code 2', description: 'Mock Description' },
    ],
  },
];

export const defaultPurposeCode = { purposeCode: 'Mock Code 2', description: 'Mock Description' };
