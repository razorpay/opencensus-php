import ShippingAccount from 'merchant/views/MagicCheckout/ShippingServices';
import BulkAddressUpload from 'merchant/views/MagicCheckout/BulkAddressUpload';

const routes = [
  {
    tabName: 'Address',
    path: '/magic/address',
    condition: (_user) => _user.isBulkAddressUploadEnabled,
    Component: BulkAddressUpload,
  },
  {
    tabName: 'Shipping Services',
    path: '/magic/shipping',
    condition: (_user) => _user.isShiprocketEnabled,
    Component: ShippingAccount,
  },
];

export const isMagicCheckoutTabsEnabled = (user) =>
  routes.reduce((enabled, route) => enabled || route.condition(user), false);

export default routes;
