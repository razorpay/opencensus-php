import React, { Suspense, useEffect, useMemo, useState } from 'react';
import { connect } from 'react-redux';
import moment from 'moment';

import ErrorBoundary, { Ranks, Teams } from 'common/new-ui/ErrorBoundary';
import Header from 'merchant/views/MagicCheckout/OrderAnalytics/common/Header';
import ShimmerWidget from 'merchant/views/MagicCheckout/OrderAnalytics/widgets/Shimmer';

import { useOrderAnalyticsContext } from 'merchant/views/MagicCheckout/OrderAnalytics/OrderAnalyticsContext';

import { TABS } from 'merchant/views/MagicCheckout/ReportsAndAnalyticsV2/OrderAnalytics/tabs';

import { CHART_COMPONENT_MAP } from 'merchant/views/MagicCheckout/OrderAnalytics/constants/component-map';

const initialReportsStart = moment().subtract(2, 'days').startOf('day').unix();
const initialReportsEnd = moment().startOf('day').unix();

const OrderAnalyticsContainer = ({ dashboardView, org, activeRoute }) => {
  const { setTimeRange, analyticsData, isFetching, activeTab, setActiveTab } =
    useOrderAnalyticsContext();

  useEffect(() => {
    setActiveTab(activeRoute);
  }, [activeRoute]);

  const [reportsTimeRange, setReportsTimeRange] = useState({
    start: initialReportsStart,
    end: initialReportsEnd,
  });

  const handleTimeRangeChange = ({ start, end }) => {
    setTimeRange({ start: start.unix(), end: end.unix() });
  };

  const handleReportsTimeRangeChange = ({ start, end }) => {
    setReportsTimeRange({ start: start.unix(), end: end.unix() });
  };

  const updatedAt = useMemo(() => {
    if (activeTab.label === TABS.CONVERSION.label) {
      return analyticsData?.metrics?.conversion_funnel?.updated_at;
    }
    return analyticsData?.updated_at;
  }, [activeTab, analyticsData]);

  return (
    <div className="magic-analytics-container">
      <Header
        setTimeRange={handleTimeRangeChange}
        updated_at={updatedAt}
        dashboardView={dashboardView}
        org={org}
        setReportsTimeRange={handleReportsTimeRangeChange}
      />
      {activeTab.isCharts ? (
        <div className="charts-container">
          {activeTab.layout.map((layoutItem, index) => {
            const { chart, width } = layoutItem;
            const Component = CHART_COMPONENT_MAP[chart];
            const data = analyticsData?.metrics?.[chart];
            const isDataFetching = isFetching;
            return (
              <ErrorBoundary team={Teams?.MAGIC_CHECKOUT} rank={Ranks.P0} resetOnProps key={index}>
                <div className={`chart-${width}`}>
                  <Suspense fallback={<ShimmerWidget height={350} width="100%" />}>
                    <Component
                      aggregation={analyticsData.aggregate}
                      isFetching={isDataFetching}
                      data={data}
                    />
                  </Suspense>
                </div>
              </ErrorBoundary>
            );
          })}
        </div>
      ) : (
        <div className="components-container">
          <ErrorBoundary team={Teams?.MAGIC_CHECKOUT} rank={Ranks.P0} resetOnProps>
            <activeTab.Component
              reportsTimeRange={reportsTimeRange}
              setReportsTimeRange={setReportsTimeRange}
            />
          </ErrorBoundary>
        </div>
      )}
    </div>
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
  dashboardView: state.magicCheckout.dashboard_view,
  org: state.session.org,
});

export default connect(mapStateToProps, null)(OrderAnalyticsContainer);
