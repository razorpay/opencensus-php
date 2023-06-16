import BulkAddressUpload from 'merchant/views/MagicCheckout/BulkAddressUpload';
import MagicSettings from 'merchant/views/MagicCheckout/Settings';
import RTOAnalytics from 'merchant/views/MagicCheckout/RTOAnalytics';
import OrderStatusUpload from 'merchant/views/MagicCheckout/OrderStatusUpload';
import CODOrdersTab from 'merchant/views/MagicCheckout/CODOrdersTab';
import CODToPrepaidLinks from 'merchant/views/MagicCheckout/CODToPrepaid/CODToPrepaidLinks';
import OrderAnalytics from 'merchant/views/MagicCheckout/OrderAnalytics';

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
    tabName: 'Settings',
    path: '/magic/settings',
    condition: (_user) => _user.isMagicSettingsEnabled,
    Component: MagicSettings,
  },
  {
    tabName: 'Address',
    path: '/magic/address',
    condition: (_user) => _user.isBulkAddressUploadEnabled,
    Component: BulkAddressUpload,
  },
  {
    tabName: 'Upload Order Status',
    path: '/magic/order-status',
    Component: OrderStatusUpload,
  },
  {
    tabName: 'RTO Analytics',
    path: '/magic/analytics',
    condition: (_user) => _user.isMagicRTOAnalyticsEnabled,
    Component: RTOAnalytics,
  },
  {
    tabName: 'Order Analytics',
    path: '/magic/order-analytics',
    condition: (_user) => _user.isMagicOrderAnalyticsEnabled,
    Component: OrderAnalytics,
  },
  {
    tabName: 'COD Orders',
    path: '/magic/cod-orders',
    Component: CODOrdersTab,
  },
  {
    tabName: 'COD Order Conversion',
    path: '/magic/order-conversion',
    Component: CODToPrepaidLinks,
    condition: (_user) => _user.isMagicPrepayCODEnabled,
  },
];

export const isMagicCheckoutTabsEnabled = (user) =>
  routes.reduce(
    (enabled, route) => (route.condition ? enabled || route.condition(user) : true),
    false,
  );

export default routes;
