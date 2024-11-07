import { PLATFORMS } from 'merchant/views/MagicCheckout/constants';
import { NewOffering } from 'merchant/views/MagicCheckout/MagicDashboard/WhatsNew/types';

import Coupons from 'assets/magic_checkout/whatsnew/coupons.png';
import QuickBuy from 'assets/magic_checkout/whatsnew/quickbuy.png';

export const NEW_OFFERINGS: NewOffering[] = [
  {
    title: 'All new Coupons360',
    description:
      'Boost conversions with our all new smart coupon engine with all the coupon types you need and powerful customisation features',
    image: Coupons,
    ctaLink: '/magic/coupons',
    docLink: (isRCODEnabled: boolean) =>
      isRCODEnabled
        ? 'https://razorpay.com/docs/payments/checkout360/coupon-engine/'
        : 'https://razorpay.com/docs/payments/magic-checkout/shopify/configuration/#method-1-razorpay-dashboard-2',
    condition: (platform, _isRCODEnabled) => platform === PLATFORMS.SHOPIFY,
  },
  {
    title: 'Ultra-fast checkout with QuickBuy',
    description:
      'QuickBuy accelerates online shopping with 1-click checkout, minimising steps for a lightning-fast, frictionless customer experience',
    image: QuickBuy,
    knowMoreLink: 'https://razorpay.com/blog/quickbuy-the-future-is-now/',
    docLink: (_isRCODEnabled: boolean) =>
      'https://razorpay.com/docs/payments/magic-checkout/features/quickbuy?search-string=quickbuy',
    condition: (_platform, isRCODEnabled) => !isRCODEnabled,
  },
];
