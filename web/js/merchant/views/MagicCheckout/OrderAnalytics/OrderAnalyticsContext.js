import React, { useEffect, useState } from 'react';
import { connect } from 'react-redux';
import { useQuery } from '@tanstack/react-query';
import { fetchAnalyticsData } from './api';
// eslint-disable-next-line import/no-cycle
import { formatAnalyticsResponse } from './utils';
import { TABS } from './constants/tabs';
import { RCOD_APP_NAME, SOPC_APP_NAME } from '../common/constants';

const OrderAnalyticsContext = React.createContext(null);

const QueryOptions = {
  refetchOnWindowFocus: false,
  cacheTime: 0,
  retry: 3,
};

const getActiveTab = (dashboardView) => {
  if (dashboardView === RCOD_APP_NAME || dashboardView === SOPC_APP_NAME) return TABS.CONVERSION;
  return TABS.OVERVIEW;
};

function OrderAnalyticsProvider({ children, dashboardView }) {
  const [analyticsData, setAnalyticsData] = useState({});
  const [activeTab, setActiveTab] = useState(getActiveTab(dashboardView));
  const [timeRange, setTimeRange] = useState({ start: null, end: null });
  const { isFetching, refetch: refetchAnalyticsData } = useQuery({
    /**
     * unique key is added to avoid edge case where subsequent api calls are being made.
     * As current version of react-query is not supporting susequent refetch with same query key.
     * This issue will be resolved in the new version of react query.
     */
    queryKey: [`get-magic-order-analytics-data${timeRange.end}`],
    queryFn: () => fetchAnalyticsData(timeRange, dashboardView),
    ...QueryOptions,
    onSuccess({ data }) {
      setAnalyticsData(formatAnalyticsResponse(data));
    },
  });

  useEffect(() => {
    refetchAnalyticsData();
  }, [timeRange, dashboardView]);

  return (
    <OrderAnalyticsContext.Provider
      value={{
        analyticsData,
        setTimeRange,
        isFetching,
        activeTab,
        setActiveTab,
      }}
    >
      {children}
    </OrderAnalyticsContext.Provider>
  );
}

const useOrderAnalyticsContext = () => {
  const ctx = React.useContext(OrderAnalyticsContext);
  if (!ctx) {
    throw new Error('useOrderAnalyticsContext must be used within OrderAnalyticsProvider');
  }
  return ctx;
};

const ConnectedOrderAnalyticsProvider = connect((state) => ({
  dashboardView: state.magicCheckout.dashboard_view,
}))(OrderAnalyticsProvider);
export { useOrderAnalyticsContext, ConnectedOrderAnalyticsProvider as OrderAnalyticsProvider };
