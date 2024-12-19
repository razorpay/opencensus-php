import React, { lazy } from 'react';
import { Routes, Route, Outlet } from 'react-router-dom';

import ErrorBoundary from 'common/new-ui/ErrorBoundary';

const OnboardingPaymentMethods = lazy(
  () =>
    import(
      /* webpackChunkName: "OnboardingPaymentMethods" */ 'merchant/views/AccountAndSettings/WebsiteAppSettings/Tabs/BusinessWebsiteDetails/v2/OnboardingPaymentMethods'
    ),
);

const OnboardingBusinessWebsite = lazy(
  () =>
    import(
      /* webpackChunkName: "OnboardingBusinessWebsite" */ 'merchant/views/AccountAndSettings/WebsiteAppSettings/Tabs/BusinessWebsiteDetails/v2/OnboardingBusinessWebsite'
    ),
);

const OnboardingRoutesWrapper = (): JSX.Element => {
  return (
    <ErrorBoundary>
      <Routes>
        <Route path="onboarding" element={<Outlet />}>
          <Route path="payment-methods/*" element={<OnboardingPaymentMethods />} />
          <Route path="business-website-details" element={<OnboardingBusinessWebsite />} />
        </Route>
      </Routes>
    </ErrorBoundary>
  );
};

export default OnboardingRoutesWrapper;
