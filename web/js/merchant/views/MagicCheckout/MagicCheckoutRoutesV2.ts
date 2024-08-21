import lazy from 'merchant/routes/LazyLoader';

import SetupAndSettings from 'merchant/views/MagicCheckout/Settings/containers/SetupAndSettings';
import ReportsAndAnalytics from 'merchant/views/MagicCheckout/ReportsAndAnalyticsV2';
import ShopifyOrderEditing from 'merchant/views/MagicCheckout/ShopifyOrderEditing';
import CODToPrepaidLinks from 'merchant/views/MagicCheckout/CODToPrepaid/CODToPrepaidLinks';

import { PLATFORMS } from 'merchant/views/MagicCheckout/MagicSettings/constants';

const CouponEngine = lazy(
  () =>
    /* webpackChunkName: 'MagicCouponEngine' */ import('merchant/views/MagicCheckout/CouponEngine'),
);

const CODOrdersTab = lazy(
  () =>
    import(/* webpackChunkName: "MagicCODOrdersTab" */ 'merchant/views/MagicCheckout/CODOrdersTab'),
);

const routesV2 = [
  {
    tabName: 'Setup & Settings',
    path: '/magic/setup-settings',
    condition: (_user) => _user.isMagicSettingsEnabled,
    Component: SetupAndSettings,
    onRCOD: true,
  },
  {
    tabName: 'Reports & Analytics',
    path: '/magic/reports-analytics',
    Component: ReportsAndAnalytics,
    onRCOD: true,
    /**
     * Checks are done at TabsContainer(L1 Renderer) , Reports & Analytics Component due to complexities involved
     */
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
    onRCOD: true,
    condition: (_user, abExperiments, platform) =>
      abExperiments?.magic_coupon_engine?.variables?.result === 'on' &&
      platform === PLATFORMS.VALUES.SHOPIFY,
  },
];

export default routesV2;
