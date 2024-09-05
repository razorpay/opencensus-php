import lazy from 'merchant/routes/LazyLoader';

import {
  OrderAnalytics,
  ConversionRateAnalytics,
  Reports,
} from 'merchant/views/MagicCheckout/ReportsAndAnalyticsV2/OrderAnalytics';
import { formatRoutesByPlatform } from 'merchant/views/MagicCheckout/utils/formatGenericRoutes';

import { User, RouteItem } from 'merchant/views/MagicCheckout/types';

const RTOAnalytics = lazy(
  () =>
    import(
      /* webpackChunkName: "RTOAnalytics" */ 'merchant/views/MagicCheckout/ReportsAndAnalyticsV2/RTOAnalytics'
    ),
);

export const GENERIC_ROUTES: RouteItem[] = [
  {
    path: '/magic/reports-analytics/order-analytics',
    label: 'Order Analytics',
    Component: OrderAnalytics,
    condition: (_user: User) => _user.isMagicOrderAnalyticsEnabled,
  },
  {
    path: '/magic/reports-analytics/conversion-rate-analytics',
    label: 'Conversion rate',
    Component: ConversionRateAnalytics,
    condition: (_user: User) =>
      _user.isMagicOrderAnalyticsEnabled && _user.isMagicOrderAnalyticsCREnabled,
    onRCOD: true,
  },
  {
    path: '/magic/reports-analytics/rto',
    label: 'RTO Analytics',
    Component: RTOAnalytics,
  },
  {
    path: '/magic/reports-analytics/reports',
    label: 'Reports',
    Component: Reports,
    condition: (_user: User) => _user.isMagicOrderAnalyticsEnabled,
    onRCOD: true,
  },
];

//DRY - Above routes are common for all platforms
export const DEFAULT_ROUTES = formatRoutesByPlatform(GENERIC_ROUTES);
