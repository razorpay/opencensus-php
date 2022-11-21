import WoocCoupons from 'merchant/views/MagicCheckout/MagicSettings/components/woocommerce/CouponGCSetting';
import WoocShippingTab from 'merchant/views/MagicCheckout/MagicSettings/containers/woocommerce/ShippingWrapper';

import MagicIntelligenceTab from 'merchant/views/MagicCheckout/Settings/containers/MagicIntelligenceTab';
import CheckoutSettingsTab from 'merchant/views/MagicCheckout/MagicSettings/containers/shopify/CheckoutSettingsTab';

import NativeCoupons from 'merchant/views/MagicCheckout/MagicSettings/components/native/CheckoutSettings';
import NativeShippingWrapper from 'merchant/views/MagicCheckout/MagicSettings/containers/native/ShippingWrapper';

export const PLATFORMS = {
  SHOPIFY: 'shopify',
  WOOCOMMERCE: 'woocommerce',
  NATIVE: 'native',
};

export const TABS = {
  [PLATFORMS.SHOPIFY]: [
    {
      label: 'Store Settings',
      Component: CheckoutSettingsTab,
    },
    {
      label: 'Magic Intelligence',
      Component: MagicIntelligenceTab,
    },
  ],
  [PLATFORMS.WOOCOMMERCE]: [
    {
      label: 'Checkout Settings',
      Component: WoocCoupons,
    },
    {
      label: 'Shipping Settings',
      Component: WoocShippingTab,
      tabHeading: 'Shipping Settings',
    },
    {
      label: 'Magic Intelligence',
      Component: MagicIntelligenceTab,
    },
  ],
  [PLATFORMS.NATIVE]: [
    {
      label: 'Checkout Settings',
      Component: NativeCoupons,
    },
    {
      label: 'Shipping Settings',
      Component: NativeShippingWrapper,
      tabHeading: 'Shipping Settings',
    },
    {
      label: 'Magic Intelligence',
      Component: MagicIntelligenceTab,
    },
  ],
};

export const SWITCH_TEXTS = {
  enable: {
    codIntelligenceDisabled: {
      header: 'Enable COD Intelligence?',
      desc:
        'Realtime review of COD orders will be turned on. Magic Checkout will decide which customer sees COD option based on their past buying history.',
      secondaryCtaLabel: 'Cancel',
      primaryCtaLabel: 'Enable COD Intelligence',
    },
    manualReviewDisabled: {
      header: 'Enable manual review of COD orders?',
      desc:
        'Manual review of COD orders will be enabled. You can manually review and take action on each COD order based on RTO risk.',
      secondaryCtaLabel: 'Cancel',
      primaryCtaLabel: 'Enable manual review',
    },
    codIntelligenceEnabled: {
      header: 'Enable COD Intelligence?',
      desc:
        'Manual review of COD orders will be disabled. Magic Checkout will decide which customer sees the COD option based on past buying history.',
      secondaryCtaLabel: 'Cancel',
      primaryCtaLabel: 'Enable COD Intelligence',
    },
    manualReviewEnabled: {
      header: 'Enable manual review of COD orders?',
      desc:
        'Realtime review of COD orders will be disabled. You will have to manually review and take action on each COD order based on RTO risk.',
      secondaryCtaLabel: 'Cancel',
      primaryCtaLabel: 'Enable manual review',
    },
  },
  disable: {
    codIntelligence: {
      header: 'Disable COD Intelligence?',
      desc:
        'Realtime review of COD orders will be disabled. All customers will see the COD option increasing the risk of RTO.',
      secondaryCtaLabel: 'cancel',
      primaryCtaLabel: 'Disable COD Intelligence',
    },
    manualReview: {
      header: 'Disable manual review of COD orders?',
      desc:
        'Manual review of COD orders will be disabled. You will not get RTO risk related details for your COD orders.',
      secondaryCtaLabel: 'Cancel',
      primaryCtaLabel: 'Disable manual review',
    },
  },
};
