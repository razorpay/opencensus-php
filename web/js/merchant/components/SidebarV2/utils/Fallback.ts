import { SIDEEBAR_PRODUCTS_TITLES } from 'merchant/components/SidebarV2/constants/constants';

export const FALLBACK_PRODUCTS = [
  {
    section_name: 'PAYMENT PRODUCTS',
    section_id: 'payment_products',
    product_options: [
      {
        title: SIDEEBAR_PRODUCTS_TITLES.payment_links,
        product_id: 'payment_links',
        category: 'most_used',
        tags: [],
      },
      {
        title: SIDEEBAR_PRODUCTS_TITLES.payment_pages,
        product_id: 'payment_pages',
        category: 'explored',
        tags: [],
      },
      {
        title: SIDEEBAR_PRODUCTS_TITLES.payment_handle,
        product_id: 'payment_handle',
        category: 'most_used',
        tags: ['NEW'],
      },
      {
        title: SIDEEBAR_PRODUCTS_TITLES.pos,
        product_id: 'pos',
        category: '',
        tags: [],
      },
      {
        title: SIDEEBAR_PRODUCTS_TITLES.invoices,
        product_id: 'invoices',
        category: 'popular',
        tags: [],
      },
      {
        title: SIDEEBAR_PRODUCTS_TITLES.payment_button,
        product_id: 'payment_button',
        category: 'popular',
        tags: [],
      },
      {
        title: SIDEEBAR_PRODUCTS_TITLES.affordability,
        product_id: 'affordability',
        category: 'promoted',
        tags: ['NEW'],
      },
      {
        title: SIDEEBAR_PRODUCTS_TITLES.qr_codes,
        product_id: 'qr_codes',
        category: '',
        tags: [],
      },
      {
        title: SIDEEBAR_PRODUCTS_TITLES.subscriptions,
        product_id: 'subscriptions',
        category: '',
        tags: [],
      },
      {
        title: SIDEEBAR_PRODUCTS_TITLES.smart_collect,
        product_id: 'smart_collect',
        category: '',
        tags: [],
      },
      {
        title: SIDEEBAR_PRODUCTS_TITLES.route,
        product_id: 'route',
        category: '',
        tags: [],
      },
      {
        title: SIDEEBAR_PRODUCTS_TITLES.checkout_rewards,
        product_id: 'checkout_rewards',
        category: '',
        tags: [],
      },
      {
        title: SIDEEBAR_PRODUCTS_TITLES.magic_checkout,
        product_id: 'magic_checkout',
        category: '',
        tags: [],
      },
      {
        title: SIDEEBAR_PRODUCTS_TITLES.optimizer,
        product_id: 'optimizer',
        category: '',
        tags: [],
      },
      {
        title: SIDEEBAR_PRODUCTS_TITLES.stores,
        product_id: 'stores',
        category: '',
        tags: [],
      },
      {
        title: SIDEEBAR_PRODUCTS_TITLES.bbps,
        product_id: 'bbps',
        category: '',
        tags: [],
      },
      {
        title: SIDEEBAR_PRODUCTS_TITLES.payment_metrics,
        product_id: 'payment_metrics',
        category: '',
        tags: [],
      },
    ],
    max_default_options: 3,
  },
  {
    section_name: 'BANKING PRODUCTS',
    section_id: 'banking_products',
    product_options: [
      {
        title: SIDEEBAR_PRODUCTS_TITLES.x_banking,
        product_id: 'x_banking',
        category: 'popular',
        tags: [],
      },
      {
        title: SIDEEBAR_PRODUCTS_TITLES.x_corporate_cards,
        product_id: 'x_corporate_cards',
        category: 'popular',
        tags: [],
      },
      {
        title: SIDEEBAR_PRODUCTS_TITLES.x_payroll,
        product_id: 'x_payroll',
        category: 'popular',
        tags: [],
      },
      {
        title: SIDEEBAR_PRODUCTS_TITLES.cash_advance,
        product_id: 'cash_advance',
        category: 'popular',
        tags: [],
      },
      {
        title: SIDEEBAR_PRODUCTS_TITLES.line_of_credit,
        product_id: 'line_of_credit',
        category: 'popular',
        tags: [],
      },
    ],
    max_default_options: 3,
  },
];

export const LOYALTY_PRODUCTS_SECTION = {
  section_name: 'LOYALTY PRODUCTS',
  section_id: 'issuing',
  product_options: [
    {
      title: SIDEEBAR_PRODUCTS_TITLES.wallet,
      product_id: 'wallet',
      category: '',
      tags: [],
    },
  ],
};
