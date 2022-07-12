import WoocSettingsForm from 'merchant/views/MagicCheckout/MagicSettings/containers/woocommerce/SettingsForm';
import WoocSettingsCard from 'merchant/views/MagicCheckout/MagicSettings/containers/woocommerce/ShippingCard';
import WoocShippingTabForm from 'merchant/views/MagicCheckout/MagicSettings/containers/woocommerce/ShippingForm';

import NativeSettingsForm from 'merchant/views/MagicCheckout/MagicSettings/containers/native/SettingsForm';
import NativeSettingsCard from 'merchant/views/MagicCheckout/MagicSettings/containers/native/SettingsCard';
import NativePlatform from 'merchant/views/MagicCheckout/MagicSettings/components/native/Platform';

import ShopifySettingsForm from 'merchant/views/MagicCheckout/MagicSettings/containers/shopify/SettingsForm';
import ShopifySettingsCard from 'merchant/views/MagicCheckout/MagicSettings/containers/shopify/SettingsCard';

export const PLATFORMS = {
  LABELS: {
    WOOCOMMERCE: 'WooCommerce',
    SHOPIFY: 'Shopify',
    NATIVE: 'Custom E-Commerce Platform',
  },
  VALUES: {
    WOOCOMMERCE: 'woocommerce',
    SHOPIFY: 'shopify',
    NATIVE: 'native',
  },
};

export const PLATFORMS_DROPDOWN = [
  { label: 'Select', name: 'select' },
  { label: PLATFORMS.LABELS.WOOCOMMERCE, name: PLATFORMS.VALUES.WOOCOMMERCE },
  { label: PLATFORMS.LABELS.SHOPIFY, name: PLATFORMS.VALUES.SHOPIFY },
  { label: PLATFORMS.LABELS.NATIVE, name: PLATFORMS.VALUES.NATIVE },
];

export const FETCH_STATUS = {
  IDLE: 'idle',
  LOADING: 'loading',
  ERROR: 'error',
};

export const CONTENT = {
  [PLATFORMS.VALUES.WOOCOMMERCE]: {
    LOGO: `${window.cdnBaseUrl}/static/assets/magic-checkout/platforms/wooc.png`,
    VIEW_LABEL: 'Woocommerce',
    EDIT_LABEL: 'WOOCOMMERCE SETTINGS',
  },
  [PLATFORMS.VALUES.SHOPIFY]: {
    LOGO: `${window.cdnBaseUrl}/static/assets/magic-checkout/platforms/shopify.png`,
    VIEW_LABEL: 'Shopify',
    EDIT_LABEL: 'SHOPIFY SETTINGS',
  },
  [PLATFORMS.VALUES.NATIVE]: {
    LOGO: '',
    VIEW_LABEL: 'Custom e-commerce platform',
    EDIT_LABEL: 'CUSTOM E-COMMERCE PLATFORM',
  },
};

export const VIEWS = {
  EDIT: 'edit',
  READ: 'read',
};

export const NATIVE_SHIPPING_VIEWS = {
  PROVIDER_SELECTION: 'provider_selection',
  API: 'Api Service',
  SHIPROCKET: 'Shiprocket',
};

export const SHIPPING_PROVIDERS = [
  { label: 'Select', name: 'select' },
  { label: 'API', name: 'API', view: NATIVE_SHIPPING_VIEWS.API },
  { label: 'Shiprocket', name: 'Shiprocket', view: NATIVE_SHIPPING_VIEWS.SHIPROCKET },
];

export const COMPONENTS = {
  [PLATFORMS.VALUES.NATIVE]: {
    platformComponent: (status) => <NativePlatform status={status} />,
    formComponent: () => <NativeSettingsForm />,
    cardComponent: (onEdit) => <NativeSettingsCard onEdit={onEdit} />,
  },
  [PLATFORMS.VALUES.SHOPIFY]: {
    platformComponent: () => <ShopifySettingsForm />,
    formComponent: () => <ShopifySettingsForm />,
    cardComponent: (onEdit) => <ShopifySettingsCard onEdit={onEdit} />,
  },
  [PLATFORMS.VALUES.WOOCOMMERCE]: {
    platformComponent: () => <WoocSettingsForm />,
    formComponent: (onEdit) => <WoocShippingTabForm onEdit={onEdit} />,
    cardComponent: (onEdit) => <WoocSettingsCard onEdit={onEdit} />,
  },
};

export const NESTED_VIEW_TYPE = {
  PLATFORM_SELECTION: 'platform_selection',
  SETTINGS: 'settings',
};

export const DISABLE_MAGIC_REASONS = [
  {
    value: 'Pricing too high',
    label: 'Pricing too high',
  },
  {
    value: 'Don’t find this useful',
    label: 'Don’t find this useful',
  },
  {
    value: 'Privacy concerns',
    label: 'Privacy concerns',
  },
  {
    value: 'Support issues',
    label: 'Support issues',
  },
  {
    value: 'Others',
    label: 'Others',
  },
];

export const SHOPIFY_CHECKOUT_SETTINGS = [
  {
    label: 'Buy Now button',
    value: false,
    key: 'one_cc_buy_now_button',
    description: 'Enable Magic Checkout on Buy Now',
  },
  {
    label: 'Auto fetch coupon',
    value: false,
    key: 'one_cc_auto_fetch_coupons',
    description:
      'Enable auto fetching of coupons to show all available coupons directly on Magic Checkout',
  },
  {
    label: 'International Shipping',
    value: false,
    key: 'one_cc_international_shipping',
    description: 'Allow customers to select international pin code for delivery',
  },
  {
    label: 'Capture Billing Address',
    value: false,
    key: 'one_cc_capture_billing_address',
    description: 'Ask customers to enter billing address separately',
  },
];

export const SHOPIFY_ANALYTICS_SETTINGS = [
  {
    label: 'Google Analytics',
    value: false,
    key: 'one_cc_ga_analytics',
    description: 'Enable Google Analytics tracking for Magic Checkout orders',
  },
  {
    label: 'Facebook Pixel',
    value: false,
    key: 'one_cc_fb_analytics',
    description: 'Enable Facebook Pixel tracking for Magic Checkout orders',
  },
];

export const SHIPPING_SETTINGS = [
  {
    label: 'International Shipping',
    value: false,
    key: 'one_cc_international_shipping',
    description: 'Allow customers to select international pin code for delivery',
  },
  {
    label: 'Capture Billing Address',
    value: false,
    key: 'one_cc_capture_billing_address',
    description: 'Ask customers to enter billing address separately',
  },
];

export const COUPON_SETTINGS = {
  label: 'Auto fetch coupon',
  value: false,
  key: 'one_cc_auto_fetch_coupons',
  description:
    'Enable auto fetching of coupons to show all available coupons directly on Magic Checkout',
};

export const SHOPIFY_MAGIC_CHECKOUT = {
  label: 'Magic Checkout',
  value: false,
  description: 'Enable Magic Checkout',
  key: 'one_click_checkout',
};
