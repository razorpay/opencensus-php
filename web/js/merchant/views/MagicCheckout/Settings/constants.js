import WoocCoupons from 'merchant/views/MagicCheckout/MagicSettings/components/woocommerce/CouponGCSetting';
import WoocShippingTab from 'merchant/views/MagicCheckout/MagicSettings/containers/woocommerce/ShippingWrapper';

import MagicIntelligenceTab from 'merchant/views/MagicCheckout/Settings/containers/MagicIntelligenceTab';
import CODSettingsTab from 'merchant/views/MagicCheckout/Settings/containers/CODSettingsTab';
import CheckoutSettingsTab from 'merchant/views/MagicCheckout/MagicSettings/containers/shopify/CheckoutSettingsTab';

import NativeCoupons from 'merchant/views/MagicCheckout/MagicSettings/components/native/CheckoutSettings';
import NativeShippingWrapper from 'merchant/views/MagicCheckout/MagicSettings/containers/native/ShippingWrapper';

import CODOrderAutomation from 'merchant/views/MagicCheckout/CODOrderAutomation';

import ConfigDashboard from 'merchant/views/MagicCheckout/CODToPrepaid/ConfigsDashboard';

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
      className: 'cod-settings',
      path: '/magic/settings/cod-settings',
      label: 'COD Settings',
      Component: CODSettingsTab,
      condition: (_user) => _user.isMagicCODEngineEnabled,
    },
    {
      className: 'intelligence-settings',
      path: '/magic/settings/rto-settings',
      label: 'RTO Settings',
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
    {
      className: 'pl-configurations-container',
      path: '/magic/settings/cod-to-prepaid',
      label: 'Convert COD to Prepaid',
      Component: ConfigDashboard,
      condition: (_user) => _user.isMagicPrepayCODEnabled,
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
      path: '/magic/settings/rto-settings',
      label: 'RTO settings',
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
    {
      className: 'pl-configurations-container',
      path: '/magic/settings/cod-to-prepaid',
      label: 'Convert COD to Prepaid',
      Component: ConfigDashboard,
      condition: (_user) => _user.isMagicPrepayCODEnabled,
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
      path: '/magic/settings/rto-settings',
      label: 'RTO settings',
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
      desc: 'Realtime review of COD orders will be turned on. Magic Checkout will decide which customer sees COD option based on their past buying history.',
      secondaryCtaLabel: 'Cancel',
      primaryCtaLabel: 'Enable COD Intelligence',
    },
    manualReviewDisabled: {
      header: 'Enable manual review of COD orders?',
      desc: 'Manual review of COD orders will be enabled. You can manually review and take action on each COD order based on RTO risk.',
      secondaryCtaLabel: 'Cancel',
      primaryCtaLabel: 'Enable manual review',
    },
    codIntelligenceEnabled: {
      header: 'Enable COD Intelligence?',
      desc: 'Manual review of COD orders will be disabled. Magic Checkout will decide which customer sees the COD option based on past buying history.',
      secondaryCtaLabel: 'Cancel',
      primaryCtaLabel: 'Enable COD Intelligence',
    },
    manualReviewEnabled: {
      header: 'Enable manual review of COD orders?',
      desc: 'Realtime review of COD orders will be disabled. You will have to manually review and take action on each COD order based on RTO risk.',
      secondaryCtaLabel: 'Cancel',
      primaryCtaLabel: 'Enable manual review',
    },
    codSettings: {
      header: 'Enable cash on delivery option?',
      desc: 'You are about to enable cash on delivery as a payment option. Your customers will be able to choose cash on delivery as a payment option when making a purchase. Please note that this change will apply to all customers',
      secondaryCtaLabel: 'No, don’t!',
      primaryCtaLabel: 'Yes, enable',
    },
  },
  disable: {
    codIntelligence: {
      header: 'Disable COD Intelligence?',
      desc: 'Realtime review of COD orders will be disabled. All customers will see the COD option increasing the risk of RTO.',
      secondaryCtaLabel: 'cancel',
      primaryCtaLabel: 'Disable COD Intelligence',
    },
    manualReview: {
      header: 'Disable manual review of COD orders?',
      desc: 'Manual review of COD orders will be disabled. You will not get RTO risk related details for your COD orders.',
      secondaryCtaLabel: 'Cancel',
      primaryCtaLabel: 'Disable manual review',
    },
    codSettings: {
      header: 'Disable cash on delivery option?',
      desc: 'You are about to disable cash on delivery as a payment option. Your customers will no longer be able to choose cash on delivery when making a purchase. Please note that this change will apply to all customers',
      secondaryCtaLabel: 'No, don’t!',
      primaryCtaLabel: 'Yes, disable',
    },
  },
};

export const CREDENTIALS_MODAL = {
  woocommerce: {
    desc: 'Magic Checkout requires WooCommerce API credentials to update status of orders based on actions you take on the orders.',
  },
};
export const COD_SETTINGS_INFO = `Use this setting to enable COD on your store and configure the rules for selectively
showing COD to customers based on location, products, etc. as well as for setting the
COD fees. Please note that this will override any COD settings on your
Shopify/WooC store.`;
