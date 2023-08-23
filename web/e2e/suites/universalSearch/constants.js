const { getDefaultDateRangeForPayments } = require('../../utils');

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

export const SEARCHABLE_ENTITIES = [
  'Payments',
  'Refunds',
  'Orders',
  'Settlements',
  'Disputes',
  'Invoices',
  'PaymentLinks',
  'PaymentPages',
  'PaymentButtons',
  'Transfers',
  'Reversals',
  'Accounts',
  // 'Subscriptions',
  // 'Plans',
  // 'QRcode',
];

export const ENTITY_SEARCH_KEYS = {
  REFUND_PAYMENT_ID: 'REFUND_PAYMENT_ID',
  PAYMENT_PH_NUMBER: 'PAYMENT_PH_NUMBER',
  PAYMENT_EMAIL: 'PAYMENT_EMAIL',
  ORDER_STATUS: 'ORDER_STATUS',
  ORDER_RANDOM_QUERY: 'ORDER_RANDOM_QUERY',
  SETTLEMENTS_UTR_NUMBER: 'SETTLEMENTS_UTR_NUMBER',
  PAYMENT_PAGES_TITLE: 'PAYMENT_PAGES_TITLE',
  PAYMENT_LINKS_URL: 'PAYMENT_LINKS_URL',
  // QRCODE_STATUS: 'QRCODE_STATUS',
  ACCOUNTS_EMAIL: 'ACCOUNTS_EMAIL',
  REVERSALS_TRANSFER_ID: 'REVERSALS_TRANSFER_ID',
};

export const getEntitySearchResultsRoutes = (searchQuery, key) => {
  const { to, from } = getDefaultDateRangeForPayments();

  const routesBasedOnSearch = {
    REFUND_PAYMENT_ID: `/app/refunds?payment_id=${searchQuery}`,
    PAYMENT_PH_NUMBER: `/app/payments?country_code=+91&contact=${searchQuery}&from=${from}&to=${to}`,
    PAYMENT_EMAIL: `/app/payments?email=${searchQuery}&from=${from}&to=${to}`,
    ORDER_STATUS: `/app/orders?status=${searchQuery}`,
    ORDER_RANDOM_QUERY: `/app/orders?q=${searchQuery}`,
    SETTLEMENTS_UTR_NUMBER: `/app/settlements?utr=${searchQuery}`,
    PAYMENT_LINKS_URL: `/app/paymentlinks?short_url=${searchQuery}`,
    PAYMENT_PAGES_TITLE: `/app/paymentpages?title=${searchQuery}`,
    // QRCODE_STATUS: `/app/qr_codes?status=${searchQuery}`,
    ACCOUNTS_EMAIL: `/app/route/accounts?email_id=${searchQuery}`,
    REVERSALS_TRANSFER_ID: `/app/route/reversals?transfer_id=${searchQuery}`,
  };

  return routesBasedOnSearch[key];
};
