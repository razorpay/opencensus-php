import { PLATFORMS } from 'merchant/views/MagicCheckout/constants';
import { NewOffering } from 'merchant/views/MagicCheckout/MagicDashboard/WhatsNew/types';

import Coupons from 'assets/magic_checkout/whatsnew/coupons.png';
import QuickBuy from 'assets/magic_checkout/whatsnew/quickbuy.png';
import RazorpayLogin from 'assets/magic_checkout/whatsnew/razorpay_login.png';

export const NEW_OFFERINGS: NewOffering[] = [
  {
    title: 'All new Coupons360',
    description:
      'Boost conversions with our all new smart coupon engine with all the coupon types you need and powerful customisation features',
    image: Coupons,
    ctaLink: '/magic/coupons',
    docLink: (isRCODEnabled: boolean) =>
      isRCODEnabled
        ? 'https://razorpay.com/docs/payments/cod-magic-checkout/shopify/coupons/'
        : 'https://razorpay.com/docs/payments/magic-checkout/shopify/configuration/#method-1-razorpay-dashboard-2',
    condition: (platform, _isRCODEnabled) => platform === PLATFORMS.SHOPIFY,
  },
  {
    title: 'Ultra-fast checkout with QuickBuy',
    description:
      'QuickBuy accelerates online shopping with 1-click checkout, minimising steps for a lightning-fast, frictionless customer experience',
    image: QuickBuy,
    externalLink: {
      href: 'https://razorpay.com/blog/quickbuy-the-future-is-now/',
      label: 'Know More',
    },
    docLink: (_isRCODEnabled: boolean) =>
      'https://razorpay.com/docs/payments/magic-checkout/features/quickbuy?search-string=quickbuy',
    condition: (_platform, isRCODEnabled) => !isRCODEnabled,
  },
  {
    title: 'Login with Razorpay: One Identity Across Stores',
    description:
      'Eliminate friction with seamless 1-click authentication that recognizes shoppers across the Razorpay merchant network. Increase logged-in users by up to 40% and reduce re-targeting costs while turning anonymous browsers into identified customers ready to purchase.',
    image: RazorpayLogin,
    externalLink: {
      href: 'https://razorpay.typeform.com/to/n5cuHwfz?utm_source=dashboard&utm_medium=form&utm_campaign=whatsnew;',
      label: 'Join Early Access Program',
    },
    condition: (platform, _isRCODEnabled) => platform === PLATFORMS.SHOPIFY,
  },
];
