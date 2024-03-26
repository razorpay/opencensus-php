import React, { lazy } from 'react';
import ErrorBoundary, { Ranks, Teams } from 'common/new-ui/ErrorBoundary';
import { ErrorBoundaryFallBackComponent } from './utils';

const CarouselWithCount = lazy(() =>
  import('merchant/widgets/CarouselWithCount').then((module) => ({
    default: module.CarouselWithCountWidget,
  })),
);

const InsightsChartWidget = lazy(() => import('merchant/widgets/InsightsChart'));

const MerchantOverview = lazy(() =>
  import('merchant/containers/Home/RTUX/MerchantOverview').then((module) => ({
    default: module.MerchantOverview,
  })),
);

const Carousel = lazy(() =>
  import('merchant/widgets/Carousel').then((module) => ({
    default: module.CarouselWidget,
  })),
);

const TabbedCharts = lazy(() =>
  import('merchant/widgets/TabbedCharts').then((module) => ({
    default: module.TabbedCharts,
  })),
);

const withWidgetErrorBoundary = (Component) => (props) =>
  (
    <ErrorBoundary
      rank={Ranks.P0}
      team={Teams.PG_DASHBOARD}
      FallbackComponent={() => <ErrorBoundaryFallBackComponent marginX="spacing.6" />}
    >
      <Component {...props} />
    </ErrorBoundary>
  );

const widgetKeyToComponentMapping = {
  carousel_cards_with_count: CarouselWithCount,
  insight_charts: InsightsChartWidget,
  carousal_card: Carousel,
  hero_card: MerchantOverview,
  tabbed_chart: TabbedCharts,
};

Object.keys(widgetKeyToComponentMapping).forEach((key) => {
  widgetKeyToComponentMapping[key] = withWidgetErrorBoundary(widgetKeyToComponentMapping[key]);
});

export { widgetKeyToComponentMapping };
