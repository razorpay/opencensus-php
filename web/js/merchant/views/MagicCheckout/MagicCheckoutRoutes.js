import ShippingAccount from 'merchant/views/MagicCheckout/ShippingServices';
import BulkAddressUpload from 'merchant/views/MagicCheckout/BulkAddressUpload';
import OrderStatusTab from 'merchant/views/MagicCheckout/OrderStatusTab';
import MagicSettings from 'merchant/views/MagicCheckout/MagicSettings';

/**
 * Order of tabs:
 * 1. Settings
 * 2. Shipping Services
 * 3. Address Upload
 * 4. Delivery Status upload
 *
 * Note: when adding a new tab, confirm the ordering with product first.
 */

const routes = [
  {
    tabName: 'Magic Settings',
    path: '/magic/settings',
    condition: (_user) => _user.isMagicSettingsEnabled,
    Component: MagicSettings,
  },
  {
    tabName: 'Shipping Services',
    path: '/magic/shipping',
    condition: (_user) => _user.isShiprocketEnabled,
    Component: ShippingAccount,
  },
  {
    tabName: 'Address',
    path: '/magic/address',
    condition: (_user) => _user.isBulkAddressUploadEnabled,
    Component: BulkAddressUpload,
  },
  {
    tabName: 'Upload Delivery Status',
    path: '/magic/delivery-status',
    Component: OrderStatusTab,
  },
];

export const isMagicCheckoutTabsEnabled = (user) =>
  routes.reduce(
    (enabled, route) => (route.condition ? enabled || route.condition(user) : true),
    false,
  );

export default routes;
