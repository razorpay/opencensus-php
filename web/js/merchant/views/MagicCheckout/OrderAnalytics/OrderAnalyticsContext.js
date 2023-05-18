import React, { useEffect, useState } from 'react';
import { fetchAnalyticsData } from './api';
// eslint-disable-next-line import/no-cycle
import { formatAnalyticsResponse } from './utils';

const OrderAnalyticsContext = React.createContext(null);

function OrderAnalyticsProvider({ children }) {
  const [analyticsData, setAnalyticsData] = useState({});
  const [timeRange, setTimeRange] = useState({ start: null, end: null });
  const [isFetching, setIsFetching] = useState(false);
  useEffect(() => {
    setIsFetching(true);
    fetchAnalyticsData(timeRange)
      .then(({ data }) => {
        setAnalyticsData(formatAnalyticsResponse(data));
      })
      .finally(() => setIsFetching(false));
  }, [timeRange]);

  return (
    <OrderAnalyticsContext.Provider
      value={{
        analyticsData,
        setTimeRange,
        isFetching,
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
