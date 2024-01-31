export const fundsPaths = {
  brandAccount: '/gcms/funds/brand-account',
  resellerAccounts: '/gcms/funds/reseller-account',
  programAccounts: '/gcms/funds/program-account',
};

export const BRAND_ACCOUNT_TRANSACTION_STATUS = {
  success: {
    label: 'Success',
    value: 'success',
    color: 'positive',
  },
  failure: {
    label: 'Failure',
    value: 'failure',
    color: 'negative',
  },
};

export const DATE_RANGE_PRESETS: [string, number, string][] = [
  ['All Time', -30, 'days'],
  ['Past 7 Days', -7, 'days'],
  ['Past 30 Days', -30, 'days'],
  ['Past 90 Days', -90, 'days'],
];
