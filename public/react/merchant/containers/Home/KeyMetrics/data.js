export const tabsMeta = {
  transactionVolume: {
    title: 'Transaction Volume',
    grouping: [
      { value: 'method', text: 'By Payment Method' },
      { value: 'platform', text: 'By Platforms' },
    ],
    options: [],
  },
  numTransactions: {
    title: 'Number of Transactions',
    grouping: [],
    options: [],
  },
  refunds: {
    title: 'Refunds in total',
    grouping: [],
    options: [],
  },
  savedCards: {
    title: 'New Saved Cards',
    grouping: [],
    options: [],
  },
};

export const tabsOrder = [
  'transactionVolume',
  'numTransactions',
  'refunds',
  'savedCards',
];

export default {
  tabsMeta,
  tabsOrder,
};
