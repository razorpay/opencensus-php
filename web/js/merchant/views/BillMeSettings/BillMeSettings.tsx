import React, { Suspense } from 'react';
import { Box, ToastContainer } from '@razorpay/blade/components';
import { Route, Routes } from 'react-router-dom';

import Loader from 'common/components/Loader';
import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import { RouteGuard } from 'merchant/components/ShowWhen';
import lazy from 'merchant/routes/LazyLoader';
import { ROUTES_INFO } from 'merchant/views/AccountAndSettings/typings/routes';

const BrandsAndTerminals = lazy(
  () =>
    import(
      /* webpackChunkName: "BrandsAndTerminals" */ 'merchant/views/BillMeSettings/BrandsAndTerminals'
    ),
);

const BillMeSettings = (): React.ReactElement => {
  const getRefRoute = (routePath: string) => {
    return `${routePath.replace('/billme-settings/', '')}/*`;
  };

  return (
    <Box paddingX="spacing.8" paddingY="spacing.7">
      <ToastContainer />
      <ErrorBoundary resetOnProps>
        <Suspense fallback={<Loader />}>
          <Box>
            <Routes>
              <Route
                path={getRefRoute(ROUTES_INFO.BILLME_SETTINGS)}
                element={
                  <RouteGuard>
                    <BrandsAndTerminals />
                  </RouteGuard>
                }
              />
            </Routes>
          </Box>
        </Suspense>
      </ErrorBoundary>
    </Box>
  );
};

export default BillMeSettings;
