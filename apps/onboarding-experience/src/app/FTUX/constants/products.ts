import { ArrowUpRightIcon, SparklesIcon } from '@razorpay/blade/components';
import SplitIncomingPaymentsIcon from '@OnboardingExperienceAssets/NoCodeProducts/SplitIncomingPaymentsIcon.svg';
import StorefrontIcon from '@OnboardingExperienceAssets/NoCodeProducts/StorefrontIcon.svg';
import InvoicesIcon from '@OnboardingExperienceAssets/NoCodeProducts/InvoicesIcon.svg';
import CollectPaymentFromCustomerIcon from '@OnboardingExperienceAssets/NoCodeProducts/CollectPaymentFromCustomerIcon.svg';
import PaymentLinksThumbnail from '@OnboardingExperienceAssets/NoCodeProducts/PaymentLinksThumbnail.svg';
import PaymentPagesThumbnail from '@OnboardingExperienceAssets/NoCodeProducts/PaymentPagesThumbnail.svg';
import InvoicesThumbnail from '@OnboardingExperienceAssets/NoCodeProducts/InvoicesThumbnail.svg';
import StorefrontThumbnail from '@OnboardingExperienceAssets/NoCodeProducts/StorefrontThumbnail.svg';
import PaymentButtonThumbnail from '@OnboardingExperienceAssets/NoCodeProducts/PaymentButtonThumbnail.svg';
import AffordabilityThumbnail from '@OnboardingExperienceAssets/NoCodeProducts/AffordabilityThumbnail.svg';
import QrCodesThumbnail from '@OnboardingExperienceAssets/NoCodeProducts/QrCodesThumbnail.svg';
import SubscriptionsThumbnail from '@OnboardingExperienceAssets/NoCodeProducts/SubscriptionsThumbnail.svg';
import SmartCollectThumbnail from '@OnboardingExperienceAssets/NoCodeProducts/SmartCollectThumbnail.svg';
import RouteThumbnail from '@OnboardingExperienceAssets/NoCodeProducts/RouteThumbnail.svg';
import CheckoutRewardsThumbnail from '@OnboardingExperienceAssets/NoCodeProducts/CheckoutRewardsThumbnail.svg';
import MagicCheckoutThumbnail from '@OnboardingExperienceAssets/NoCodeProducts/MagicCheckoutThumbnail.svg';
import OptimizerThumbnail from '@OnboardingExperienceAssets/NoCodeProducts/OptimizerThumbnail.svg';

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
    image: InvoicesThumbnail,
    tagIcon: SparklesIcon,
    tagText: 'Set up in 2 mins',
    title: 'Invoices',
    description: 'Create GST based invoices instantly and notify your customer via sms or email.',
    linkUrl: '/app/invoices',
    linkText: 'Use now',
    linkIcon: ArrowUpRightIcon,
  },
  [PRODUCT_TYPES.PAYMENT_BUTTON]: {
    image: PaymentButtonThumbnail,
    tagIcon: SparklesIcon,
    tagText: 'Set up in 2 mins',
    title: 'Payment Button',
    description:
      'Create a Payment Button to collect payments on your website or blog with a simple one-line code.',
    linkUrl: '/app/paymentbuttons',
    linkText: 'Use now',
    linkIcon: ArrowUpRightIcon,
  },
  [PRODUCT_TYPES.AFFORDABILITY_WIDGET]: {
    image: AffordabilityThumbnail,
    tagIcon: SparklesIcon,
    tagText: 'Set up in 2 mins',
    title: 'Affordability widget',
    description:
      'Highlight EMI, Pay Later, and Offers on product pages to drive early interest and more sales.',
    linkUrl: '/app/magic',
    linkText: 'Use now',
    linkIcon: ArrowUpRightIcon,
  },
  [PRODUCT_TYPES.QR_CODES]: {
    image: QrCodesThumbnail,
    tagIcon: SparklesIcon,
    tagText: 'Set up in 2 mins',
    title: 'QR Codes',
    description:
      'Make as many QR codes as you need. Share or print them so customers can scan and pay.',
    linkUrl: '/app/qr_codes',
    linkText: 'Use now',
    linkIcon: ArrowUpRightIcon,
  },
  [PRODUCT_TYPES.SUBSCRIPTIONS]: {
    image: SubscriptionsThumbnail,
    tagIcon: SparklesIcon,
    tagText: 'Set up in 2 mins',
    title: 'Subscriptions',
    description:
      'Create subscription plans with your own pricing and billing cycles. Share links to start collecting recurring payments.',
    linkUrl: '/app/subscriptions',
    linkText: 'Use now',
    linkIcon: ArrowUpRightIcon,
  },
  [PRODUCT_TYPES.SMART_COLLECT]: {
    image: SmartCollectThumbnail,
    tagIcon: SparklesIcon,
    tagText: 'Set up in 2 mins',
    title: 'Smart Collect',
    description:
      'Track UPI, IMPS, NEFT, and RTGS payments in real-time. Get instant collections and let reconciliation happen automatically.',
    linkUrl: '/app/smartcollect/virtualaccounts',
    linkText: 'Use now',
    linkIcon: ArrowUpRightIcon,
  },
  [PRODUCT_TYPES.ROUTE]: {
    image: RouteThumbnail,
    tagIcon: SparklesIcon,
    tagText: 'Set up in 2 mins',
    title: 'Route',
    description:
      'Link vendor, seller, or service provider accounts to easily send payments from your transactions.',
    linkUrl: '/app/route/payments',
    linkText: 'Use now',
    linkIcon: ArrowUpRightIcon,
  },
  [PRODUCT_TYPES.CHECKOUT_REWARDS]: {
    image: CheckoutRewardsThumbnail,
    tagIcon: SparklesIcon,
    tagText: 'Set up in 2 mins',
    title: 'Checkout Rewards',
    description:
      'Boost sales with exciting rewards for every customer purchase. Drive more conversions and repeat orders.',
    linkUrl: '/app/checkout-rewards',
    linkText: 'Use now',
    linkIcon: ArrowUpRightIcon,
  },
  [PRODUCT_TYPES.MAGIC_CHECKOUT]: {
    image: MagicCheckoutThumbnail,
    tagIcon: SparklesIcon,
    tagText: 'Set up in 2 mins',
    title: 'Magic Checkout',
    description:
      'Drive more orders and reduce RTOs with a faster, smarter checkout experience your customers will love.',
    linkUrl: '/app/magic',
    linkText: 'Use now',
    linkIcon: ArrowUpRightIcon,
  },
  [PRODUCT_TYPES.OPTIMIZER]: {
    image: OptimizerThumbnail,
    tagIcon: SparklesIcon,
    tagText: 'Set up in 2 mins',
    title: 'Optimizer',
    description:
      'Route transactions to multiple gateways with one switch on Optimizer. Boost success rates by over 10%.',
    linkUrl: '/app/optimizer',
    linkText: 'Use now',
    linkIcon: ArrowUpRightIcon,
  },
  [PRODUCT_TYPES.STOREFRONT]: {
    image: StorefrontThumbnail,
    tagIcon: SparklesIcon,
    tagText: 'Set up in 2 mins',
    title: 'Storefront',
    description:
      'Display your products on your Razorpay Webstore, accept orders, and enhance listings with images and descriptions.',
    linkUrl: '/app/paymentpages',
    linkText: 'Use now',
    linkIcon: ArrowUpRightIcon,
  },
  [PRODUCT_TYPES.PAYMENT_PAGES]: {
    image: PaymentPagesThumbnail,
    tagIcon: SparklesIcon,
    tagText: 'Set up in 2 mins',
    title: 'Payment Pages',
    description:
      'Create a simple checkout page to accept payments online. No website or coding needed.',
    linkUrl: '/app/paymentpages',
    linkText: 'Use now',
    linkIcon: ArrowUpRightIcon,
  },
  [PRODUCT_TYPES.PAYMENT_LINKS]: {
    image: PaymentLinksThumbnail,
    tagIcon: SparklesIcon,
    tagText: 'Set up in 2 mins',
    title: 'Payment Links',
    description:
      'Generate a link you can share with customers to get paid instantly, without any setup.',
    linkUrl: '/app/paymentlinks',
    linkText: 'Use now',
    linkIcon: ArrowUpRightIcon,
  },
};

export const PRODUCT_CATEGORIES = [
  {
    image: CollectPaymentFromCustomerIcon,
    description: 'I want to collect payments from customers directly',
    products: [PRODUCT_TYPES.PAYMENT_BUTTON],
  },
  {
    image: StorefrontIcon,
    description: 'I want to create a store or landing page for my business and collect payments',
    products: [PRODUCT_TYPES.PAYMENT_PAGES, PRODUCT_TYPES.STOREFRONT],
  },
  {
    image: InvoicesIcon,
    description: 'I want to issue invoices for my business.',
    products: [PRODUCT_TYPES.INVOICES],
  },
  {
    image: SplitIncomingPaymentsIcon,
    description:
      'I want to split incoming payments into linked accounts for managing settlements and reconciliation.',
    products: [PRODUCT_TYPES.PAYMENT_LINKS],
  },
];

export const ALL_PRODUCTS = Object.values(AVAILABLE_PRODUCTS_MAP);
