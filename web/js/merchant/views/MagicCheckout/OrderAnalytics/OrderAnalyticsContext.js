import React, { useEffect, useState } from 'react';
import { useQuery } from 'react-query';
import { fetchAnalyticsData } from './api';
// eslint-disable-next-line import/no-cycle
import { formatAnalyticsResponse } from './utils';
import { TABS } from './constants/tabs';

const OrderAnalyticsContext = React.createContext(null);

const QueryOptions = {
  refetchOnWindowFocus: false,
  cacheTime: 0,
  retry: 3,
};

function OrderAnalyticsProvider({ children }) {
  const [analyticsData, setAnalyticsData] = useState({});
  const [activeTab, setActiveTab] = useState(TABS.OVERVIEW);
  const [timeRange, setTimeRange] = useState({ start: null, end: null });
  const { isFetching, refetch: refetchAnalyticsData } = useQuery(
    /**
     * unique key is added to avoid edge case where subsequent api calls are being made.
     * As current version of react-query is not supporting susequent refetch with same query key.
     * This issue will be resolved in the new version of react query.
     */
    [`get-magic-order-analytics-data${timeRange.end}`],
    () => fetchAnalyticsData(timeRange),
    {
      ...QueryOptions,
      onSuccess({ data }) {
        setAnalyticsData(formatAnalyticsResponse(data));
      },
    },
  );

  useEffect(() => {
    refetchAnalyticsData();
  }, [timeRange]);

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

export { useOrderAnalyticsContext, OrderAnalyticsProvider };
