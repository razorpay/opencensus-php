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

const BrandsAndTerminals = lazy(
  () =>
    import(
      /* webpackChunkName: "BrandsAndTerminals" */ 'merchant/views/BillMeSettings/BrandsAndTerminals'
    ),
);

const BillMeSettings = (): React.ReactElement => {
  const user = useStore((state) => state.session.user);

  useEffect(() => {
    initRazorAnalytics({ product: DASHBOARD_TEAMS.BILLME, user });

    return () => {
      window.razorAnalytics?.disableTracking?.();
    };
  }, []);

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
