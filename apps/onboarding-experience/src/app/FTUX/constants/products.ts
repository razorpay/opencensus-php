import CardBanner from 'apps/onboarding-experience/src/assets/CardBanner.svg';
import { SparklesIcon } from '@razorpay/blade/components';

export enum PRODUCT_TYPES {
  PAYMENT_LINKS = 'PAYMENT_LINKS',
  PAYMENT_PAGES = 'PAYMENT_PAGES',
  INVOICES = 'INVOICES',
  PAYMENT_BUTTON = 'PAYMENT_BUTTON',
  AFFORDABILITY_WIDGET = 'AFFORDABILITY_WIDGET',
  QR_CODES = 'QR_CODES',
  SUBSCRIPTIONS = 'SUBSCRIPTIONS',
  SMART_COLLECT = 'SMART_COLLECT',
  ROUTE = 'ROUTE',
  CHECKOUT_REWARDS = 'CHECKOUT_REWARDS',
  MAGIC_CHECKOUT = 'MAGIC_CHECKOUT',
  OPTIMIZER = 'OPTIMIZER',
  STOREFRONT = 'STOREFRONT',
}

export const AVAILABLE_PRODUCTS_MAP = {
  [PRODUCT_TYPES.INVOICES]: {
    asset: CardBanner,
    tagIcon: SparklesIcon,
    tagText: 'Set up in 2 mins',
    title: 'Invoices',
    description: 'Create GST based invoices instantly and notify your customer via sms or email.',
    linkUrl: '/payments/invoices',
    linkText: 'Use Now',
  },
  [PRODUCT_TYPES.PAYMENT_BUTTON]: {
    asset: CardBanner,
    tagIcon: SparklesIcon,
    tagText: 'Set up in 2 mins',
    title: 'Payment Button',
    description:
      'Create a Payment Button to collect payments on your website or blog with a simple one-line code.',
    linkUrl: '/payments/payment-button',
    linkText: 'Use Now',
  },
  [PRODUCT_TYPES.AFFORDABILITY_WIDGET]: {
    asset: CardBanner,
    tagIcon: SparklesIcon,
    tagText: 'Set up in 2 mins',
    title: 'Affordability widget',
    description:
      'Highlight EMI, Pay Later, and Offers on product pages to drive early interest and more sales.',
    linkUrl: '/payments/affordability-widget',
    linkText: 'Use Now',
  },
  [PRODUCT_TYPES.QR_CODES]: {
    asset: CardBanner,
    tagIcon: SparklesIcon,
    tagText: 'Set up in 2 mins',
    title: 'QR Codes',
    description:
      'Make as many QR codes as you need. Share or print them so customers can scan and pay.',
    linkUrl: '/payments/qr-codes',
    linkText: 'Use Now',
  },
  [PRODUCT_TYPES.SUBSCRIPTIONS]: {
    asset: CardBanner,
    tagIcon: SparklesIcon,
    tagText: 'Set up in 2 mins',
    title: 'Subscriptions',
    description:
      'Create subscription plans with your own pricing and billing cycles. Share links to start collecting recurring payments.',
    linkUrl: '/payments/subscriptions',
    linkText: 'Use Now',
  },
  [PRODUCT_TYPES.SMART_COLLECT]: {
    asset: CardBanner,
    tagIcon: SparklesIcon,
    tagText: 'Set up in 2 mins',
    title: 'Smart Collect',
    description:
      'Track UPI, IMPS, NEFT, and RTGS payments in real-time. Get instant collections and let reconciliation happen automatically.',
    linkUrl: '/payments/smart-collect',
    linkText: 'Use Now',
  },
  [PRODUCT_TYPES.ROUTE]: {
    asset: CardBanner,
    tagIcon: SparklesIcon,
    tagText: 'Set up in 2 mins',
    title: 'Route',
    description:
      'Link vendor, seller, or service provider accounts to easily send payments from your transactions.',
    linkUrl: '/payments/route',
    linkText: 'Use Now',
  },
  [PRODUCT_TYPES.CHECKOUT_REWARDS]: {
    asset: CardBanner,
    tagIcon: SparklesIcon,
    tagText: 'Set up in 2 mins',
    title: 'Checkout Rewards',
    description:
      'Boost sales with exciting rewards for every customer purchase. Drive more conversions and repeat orders.',
    linkUrl: '/payments/checkout-rewards',
    linkText: 'Use Now',
  },
  [PRODUCT_TYPES.MAGIC_CHECKOUT]: {
    asset: CardBanner,
    tagIcon: SparklesIcon,
    tagText: 'Set up in 2 mins',
    title: 'Magic Checkout',
    description:
      'Drive more orders and reduce RTOs with a faster, smarter checkout experience your customers will love.',
    linkUrl: '/payments/magic-checkout',
    linkText: 'Use Now',
  },
  [PRODUCT_TYPES.OPTIMIZER]: {
    asset: CardBanner,
    tagIcon: SparklesIcon,
    tagText: 'Set up in 2 mins',
    title: 'Optimizer',
    description:
      'Route transactions to multiple gateways with one switch on Optimizer. Boost success rates by over 10%.',
    linkUrl: '/payments/optimizer',
    linkText: 'Use Now',
  },
  [PRODUCT_TYPES.PAYMENT_PAGES]: {
    asset: CardBanner,
    tagIcon: SparklesIcon,
    tagText: 'Set up in 2 mins',
    title: 'Payment Pages',
    description:
      'Create a simple checkout page to accept payments online. No website or coding needed.',
    linkUrl: '/payments/payment-pages',
    linkText: 'Use Now',
  },
  [PRODUCT_TYPES.STOREFRONT]: {
    asset: CardBanner,
    tagIcon: SparklesIcon,
    tagText: 'Set up in 2 mins',
    title: 'Storefront',
    description:
      'Display your products on your Razorpay Webstore, accept orders, and enhance listings with images and descriptions.',
    linkUrl: '/payments/storefront',
    linkText: 'Use Now',
  },
  [PRODUCT_TYPES.PAYMENT_LINKS]: {
    asset: CardBanner,
    tagIcon: SparklesIcon,
    tagText: 'Set up in 2 mins',
    title: 'Payment Link',
    description:
      'Generate a link you can share with customers to get paid instantly, without any setup.',
    linkUrl: '/payments/payment-links',
    linkText: 'Use Now',
  },
};

export const PRODUCT_CATEGORIES = [
  {
    asset: CardBanner,
    description: 'I want to collect payments from customers directly',
    products: [PRODUCT_TYPES.PAYMENT_BUTTON],
  },
  {
    asset: CardBanner,
    description: 'I want to create a store or landing page for my business and collect payments',
    products: [PRODUCT_TYPES.PAYMENT_PAGES, PRODUCT_TYPES.STOREFRONT],
  },
  {
    asset: CardBanner,
    description: 'I want to issue invoices for my business.',
    products: [PRODUCT_TYPES.INVOICES],
  },
  {
    asset: CardBanner,
    description:
      'I want to split incoming payments into linked accounts for managing settlements and reconciliation.',
    products: [PRODUCT_TYPES.PAYMENT_LINKS],
  },
];

export const ALL_PRODUCTS = Object.values(AVAILABLE_PRODUCTS_MAP);
