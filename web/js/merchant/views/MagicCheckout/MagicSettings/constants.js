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
