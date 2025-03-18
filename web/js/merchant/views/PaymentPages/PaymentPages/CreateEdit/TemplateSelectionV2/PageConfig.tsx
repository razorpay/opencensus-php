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
    title: 'Storefront',
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
    carouselImages: [
      { src: "https://cdn.razorpay.com/static/assets/storefront/SF-1.png", alt: 'Storefront Example 1' },
      { src: "https://cdn.razorpay.com/static/assets/storefront/SF-2.png", alt: 'Storefront Example 2' },
      { src: "https://cdn.razorpay.com/static/assets/storefront/SF-3.png", alt: 'Storefront Example 3' },
      { src: "https://cdn.razorpay.com/static/assets/storefront/SF-4.png", alt: 'Storefront Example 4' },
    ],
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
        textDesktop: 'Create a single-page checkout—no coding, no full store setup needed.',
        textMobile: 'Simple one-page checkout, needs no store!'
      },
      {
        icon: <PaymentPagesIcon color="surface.icon.onSea.onSubtle" />,
        textDesktop: 'Ideal for one-time payments, services, and registrations.',
        textMobile: 'Ideal for services, courses & single item sales'
      },
      {
        icon: <ShuffleIcon color="surface.icon.onSea.onSubtle" />,
        textDesktop: 'No cart or catalog—customers pay directly on the page.',
        textMobile: 'No cart—customers pay instantly.'
      }
    ],
    carouselImages: [
      { src: "https://cdn.razorpay.com/static/assets/storefront/PP-1.png", alt: 'Payment Page Example 1' },
      { src: "https://cdn.razorpay.com/static/assets/storefront/PP-2.png", alt: 'Payment Page Example 2' },
      { src: "https://cdn.razorpay.com/static/assets/storefront/PP-3.png", alt: 'Payment Page Example 3' },
      { src: "https://cdn.razorpay.com/static/assets/storefront/PP-4.png", alt: 'Payment Page Example 4' },
    ],
    analyticsData: {
      product_template: 'page'
    },
    backgroundColor: 'surface.background.primary.subtle'
  }
};