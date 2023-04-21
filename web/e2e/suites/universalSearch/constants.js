export const SEARCH_TERMS_WITH_EXPECTED_RESULTS = [
  {
    searchTerm: 'transa',
    expectedSearchResults: ['Transactions', 'Transactions limits', 'Route'],
  },
  {
    searchTerm: 'settings',
    expectedSearchResults: [
      'Account & Settings',
      'SMS notification',
      'Email notification',
      'Capture and refund settings',
    ],
  },
  {
    searchTerm: 'details',
    expectedSearchResults: [
      'settlement details',
      'GST details',
      'Customer support details',
      'Contact details',
      'Business details',
      'Bank account details',
    ],
  },
  {
    searchTerm: 'create',
    expectedSearchResults: [
      'Razorpay.me Link',
      'Payment pages',
      'Payment links',
      'Payment button',
      'Invoices',
      'Contact details',
      'Route',
      'API Keys',
      'QR Code',
      'Smart Collect',
    ],
  },
  {
    searchTerm: 'email',
    expectedSearchResults: [
      'Customer support details',
      'Contact details',
      'Account & Settings',
      'Email notification',
    ],
  },
  {
    searchTerm: 'status',
    expectedSearchResults: ['Settlements', 'Refunds', 'Transactions'],
  },
  {
    searchTerm: 'user',
    expectedSearchResults: ['Manage team', 'Customer support details'],
  },
  {
    searchTerm: 'credit',
    expectedSearchResults: ['Credits', 'Line of credit'],
  },
  {
    searchTerm: 'bank',
    expectedSearchResults: ['X Banking', 'Bank account details'],
  },
  {
    searchTerm: 'link',
    expectedSearchResults: ['Payment links', 'Razorpay.me Link', 'Reminders'],
  },
];

export const COPY_TEXTS = {
  NO_RESULTS_FOUND_TEXT: {
    TITLE: 'No search results found',
    TEXT: 'You can search for payment products, Account & Settings, and more',
  },
  POPULAR_SEARCHES: 'Popular searches',
};
