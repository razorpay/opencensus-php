import lazy from 'merchant/routes/LazyLoader';
import WoocCoupons from 'merchant/views/MagicCheckout/MagicSettings/components/woocommerce/CouponGCSetting';
import WoocShippingTab from 'merchant/views/MagicCheckout/MagicSettings/containers/woocommerce/ShippingWrapper';

import MagicIntelligenceTab from 'merchant/views/MagicCheckout/Settings/containers/MagicIntelligenceTab';
import CODSettingsTab from 'merchant/views/MagicCheckout/Settings/containers/CODSettingsTab';
import ShippingSettingsTab from 'merchant/views/MagicCheckout/Settings/containers/ShippingSettingsTab';
import CheckoutSettingsTab from 'merchant/views/MagicCheckout/MagicSettings/containers/shopify/CheckoutSettingsTab';
import CouponEngineSettingsTab from 'merchant/views/MagicCheckout/MagicSettings/containers/shopify/CouponEngineSettingsTab';

import NativeCoupons from 'merchant/views/MagicCheckout/MagicSettings/components/native/CheckoutSettings';
import NativeShippingWrapper from 'merchant/views/MagicCheckout/MagicSettings/containers/native/ShippingWrapper';

import CODOrderAutomation from 'merchant/views/MagicCheckout/CODOrderAutomation';

import ConfigDashboard from 'merchant/views/MagicCheckout/CODToPrepaid/ConfigsDashboard';

const AnalyticsSettings = lazy(() =>
  import(
    /* webpackChunkName: "MagicAnalyticsSettings" */ 'merchant/views/MagicCheckout/AnalyticsSettings'
  ),
);

export const APP_VIEW_RADIO_OPTIONS = [
  {
    label: 'Magic Checkout',
    value: 'magic_checkout',
  },
  {
    label: 'Shopify One-page Checkout(MagicX)',
    value: 'rcod',
  },
];

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
      onRCOD: true,
    },
    {
      className: 'shipping-settings',
      path: '/magic/settings/shipping-settings',
      label: 'Shipping Settings',
      Component: ShippingSettingsTab,
      exact: false,
      condition: (_user, abExperiments) =>
        abExperiments?.magic_shopify_shipping_engine?.variables?.result === 'on',
    },
    {
      className: 'intelligence-settings',
      path: '/magic/settings/rto-settings',
      label: 'RTO Settings',
      Component: MagicIntelligenceTab,
      condition: (_user) => ACCESS_ROLES.includes(_user.role),
      onRCOD: true,
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
    {
      className: 'analytics-settings',
      path: '/magic/settings/analytics-settings',
      label: 'Analytics settings',
      Component: AnalyticsSettings,
      condition: (_, abExperiments) =>
        abExperiments?.magic_analytics_setting?.variables?.result === 'on',
    },
    {
      className: 'magic-checkout-settings',
      path: '/magic/settings/coupons',
      label: 'Coupon Settings',
      Component: CouponEngineSettingsTab,
      condition: (_, abExperiments) =>
        abExperiments?.magic_coupon_engine?.variables?.result === 'on',
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
      className: 'cod-settings',
      path: '/magic/settings/cod-settings',
      label: 'COD Settings',
      Component: CODSettingsTab,
      condition: (_user) => _user.isMagicCODEngineEnabled,
    },
    {
      className: 'shipping-settings',
      path: '/magic/settings/shipping',
      label: 'Shipping Settings',
      Component: WoocShippingTab,
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
    shippingSettings: {
      header: 'Enable Magic shipping?',
      desc: 'Enabling Magic Shipping will bypass all shipping settings from any plugins on your E-commerce platform and prioritise these shipping configuration above all.  Are you sure want to enable Magic Shipping?',
      secondaryCtaLabel: 'No, don’t!',
      primaryCtaLabel: 'Yes, enable',
    },
  },
  disable: {
    codIntelligence: {
      header: 'Disable COD Intelligence?',
      desc: 'Realtime review of COD orders will be disabled. All customers will see the COD option increasing the risk of RTO.',
      secondaryCtaLabel: 'Cancel',
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
    shippingSettings: {
      header: 'Disable Magic shipping?',
      desc: 'Disabling Magic Shipping will revert back to the shipping settings from plugins on your E-commerce platform. Magic Shipping will no loger be applicable. Are you sure you want to disable Magic Shipping?',
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

export const MAGIC_SHIPPING_DESCRIPTION = `Enabling Magic Shipping will bypass all shipping configurations from any plugins on your E-commerce platform and follow configurations added below.`;
export const SHIPPING_SETTINGS_INFO = `Choose where you ship and how much you charge for shipping at checkout.`;

export const RCOD_SETTINGS_INFO =
  'Use this setting to enable COD on your store and configure the COD fees.';

export const UPDATE_WOOC_PLUGIN_MSG =
  'Note: To use advance COD settings, please update your Razorpay WooCommerce plugin to version 4.5.6 or above.';
