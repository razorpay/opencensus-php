import WoocSettingsForm from 'merchant/views/MagicCheckout/MagicSettings/containers/WoocSettingsForm';
import WoocSettingsCard from 'merchant/views/MagicCheckout/MagicSettings/containers/WoocSettingsCard';

import NativeSettingsForm from 'merchant/views/MagicCheckout/MagicSettings/containers/NativeSettingsForm';
import NativeSettingsCard from 'merchant/views/MagicCheckout/MagicSettings/containers/NativeSettingsCard';

import ShopifySettingsForm from 'merchant/views/MagicCheckout/MagicSettings/containers/ShopifySettingsForm';
import ShopifySettingsCard from 'merchant/views/MagicCheckout/MagicSettings/containers/ShopifySettingsCard';

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

export const COMPONENTS = {
  [PLATFORMS.VALUES.NATIVE]: {
    formComponent: () => <NativeSettingsForm />,
    cardComponent: (onEdit) => <NativeSettingsCard onEdit={onEdit} />,
  },
  [PLATFORMS.VALUES.SHOPIFY]: {
    formComponent: () => <ShopifySettingsForm />,
    cardComponent: (onEdit) => <ShopifySettingsCard onEdit={onEdit} />,
  },
  [PLATFORMS.VALUES.WOOCOMMERCE]: {
    formComponent: () => <WoocSettingsForm />,
    cardComponent: (onEdit) => <WoocSettingsCard onEdit={onEdit} />,
  },
};
