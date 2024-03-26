import React, { lazy } from 'react';

const InsightItem = lazy(() => import('merchant/widgets/InsightsChart/subwidgets/InsightItem'));
const DoughnutWidget = lazy(
  () => import('merchant/widgets/InsightsChart/subwidgets/DoughnutWidget'),
);

export const subWidgetKeyToComponentMapping = {
  insight_item: (props): JSX.Element => <InsightItem {...props} />,
  doughnut_chart: (props): JSX.Element => <DoughnutWidget {...props} />,
};
