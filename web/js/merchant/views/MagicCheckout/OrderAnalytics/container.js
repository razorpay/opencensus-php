import React, { Suspense } from 'react';
import ErrorBoundary, { Ranks, Teams } from 'common/new-ui/ErrorBoundary';
import Header from './common/Header';
import { CHART_COMPONENT_MAP, LAYOUT } from './constants';
import { useOrderAnalyticsContext } from './OrderAnalyticsContext';
import ComingSoon from './widgets/ComingSoon';
import ShimmerWidget from 'merchant/views/MagicCheckout/OrderAnalytics/widgets/Shimmer';

function OrderAnalyticsContainer() {
  const { setTimeRange, analyticsData, isFetching } = useOrderAnalyticsContext();
  const handleTimeRangeChange = ({ start, end }) => {
    setTimeRange({ start: start.unix(), end: end.unix() });
  };

  return (
    <div className="magic-analytics-container">
      <Header setTimeRange={handleTimeRangeChange} updated_at={analyticsData?.updated_at} />
      <div className="charts-container">
        {LAYOUT.map((layoutItem, index) => {
          const { chart, width } = layoutItem;
          const Component = CHART_COMPONENT_MAP[chart];
          return (
            <ErrorBoundary team={Teams?.MAGIC_CHECKOUT} rank={Ranks.P0} resetOnProps key={index}>
              <div className={`chart-${width}`}>
                <Suspense fallback={<ShimmerWidget height={350} width="100%" />}>
                  <Component
                    aggregation={analyticsData.aggregate}
                    isFetching={isFetching}
                    data={analyticsData?.metrics?.[chart]}
                  />
                </Suspense>
              </div>
            </ErrorBoundary>
          );
        })}
      </div>
      <ComingSoon />
    </div>
  );
}

export default OrderAnalyticsContainer;
