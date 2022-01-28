import ShippingAccount from 'merchant/views/MagicCheckout/ShippingServices';
import BulkAddressUpload from 'merchant/views/MagicCheckout/BulkAddressUpload';

const routes = [
  {
    tabName: 'Address',
    path: '/magic',
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

export default routes;
