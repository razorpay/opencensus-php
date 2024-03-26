import lazy from 'merchant/routes/LazyLoader';

import BulkAddressUpload from 'merchant/views/MagicCheckout/BulkAddressUpload';
import MagicSettings from 'merchant/views/MagicCheckout/Settings';
import RTOAnalytics from 'merchant/views/MagicCheckout/RTOAnalytics';
import OrderStatusUpload from 'merchant/views/MagicCheckout/OrderStatusUpload';
import ShopifyOrderEditing from 'merchant/views/MagicCheckout/ShopifyOrderEditing';
import CODToPrepaidLinks from 'merchant/views/MagicCheckout/CODToPrepaid/CODToPrepaidLinks';
import OrderAnalytics from 'merchant/views/MagicCheckout/OrderAnalytics';
import { PLATFORMS } from 'merchant/views/MagicCheckout/MagicSettings/constants';

const CouponEngine = lazy(() =>
  /* webpackChunkName: 'MagicCouponEngine' */ import('merchant/views/MagicCheckout/CouponEngine'),
);

const CODOrdersTab = lazy(() =>
  import(/* webpackChunkName: "MagicCODOrdersTab" */ 'merchant/views/MagicCheckout/CODOrdersTab'),
);

/**
 * Order of tabs:
 * 1. Settings
 * 2. Shipping Services
 * 3. Address Upload
 * 4. Delivery Status upload
 * 5. Cod Orders
 * 6. Editing Orders
 * 7. Coupon Engine
 *
 * Note: when adding a new tab, confirm the ordering with product first.
 */

const routes = [
  {
    tabName: 'Settings',
    path: '/magic/settings',
    condition: (_user) => _user.isMagicSettingsEnabled,
    Component: MagicSettings,
    onRCOD: true,
  },
  {
    tabName: 'Address',
    path: '/magic/address',
    condition: (_user) => _user.isBulkAddressUploadEnabled,
    Component: BulkAddressUpload,
    onRCOD: true,
  },
  {
    tabName: 'Delivery Status',
    path: '/magic/delivery-status',
    Component: OrderStatusUpload,
    onRCOD: true,
  },
  {
    tabName: 'RTO Analytics',
    path: '/magic/analytics',
    Component: RTOAnalytics,
  },
  {
    tabName: 'Order Analytics',
    path: '/magic/order-analytics',
    condition: (_user) => _user.isMagicOrderAnalyticsEnabled,
    Component: OrderAnalytics,
    onRCOD: true,
  },
  {
    tabName: 'COD Orders',
    path: '/magic/cod-orders',
    Component: CODOrdersTab,
    onRCOD: true,
  },
  {
    tabName: 'COD Order Conversion',
    path: '/magic/order-conversion',
    Component: CODToPrepaidLinks,
    condition: (_user) => _user.isMagicPrepayCODEnabled,
  },
  {
    tabName: 'Edit Orders',
    path: '/magic/order-editing',
    Component: ShopifyOrderEditing,
    condition: (_user) => _user.isMagicShopifyOrderEditEnabled,
  },
  {
    tabName: 'Coupons',
    path: '/magic/coupons',
    Component: CouponEngine,
    condition: (_user, abExperiments, platform) =>
      abExperiments?.magic_coupon_engine?.variables?.result === 'on' &&
      platform === PLATFORMS.VALUES.SHOPIFY,
  },
];

export const isMagicCheckoutTabsEnabled = (user) =>
  routes.reduce(
    (enabled, route) => (route.condition ? enabled || route.condition(user) : true),
    false,
  );

export default routes;
