import ShippingAccount from 'merchant/views/MagicCheckout/ShippingServices';
import BulkAddressUpload from 'merchant/views/MagicCheckout/BulkAddressUpload';
import OrderStatusTab from 'merchant/views/MagicCheckout/OrderStatusTab';

const routes = [
  {
    tabName: 'Address',
    path: '/magic/address',
    condition: (_user) => _user.isBulkAddressUploadEnabled,
    Component: BulkAddressUpload,
  },
  {
    tabName: 'Upload Delivery Status',
    path: '/magic/delivery-status',
    condition: (_user) => false,
    Component: OrderStatusTab,
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
