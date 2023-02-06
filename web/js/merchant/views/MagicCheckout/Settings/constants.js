import WoocCoupons from 'merchant/views/MagicCheckout/MagicSettings/components/woocommerce/CouponGCSetting';
import WoocShippingTab from 'merchant/views/MagicCheckout/MagicSettings/containers/woocommerce/ShippingWrapper';

import MagicIntelligenceTab from 'merchant/views/MagicCheckout/Settings/containers/MagicIntelligenceTab';
import CheckoutSettingsTab from 'merchant/views/MagicCheckout/MagicSettings/containers/shopify/CheckoutSettingsTab';

import NativeCoupons from 'merchant/views/MagicCheckout/MagicSettings/components/native/CheckoutSettings';
import NativeShippingWrapper from 'merchant/views/MagicCheckout/MagicSettings/containers/native/ShippingWrapper';

import CODOrderAutomation from 'merchant/views/MagicCheckout/CODOrderAutomation';

export const PLATFORMS = {
  SHOPIFY: 'shopify',
  WOOCOMMERCE: 'woocommerce',
  NATIVE: 'native',
};

export const ACCESS_ROLES = ['owner', 'admin'];

export const TABS = {
  [PLATFORMS.SHOPIFY]: [
    {
      className: 'magic-checkout-settings',
      path: '/magic/settings',
      label: 'Store Settings',
      Component: CheckoutSettingsTab,
      condition: (_user) => ACCESS_ROLES.includes(_user.role),
    },
    {
      className: 'intelligence-settings',
      path: '/magic/settings/magic-intelligence',
      label: 'Magic Intelligence',
      Component: MagicIntelligenceTab,
      condition: (_user) => ACCESS_ROLES.includes(_user.role),
    },
    {
      className: 'automation-settings',
      path: '/magic/settings/cod-review-workflow',
      label: 'COD Review Workflow',
      Component: CODOrderAutomation,
      condition: (_user) => _user.isMagicCODOrderAutomationEnabled,
    },
  ],
  [PLATFORMS.WOOCOMMERCE]: [
    {
      className: 'magic-checkout-settings',
      path: '/magic/settings',
      label: 'Checkout Settings',
      Component: WoocCoupons,
      condition: (_user) => ACCESS_ROLES.includes(_user.role),
    },
    {
      className: 'shipping-settings',
      path: '/magic/settings/shipping',
      label: 'Shipping Settings',
      Component: WoocShippingTab,
      tabHeading: 'Shipping Settings',
      condition: (_user) => ACCESS_ROLES.includes(_user.role),
    },
    {
      className: 'intelligence-settings',
      path: '/magic/settings/magic-intelligence',
      label: 'Magic Intelligence',
      Component: MagicIntelligenceTab,
      condition: (_user) => ACCESS_ROLES.includes(_user.role),
    },
    {
      className: 'automation-settings',
      path: '/magic/settings/cod-review-workflow',
      label: 'COD Review Workflow',
      Component: CODOrderAutomation,
      condition: (_user) => _user.isMagicCODOrderAutomationEnabled,
    },
  ],
  [PLATFORMS.NATIVE]: [
    {
      className: 'magic-checkout-settings',
      path: '/magic/settings',
      label: 'Checkout Settings',
      Component: NativeCoupons,
      condition: (_user) => ACCESS_ROLES.includes(_user.role),
    },
    {
      className: 'shipping-settings',
      path: '/magic/settings/shipping',
      label: 'Shipping Settings',
      Component: NativeShippingWrapper,
      tabHeading: 'Shipping Settings',
      condition: (_user) => ACCESS_ROLES.includes(_user.role),
    },
    {
      className: 'intelligence-settings',
      path: '/magic/settings/magic-intelligence',
      label: 'Magic Intelligence',
      Component: MagicIntelligenceTab,
      condition: (_user) => ACCESS_ROLES.includes(_user.role),
    },
    {
      className: 'automation-settings',
      path: '/magic/settings/cod-review-workflow',
      label: 'COD Review Workflow',
      Component: CODOrderAutomation,
      condition: (_user) => _user.isMagicCODOrderAutomationEnabled,
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
