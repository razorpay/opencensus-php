
import Settings from "merchant/views/MagicCheckout/Settings/containers/SSO/components/tabs/Settings";
import Customise from "merchant/views/MagicCheckout/Settings/containers/SSO/components/tabs/Customise";

const PLATFORMS = {
  SHOPIFY: 'shopify',
  WOOCOMMERCE: 'woocommerce',
  NATIVE: 'native',
};

const COMMON_SSO_ROUTES = [
  {
    label: 'Settings',
    id: 'settings',
    path: '/magic/settings/sso/settings',
    Component: Settings,
    onRCOD: true,
  },
  {
    label: 'Customise',
    id: 'customise',
    path: '/magic/settings/sso/customise',
    Component: Customise,
    onRCOD: true,
  },
];

export const SSO_SETTINGS_ROUTES = {
  [PLATFORMS.NATIVE]: COMMON_SSO_ROUTES,
  [PLATFORMS.SHOPIFY]: COMMON_SSO_ROUTES,
  [PLATFORMS.WOOCOMMERCE]: COMMON_SSO_ROUTES,
};
