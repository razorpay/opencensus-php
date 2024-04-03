import { CategoryTypes } from 'merchant/views/MagicCheckout/OrderAnalytics/Reports/types';

export const CATEGORY_TYPES: CategoryTypes = {
  ORDER: 'Order',
  CHECKOUT: 'Checkout',
  LANDING_PAGES: 'Landing Pages',
};

export const CATEGORY_TYPES_API = {
  Order: 'orders',
  Checkout: 'checkouts',
  'Landing Pages': 'landing_pages',
};

export const reportsCards = [
  {
    category: CATEGORY_TYPES.ORDER,
    heading: 'Order Reports',
    desc: 'Download full order report - Order ID, Customer details, Items, Payments, Marketing & More',
  },
  {
    category: CATEGORY_TYPES.CHECKOUT,
    heading: 'Checkout Reports',
    desc: 'Download all checkouts created - Gather insights on where customers dropped off, performing campaigns, etc',
  },
  {
    category: CATEGORY_TYPES.LANDING_PAGES,
    heading: 'Landing Pages Reports',
    desc: 'Track conversion and performance of Landing pages',
  },
];
