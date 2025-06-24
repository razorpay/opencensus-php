import React from 'react';
import {
  StorefrontIcon,
  BoxIcon,
  MagicCheckoutIcon,
  InstantSettlementIcon,
  PaymentPagesIcon,
  ShuffleIcon
} from '@razorpay/blade/components';

export const PAYMENT_PAGES_TYPES = {
  payment_page: 'payment_pages',
  storefront: 'storefront',
};

export const PAGE_CONFIGS = {
  [PAYMENT_PAGES_TYPES.storefront]: {
    id: PAYMENT_PAGES_TYPES.storefront,
    title: 'Razorpay Webstore',
    subtitle: 'Best for businesses selling multiple products',
    features: [
      {
        icon: <StorefrontIcon color="surface.icon.primary.normal" />,
        textDesktop: 'Online store with seamless payment acceptance - no coding required!',
        textMobile: 'Online store with checkout—no coding needed!'
      },
      {
        icon: <BoxIcon color="surface.icon.primary.normal" />,
        textDesktop: 'Ideal for selling multiple products with a catalog',
        textMobile: 'Ideal for selling multiple products with a catalog'
      },
      {
        icon: <MagicCheckoutIcon color="surface.icon.primary.normal" />,
        textDesktop: 'Let customers browse, add to cart, and shop seamlessly',
        textMobile: 'Customers can browse, add to cart & checkout'
      }
    ],
    previewGif: 'https://cdn.razorpay.com/static/assets/storefront/storefront.gif',
    analyticsData: {
      product_template: 'storefront'
    },
    backgroundColor: 'surface.background.primary.subtle'
  },
  [PAYMENT_PAGES_TYPES.payment_page]: {
    id: PAYMENT_PAGES_TYPES.payment_page,
    title: 'Payment Page',
    subtitle: 'Best for businesses selling a single product or service',
    features: [
      {
        icon: <InstantSettlementIcon color="surface.icon.onSea.onSubtle" />,
        textDesktop: 'Custom-branded checkout for seamless payments—no coding required!',
        textMobile: 'Custom checkout for payments - no coding!'
      },
      {
        icon: <PaymentPagesIcon color="surface.icon.onSea.onSubtle" />,
        textDesktop: 'Ideal for businesses selling single products, offerings and registrations',
        textMobile: 'Ideal for selling single products and services'
      },
      {
        icon: <ShuffleIcon color="surface.icon.onSea.onSubtle" />,
        textDesktop: 'Custom form to collect customer inputs and payments on one page',
        textMobile: 'Custom form to gather inputs and payments'
      }
    ],
    previewGif: 'https://cdn.razorpay.com/static/assets/storefront/payment_pages.gif',
    analyticsData: {
      product_template: 'page'
    },
    backgroundColor: 'surface.background.primary.subtle'
  }
};