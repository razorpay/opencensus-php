import lazy from 'merchant/routes/LazyLoader';

import MagicDashboard from 'merchant/views/MagicCheckout/MagicDashboard';

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

const SetupAndSettings = lazy(
  () =>
    import(
      /* webpackChunkName: "SetupAndSettings" */ 'merchant/views/MagicCheckout/Settings/containers/SetupAndSettings'
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
  },
  {
    tabName: 'Orders',
    path: '/magic/orders',
    Component: Orders,
    onRCOD: true,
  },
  {
    tabName: 'Coupons',
    path: '/magic/coupons',
    Component: CouponEngine,
    onRCOD: true,
    condition: (_user, abExperiments) =>
      abExperiments?.magic_coupon_engine?.variables?.result === 'on',
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
    tabName: 'Setup & Settings',
    path: '/magic/setup-settings',
    condition: (_user) => _user.isMagicSettingsEnabled,
    Component: SetupAndSettings,
    onRCOD: true,
  },
];

export default routesV2;
