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
    ['get-magic-order-analytics-data'],
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
