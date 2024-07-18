import React from 'react';

import AnalyticsWrapper from 'merchant/views/MagicCheckout/ReportsAndAnalyticsV2/OrderAnalytics/Wrapper';

import { TABS } from 'merchant/views/MagicCheckout/ReportsAndAnalyticsV2/OrderAnalytics/tabs';

/**
 * Conversion , Overview and Reports are rendered by a generic container
 * as they are rendered as Tabs in existing UI. In new UI(Dashboard Revamp)
 * we are rendering them as individual routes and hence individual wrappers over
 * generic container
 */
export const ConversionRateAnalytics: React.FC = () => {
  return <AnalyticsWrapper activeRoute={TABS.CONVERSION} />;
};

export const OrderAnalytics: React.FC = () => {
  return <AnalyticsWrapper activeRoute={TABS.OVERVIEW} />;
};

export const Reports: React.FC = () => {
  return <AnalyticsWrapper activeRoute={TABS.REPORTS} />;
};
