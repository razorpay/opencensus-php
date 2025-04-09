import React, { useEffect, Suspense } from 'react';
import { Box, ToastContainer } from '@razorpay/blade/components';
import { Route, Routes } from 'react-router-dom';

import { useStore } from '@federated/apps/shell/commonStore';
import { DASHBOARD_TEAMS } from '@libs/shared-types';
import { initRazorAnalytics } from '@libs/shared-utils';
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
const StoreCreateOrEdit = lazy(
  () => import(/* webpackChunkName: "StoreCreateOrEdit" */ './StoreCreateOrEdit'),
);

const StoreSettings = (): React.ReactElement => {
  const user = useStore((state) => state.session.user);

  useEffect(() => {
    initRazorAnalytics({ product: DASHBOARD_TEAMS.BILLME, user });

    return () => {
      window.razorAnalytics?.disableTracking?.();
    };
  }, []);

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
              <Route
                path={getRefRoute(ROUTES_INFO.STORE_CREATE)}
                element={
                  <RouteGuard>
                    <StoreCreateOrEdit />
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
