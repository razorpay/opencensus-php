import React, { Suspense, useCallback, useMemo } from 'react';
import ErrorBoundary, { Ranks, Teams } from 'common/new-ui/ErrorBoundary';
import Header from './common/Header';
import { CHART_COMPONENT_MAP } from './constants/component-map';
import { useOrderAnalyticsContext } from './OrderAnalyticsContext';
import ShimmerWidget from 'merchant/views/MagicCheckout/OrderAnalytics/widgets/Shimmer';
import { TABS } from './constants/tabs';
import { TabsContainer, Tabs, Tab } from './styles';
import { connect } from 'react-redux';

function OrderAnalyticsContainer({ user }) {
  const { setTimeRange, analyticsData, isFetching, activeTab, setActiveTab } =
    useOrderAnalyticsContext();
  const handleTimeRangeChange = ({ start, end }) => {
    setTimeRange({ start: start.unix(), end: end.unix() });
  };

  const { label } = activeTab;

  const getTab = useCallback(
    (tabs) => {
      return label === tabs.label ? ' active' : '';
    },
    [label],
  );

  const handleTabClick = (tab) => {
    if (label === tab.label) return;
    setActiveTab(tab);
  };

  const updatedAt = useMemo(() => {
    if (activeTab.label === TABS.CONVERSION.label) {
      return analyticsData?.metrics?.conversion_funnel?.updated_at;
    }
    return analyticsData?.updated_at;
  }, [activeTab, analyticsData]);

  return (
    <TabsContainer>
      <Tabs>
        {Object.keys(TABS).map((tabName) => {
          return TABS[tabName].condition && !TABS[tabName].condition(user) ? null : (
            <Tab
              key={TABS[tabName].label}
              className={getTab(TABS[tabName])}
              onClick={() => handleTabClick(TABS[tabName])}
            >
              {TABS[tabName].label}
            </Tab>
          );
        })}
      </Tabs>
      <div className="magic-analytics-container">
        <Header setTimeRange={handleTimeRangeChange} updated_at={updatedAt} />
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
      </div>
    </TabsContainer>
  );
}

const mapStateToProps = (state) => ({
  user: state.session.user,
});

export default connect(mapStateToProps, null)(OrderAnalyticsContainer);
