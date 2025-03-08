export const state = {
  session: {
    user: {
      isAllowedView: () => true,
    },
  },
  leftNav: {
    loading: false,
    error: null,
    data: [],
  },
};

export const navigationApi = {
  sections: [
    {
      section_name: 'PAYMENT PRODUCTS',
      section_id: 'payment_products',
      product_options: [
        {
          title: 'QR Codess',
          product_id: 'qr_codes',
          category: 'popular',
          tags: [],
        },
        {
          title: 'Payment Links',
          product_id: 'payment_links',
          category: 'popular',
          tags: [],
        },
        {
          title: 'Payment Pages',
          product_id: 'payment_pages',
          category: 'popular',
          tags: [],
        },
        {
          title: 'Smart Collect',
          product_id: 'smart_collect',
          category: 'popular',
          tags: [],
        },
        {
          title: 'Subscriptions',
          product_id: 'subscriptions',
          category: 'popular',
          tags: [],
        },
        {
          title: 'Payment Button',
          product_id: 'payment_button',
          category: 'popular',
          tags: [],
        },
        {
          title: 'Invoices',
          product_id: 'invoices',
          category: 'popular',
          tags: [],
        },
        {
          title: 'POS',
          product_id: 'pos',
          category: 'promoted',
          tags: ['NEW'],
        },
        {
          title: 'Route',
          product_id: 'route',
          category: '',
          tags: [],
        },
        {
          title: 'Checkout Rewards',
          product_id: 'checkout_rewards',
          category: '',
          tags: [],
        },
        {
          title: 'Affiliate Accounts',
          product_id: 'affiliate_accounts',
          category: '',
          tags: [],
        },
        {
          title: 'Magic Checkout',
          product_id: 'magic_checkout',
          category: '',
          tags: [],
        },
        {
          title: 'Affordability',
          product_id: 'affordability',
          category: '',
          tags: [],
        },
        {
          title: 'Payment Metrics',
          product_id: 'payment_metrics',
          category: '',
          tags: [],
        },
        {
          title: 'Razorpay.me Link',
          product_id: 'payment_handle',
          category: '',
          tags: [],
        },
        {
          title: 'Earnings',
          product_id: 'earnings',
          category: '',
          tags: [],
        },
        {
          title: 'Transfers',
          product_id: 'transfers',
          category: '',
          tags: [],
        },
        {
          title: 'Reversals',
          product_id: 'reversals',
          category: '',
          tags: [],
        },
        {
          title: 'Optimizer',
          product_id: 'optimizer',
          category: '',
          tags: [],
        },
        {
          title: 'Applications',
          product_id: 'applications',
          category: '',
          tags: [],
        },
        {
          title: 'Stores',
          product_id: 'stores',
          category: '',
          tags: [],
        },
        {
          title: 'BBPS',
          product_id: 'bbps',
          category: '',
          tags: [],
        },
        {
          title: 'BillMe',
          product_id: 'bill_me',
          category: '',
          tags: [],
        },
      ],
      max_default_options: '3',
    },
    {
      section_name: 'BANKING PRODUCTS',
      section_id: 'banking_products',
      product_options: [
        {
          title: 'X Banking',
          product_id: 'x_banking',
          category: 'popular',
          tags: [],
        },
        {
          title: 'X Corporate Cards',
          product_id: 'x_corporate_cards',
          category: 'popular',
          tags: [],
        },
        {
          title: 'X Payroll',
          product_id: 'x_payroll',
          category: 'popular',
          tags: [],
        },
        {
          title: 'Cash Advance',
          product_id: 'cash_advance',
          category: 'popular',
          tags: [],
        },
        {
          title: 'Line of Credit',
          product_id: 'line_of_credit',
          category: 'popular',
          tags: [],
        },
        {
          title: 'X Vendor Payments',
          product_id: 'x_vendor_payments',
          category: 'popular',
          tags: [],
        },
      ],
      max_default_options: '3',
    },
  ],
};
