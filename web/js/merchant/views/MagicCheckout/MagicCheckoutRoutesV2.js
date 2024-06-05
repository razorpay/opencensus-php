import lazy from 'merchant/routes/LazyLoader';

import BulkAddressUpload from 'merchant/views/MagicCheckout/BulkAddressUpload';
import MagicSettingsV2 from 'merchant/views/MagicCheckout/Settings/containers/NestedVerticalTabV2';
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

const routesV2 = [
  {
    tabName: 'Setup & Settings',
    path: '/magic/setup-settings',
    condition: (_user) => _user.isMagicSettingsEnabled,
    Component: MagicSettingsV2,
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

export default routesV2;
