import React, { Suspense, useMemo } from 'react';
import { connect } from 'react-redux';

import ErrorBoundary, { Ranks, Teams } from 'common/new-ui/ErrorBoundary';
import Header from 'merchant/views/MagicCheckout/OrderAnalytics/common/Header';
import ShimmerWidget from 'merchant/views/MagicCheckout/OrderAnalytics/widgets/Shimmer';

import { useOrderAnalyticsContext } from 'merchant/views/MagicCheckout/OrderAnalytics/OrderAnalyticsContext';
import { isRouteAuthorised } from 'merchant/views/MagicCheckout/utils/genericRouteCheck';

import {
  CHART_COMPONENT_MAP,
  MAGIC_DASHBOARD,
} from 'merchant/views/MagicCheckout/MagicDashboard/tabs';
import { SOPC_APP_NAME, RCOD_APP_NAME } from 'merchant/views/MagicCheckout/common/constants';

const activeTab = MAGIC_DASHBOARD;
function MagicDashboardContainer({ user, magicCheckout, org }) {
  /**
   * Using Existing OrderAnalytics ContextProvider for analyticsData
   */
  const { setTimeRange, analyticsData, isFetching } = useOrderAnalyticsContext();
  const { dashboard_view, cod_intelligence, cod_order_control, rcod } = magicCheckout;

  const isOrderAnalyticsChartEnabled =
    !(dashboard_view === SOPC_APP_NAME || dashboard_view === RCOD_APP_NAME) &&
    user.isMagicOrderAnalyticsEnabled;

  const isRTOAnalyticsChartEnabled = !(
    !cod_intelligence &&
    (!user?.isMagicRTOAnalyticsV3Enabled || !cod_order_control)
  );

  const isConversionRateChartEnabled =
    user.isMagicOrderAnalyticsEnabled && user.isMagicOrderAnalyticsCREnabled;

  const handleTimeRangeChange = ({ start, end }) => {
    setTimeRange({ start: start.unix(), end: end.unix() });
  };

  const updatedAt = useMemo(() => {
    return analyticsData?.metrics?.conversion_funnel?.updated_at;
  }, [analyticsData]);

  const isChartTypeEnabled = (chartType) => {
    switch (chartType) {
      case 'Order Analytics':
        return isOrderAnalyticsChartEnabled;
      case 'RTO Analytics':
        return isRTOAnalyticsChartEnabled;
      case 'Conversion Rate Analytics':
        return isConversionRateChartEnabled;
      default:
        return true;
    }
  };

  return (
    <div className="magic-analytics-container">
      {(isOrderAnalyticsChartEnabled || isConversionRateChartEnabled) && (
        <Header
          setTimeRange={handleTimeRangeChange}
          updated_at={updatedAt}
          dashboard_view={dashboard_view}
          org={org}
        />
      )}
      <div className="charts-container">
        {activeTab.layout.map((layoutItem, index) => {
          const { chart, width, type } = layoutItem;

          if (!isRouteAuthorised(layoutItem, user, {}, rcod)) return null;

          if (!isChartTypeEnabled(type)) return null;

          const Component = CHART_COMPONENT_MAP[chart];
          const data = analyticsData?.metrics?.[chart];
          return (
            <ErrorBoundary team={Teams?.MAGIC_CHECKOUT} rank={Ranks.P0} resetOnProps key={index}>
              <div className={`chart-${width}`}>
                <Suspense fallback={<ShimmerWidget height={350} width="100%" />}>
                  <Component
                    aggregation={analyticsData.aggregate}
                    isFetching={isFetching}
                    data={data}
                  />
                </Suspense>
              </div>
            </ErrorBoundary>
          );
        })}
      </div>
    </div>
  );
}

const mapStateToProps = (state) => ({
  user: state.session.user,
  magicCheckout: state.magicCheckout,
  org: state.session.org,
});

export default connect(mapStateToProps, null)(MagicDashboardContainer);
