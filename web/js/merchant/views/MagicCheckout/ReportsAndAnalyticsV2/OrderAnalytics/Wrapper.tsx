import React from 'react';

import { OrderAnalyticsProvider } from 'merchant/views/MagicCheckout/OrderAnalytics/OrderAnalyticsContext';

import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import lazy from 'merchant/routes/LazyLoader';

import { ActiveTab } from 'merchant/views/MagicCheckout/ReportsAndAnalyticsV2/types';

const OrderAnalyticsContainer = lazy(
  () =>
    import(
      /* webpackChunkName: "MagicOrderAnalytics" */ 'merchant/views/MagicCheckout/ReportsAndAnalyticsV2/OrderAnalytics/Container'
    ),
);

interface WrapperProps {
  activeRoute: ActiveTab;
}

const Wrapper: React.FC<WrapperProps> = ({ activeRoute }) => {
  return (
    <SuspenseWithLoader type="full">
      <OrderAnalyticsProvider>
        <OrderAnalyticsContainer activeRoute={activeRoute} />
      </OrderAnalyticsProvider>
    </SuspenseWithLoader>
  );
};

export default Wrapper;
