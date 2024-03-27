import React, { Suspense } from 'react';
import { Box, Spinner, BoxProps } from '@razorpay/blade/components';
import { Route, Routes, Navigate, useLocation } from 'react-router-dom';

import { RouteGuard } from 'merchant/components/ShowWhen';
import lazy from 'merchant/routes/LazyLoader';
import { PRODUCT_ROUTE_PATH_PARAM, PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';

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

const RemoveTrailingSlash = () => {
  const location = useLocation();

  return location.pathname.match(/.*\/$/) ? (
    <Navigate
      to={{
        pathname: location.pathname.replace(/\/+$/, ''),
        search: location.search,
      }}
      replace
    />
  ) : null;
};

const PRODUCTS_IN_ORDER = [PRODUCT_TYPE.PG, PRODUCT_TYPE.POS, PRODUCT_TYPE.CAPITAL, PRODUCT_TYPE.X];
const ProductClientsMap = {
  [PRODUCT_TYPE.PG]: PaymentsClients,
  [PRODUCT_TYPE.POS]: POSClients,
  [PRODUCT_TYPE.CAPITAL]: CapitalClients,
  [PRODUCT_TYPE.X]: RazorpayXClients,
};
type ProductClientAccountsProps = {
  productTypeVisibilityMap: Record<string, boolean>;
};
const ProductClientAccounts = ({
  productTypeVisibilityMap,
}: ProductClientAccountsProps): JSX.Element => {
  const firstVisibleProductType = PRODUCTS_IN_ORDER.find(
    (productType) => productTypeVisibilityMap[productType],
  );
  const defaultRedirectRoute =
    firstVisibleProductType && firstVisibleProductType != PRODUCT_TYPE.PG
      ? PRODUCT_ROUTE_PATH_PARAM[firstVisibleProductType]
      : null;

  return (
    <Routes>
      {PRODUCTS_IN_ORDER.filter((productType) => productTypeVisibilityMap[productType]).map(
        (productType) => {
          const ProductClients = ProductClientsMap[productType];
          const productPrefixNoSlash = PRODUCT_ROUTE_PATH_PARAM[productType];

          return (
            <Route
              key={productType}
              path={productPrefixNoSlash === '' ? '*' : `${productPrefixNoSlash}/*`}
              element={
                <RouteGuard>
                  <Suspense fallback={<CommonSuspenseSpinner />}>
                    <RemoveTrailingSlash />
                    <ProductClients />
                  </Suspense>
                </RouteGuard>
              }
            />
          );
        },
      )}
      {defaultRedirectRoute ? (
        <Route path="*" element={<Navigate to={defaultRedirectRoute} replace />} />
      ) : null}
    </Routes>
  );
};
export default ProductClientAccounts;
