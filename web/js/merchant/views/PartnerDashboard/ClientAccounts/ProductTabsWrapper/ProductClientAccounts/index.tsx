import React, { Suspense } from 'react';
import { Box, Spinner, BoxProps } from '@razorpay/blade/components';
import { Route, Routes } from 'react-router-dom';

import { RouteGuard } from 'merchant/components/ShowWhen';
import lazy from 'merchant/routes/LazyLoader';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';

const PaymentsClients = lazy(
  () =>
    import(
      /* webpackChunkName: "PartnerPlaybook" */ 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/PaymentsClients'
    ),
);

const POSClients = lazy(
  () =>
    import(
      /* webpackChunkName: "PartnerPlaybook" */ 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/POSClients'
    ),
);

const CapitalClients = lazy(
  () =>
    import(
      /* webpackChunkName: "PartnerPlaybook" */ 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/CapitalClients'
    ),
);

const RazorpayXClients = lazy(
  () =>
    import(
      /* webpackChunkName: "PartnerPlaybook" */ 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/RazorpayXClients'
    ),
);

const CommonSuspenseSpinner = ({
  minHeight = '300px',
}: Pick<BoxProps, 'minHeight'>): JSX.Element => (
  <Box minHeight={minHeight} display="flex" justifyContent="center" alignItems="center">
    <Spinner accessibilityLabel="spinner" size="xlarge" />
  </Box>
);
type ProductClientAccountsProps = {
  productTypeVisibilityMap: Record<string, boolean>;
};
const ProductClientAccounts = ({
  productTypeVisibilityMap,
}: ProductClientAccountsProps): JSX.Element => {
  return (
    <Routes>
      <Route
        path="pos/*"
        element={
          <RouteGuard additionalCondition={() => productTypeVisibilityMap[PRODUCT_TYPE.POS]}>
            <Suspense fallback={<CommonSuspenseSpinner />}>
              <POSClients />
            </Suspense>
          </RouteGuard>
        }
      />

      <Route
        path="capital/*"
        element={
          <RouteGuard additionalCondition={() => productTypeVisibilityMap[PRODUCT_TYPE.CAPITAL]}>
            <Suspense fallback={<CommonSuspenseSpinner />}>
              <CapitalClients />
            </Suspense>
          </RouteGuard>
        }
      />
      <Route
        path="x/*"
        element={
          <Suspense fallback={<CommonSuspenseSpinner />}>
            <RouteGuard additionalCondition={() => productTypeVisibilityMap[PRODUCT_TYPE.X]}>
              <RazorpayXClients />
            </RouteGuard>
          </Suspense>
        }
      />

      <Route
        path="*"
        element={
          <Suspense fallback={<CommonSuspenseSpinner />}>
            <RouteGuard additionalCondition={() => productTypeVisibilityMap[PRODUCT_TYPE.PG]}>
              <PaymentsClients />
            </RouteGuard>
          </Suspense>
        }
      />
    </Routes>
  );
};
export default ProductClientAccounts;
