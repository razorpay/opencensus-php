import lazy from 'merchant/routes/LazyLoader';

import MagicDashboard from 'merchant/views/MagicCheckout/MagicDashboard';

import { isValidPlatform } from 'merchant/views/MagicCheckout/helper';

const CouponEngine = lazy(
  () =>
    /* webpackChunkName: 'MagicCouponEngine' */ import('merchant/views/MagicCheckout/CouponEngine'),
);

const Orders = lazy(
  () => import(/* webpackChunkName: "Orders" */ 'merchant/views/MagicCheckout/OrdersV2'),
);

const ReportsAndAnalytics = lazy(
  () =>
    import(
      /* webpackChunkName: "ReportsAndAnalytics" */ 'merchant/views/MagicCheckout/ReportsAndAnalyticsV2'
    ),
);

const PlatformSettings = lazy(
  () =>
    import(
      /* webpackChunkName: "SetupAndSettings" */ 'merchant/views/MagicCheckout/Settings/containers/PlatformSettingsV2'
    ),
);

/**
 * Checks for Magic Dashboard, Orders, Reports & Analytics are performed at Tabs Container(L1 Routes Renderer) - test
 */
const routesV2 = [
  {
    tabName: 'Magic Dashboard',
    path: '/magic/dashboard',
    Component: MagicDashboard,
    condition: isValidPlatform,
    onRCOD: true,
  },
  {
    tabName: 'Orders',
    path: '/magic/orders',
    Component: Orders,
    condition: isValidPlatform,
    onRCOD: true,
  },
  {
    tabName: 'Coupons',
    path: '/magic/coupons',
    Component: CouponEngine,
    onRCOD: true,
    condition: (user, abExp, platform) =>
      user.isMagicCouponEngineEnabled && isValidPlatform(user, abExp, platform),
  },
  {
    tabName: 'Reports & Analytics',
    path: '/magic/reports-analytics',
    Component: ReportsAndAnalytics,
    condition: isValidPlatform,
    onRCOD: true,
    /**
     * Checks are done at TabsContainer(L1 Renderer) , Reports & Analytics Component due to complexities involved
     */
  },
  {
    tabName: 'Setup & Settings',
    path: '/magic/settings',
    condition: (user) => user.isMagicSettingsEnabled,
    Component: PlatformSettings,
    onRCOD: true,
  },
];

export default routesV2;
