import WoocCoupons from 'merchant/views/MagicCheckout/MagicSettings/components/woocommerce/CouponWrapper';
import WoocShippingTab from 'merchant/views/MagicCheckout/MagicSettings/containers/woocommerce/ShippingWrapper';

import MagicIntelligenceTab from 'merchant/views/MagicCheckout/Settings/containers/MagicIntelligenceTab';
import CheckoutSettingsTab from 'merchant/views/MagicCheckout/MagicSettings/containers/shopify/CheckoutSettingsTab';

import NativeCoupons from 'merchant/views/MagicCheckout/MagicSettings/components/native/CouponWrapper';
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
      label: 'Coupons',
      Component: WoocCoupons,
      tabHeading: 'Coupon Settings',
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
      label: 'Coupons',
      Component: NativeCoupons,
      tabHeading: 'Coupon Settings',
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
