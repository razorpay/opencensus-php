import KeyReportsAndAnalytics from 'merchant/views/MagicCheckout/MagicDashboard/Wrapper';

import { formatRoutesByPlatform } from 'merchant/views/MagicCheckout/utils/formatGenericRoutes';

import { RoutesConfig, RouteItem } from 'merchant/views/MagicCheckout/types';

const GENERIC_ROUTES: RouteItem[] = [
  {
    label: 'Key Reports and Analytics',
    path: '/magic/dashboard/key-reports',
    Component: KeyReportsAndAnalytics,
    onRCOD: true,
  },
];

//DRY - Above routes are common for all platforms
export const DEFAULT_ROUTES: RoutesConfig = formatRoutesByPlatform(GENERIC_ROUTES);

export const PATH_PREFIX = '/magic/dashboard/';
export const REPORTS_AND_ANALYTICS_ROUTE = '/magic/reports-analytics';
