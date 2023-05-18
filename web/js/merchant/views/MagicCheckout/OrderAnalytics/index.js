import React from 'react';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import lazy from 'merchant/routes/LazyLoader';
import { OrderAnalyticsProvider } from './OrderAnalyticsContext';
import './index.styl';

const OrderAnalyticsContainer = lazy(() =>
  import(
    /* webpackChunkName: "MagicOrderAnalytics" */ 'merchant/views/MagicCheckout/OrderAnalytics/container'
  ),
);

function OrderAnalytics() {
  return (
    <SuspenseWithLoader type="full">
      <OrderAnalyticsProvider>
        <OrderAnalyticsContainer />
      </OrderAnalyticsProvider>
    </SuspenseWithLoader>
  );
}

export default OrderAnalytics;
