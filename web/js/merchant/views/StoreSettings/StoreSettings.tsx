import React, { Suspense } from 'react';
import { Box, ToastContainer } from '@razorpay/blade/components';
import { Route, Routes } from 'react-router-dom';

import Loader from 'common/components/Loader';
import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import { RouteGuard } from 'merchant/components/ShowWhen';
import lazy from 'merchant/routes/LazyLoader';
import { ROUTES_INFO } from 'merchant/views/AccountAndSettings/typings/routes';

const StoresList = lazy(
  () => import(/* webpackChunkName: "StoresList" */ 'merchant/views/StoreSettings/StoresList'),
);
const StoreDetails = lazy(
  () => import(/* webpackChunkName: "StoreDetails" */ 'merchant/views/StoreSettings/StoreDetails'),
);

const StoreSettings = (): React.ReactElement => {
  const getRefRoute = (routePath: string) => {
    return `${routePath.replace('/store-settings/', '')}/*`;
  };

  return (
    <Box paddingX="spacing.8" paddingY="spacing.7">
      <ToastContainer />
      <ErrorBoundary resetOnProps>
        <Suspense fallback={<Loader />}>
          <Box>
            <Routes>
              <Route
                path={getRefRoute(ROUTES_INFO.STORE_SETTINGS)}
                element={
                  <RouteGuard>
                    <StoresList />
                  </RouteGuard>
                }
              />
              <Route
                path={getRefRoute(ROUTES_INFO.STORE_DETAILS)}
                element={
                  <RouteGuard>
                    <StoreDetails />
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

export default StoreSettings;
