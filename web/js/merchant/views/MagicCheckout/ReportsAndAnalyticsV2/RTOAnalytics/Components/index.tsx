import React from 'react';

import Overview from 'merchant/views/MagicCheckout/RTOAnalytics/containers/Overview';
import OrderInsights from 'merchant/views/MagicCheckout/RTOAnalytics/containers/OrderInsights';
import RiskReport from 'merchant/views/MagicCheckout/RTOAnalytics/containers/RiskReport';

import { ConnectedWrapper } from 'merchant/views/MagicCheckout/ReportsAndAnalyticsV2/RTOAnalytics/Wrapper';

export const OverviewWrapper: React.FC = () => (
  <ConnectedWrapper Component={Overview} displayName="Overview" />
);
export const OrderInsightsWrapper: React.FC = () => (
  <ConnectedWrapper Component={OrderInsights} displayName="OrderInsights" />
);
export const RiskReportWrapper: React.FC = () => (
  <ConnectedWrapper Component={RiskReport} displayName="RiskReport" />
);
