import React, { Suspense, lazy } from 'react';
import { Routes, Route, useLocation, Navigate } from 'react-router-dom';
import { DashboardLoader } from '@libs/shared-ui';
import { getItemFromLocalStorage } from '@libs/shared-utils';
import withNavigationType from '@apps/shell/src/client/components/Navigation/withNavigationType';

const HandleIndexAndPaymentsRoute = lazy(
  () => import('@apps/shell/src/client/components/ProductRouter/HandleIndexAndPaymentsRoute'),
);

const Home = () => <div>Home</div>;
const XDashboard = () => <div>Banking</div>;
const WrappedXDashboard = withNavigationType(XDashboard);

export const ProductRouter = (): JSX.Element => {
  const isOneHomeEnabled = Boolean(window?.IS_ONE_HOME_ENABLED);
  const location = useLocation();

  const hasUserInteractedWithHomeConsent = getItemFromLocalStorage(
    'hasUserInteractedWithHomeConsent',
  );
  const hasNotInteractedWithHomeConsent =
    hasUserInteractedWithHomeConsent === null || hasUserInteractedWithHomeConsent === 'false';

  // Check if user is not already on home path
  const isNotHomePath = !location.pathname.startsWith('/home');

  if (hasNotInteractedWithHomeConsent && isOneHomeEnabled && isNotHomePath) {
    return <Navigate to="/home" replace />;
  }

  return (
    <Suspense
      fallback={
        <DashboardLoader
          labelPosition="bottom"
          label="Loading... Please wait..."
          loaderType="wrt-viewport"
        />
      }
    >
      <Routes>
        {isOneHomeEnabled && <Route path="home" element={<Home />} />}
        <Route path="banking/*" element={<WrappedXDashboard />} />
        <Route path="*" element={<HandleIndexAndPaymentsRoute />} />
      </Routes>
    </Suspense>
  );
};
