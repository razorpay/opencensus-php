import {
  OverviewWrapper,
  RiskReportWrapper,
  OrderInsightsWrapper,
} from 'merchant/views/MagicCheckout/ReportsAndAnalyticsV2/RTOAnalytics/Components';

import { PLATFORMS } from 'merchant/views/MagicCheckout/constants';

import { RouteItem } from 'merchant/views/MagicCheckout/types';

interface RTORouteItem extends RouteItem {
  eventName: string;
}

interface RTORoutesConfig {
  [x: string]: RTORouteItem[];
}

const RTO_GENERIC_ROUTES: RTORouteItem[] = [
  {
    label: 'Overview',
    path: '/magic/reports-analytics/rto/overview',
    Component: OverviewWrapper,
    eventName: 'Overview',
  },
  {
    path: '/magic/reports-analytics/rto/risk-report',
    label: 'Risk Report',
    Component: RiskReportWrapper,
    eventName: 'RiskReport',
  },
  {
    path: '/magic/reports-analytics/rto/rto-insights',
    label: 'RTO Insights',
    Component: OrderInsightsWrapper,
    eventName: 'RTOInsights',
  },
];

//DRY - Above routes are common for all platforms
export const RTO_ROUTES = Object.values(PLATFORMS).reduce<RTORoutesConfig>(
  (RTORoutes, Platform: string) => {
    RTORoutes[Platform as string] = RTO_GENERIC_ROUTES;
    return RTORoutes;
  },
  {} as RTORoutesConfig,
);
