import React, { lazy } from 'react';
import { Routes, Route, Outlet, Navigate } from 'react-router-dom';

import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import { RouteGuard } from 'merchant/components/ShowWhen';
import { Platform } from 'merchant/views/ApiKeysAndPlugins/KeysAndPlugins/types';
import {
  getIntentChannels,
  getProvidedChannels,
} from 'merchant/views/ApiKeysAndPlugins/KeysAndPlugins/utils';

const OnboardingBusinessWebsite = lazy(
  () =>
    import(
      /* webpackChunkName: "OnboardingBusinessWebsite" */ 'merchant/views/AccountAndSettings/WebsiteAppSettings/Tabs/BusinessWebsiteDetails/v2/OnboardingBusinessWebsite'
    ),
);

const ApiKeysAndPlugins = lazy(
  () => import(/* webpackChunkName: "ApiKeysAndPlugins" */ 'merchant/views/ApiKeysAndPlugins'),
);

const OnboardingRoutesWrapper = (): JSX.Element => {
  return (
    <ErrorBoundary>
      <Routes>
        <Route path="onboarding" element={<Outlet />}>
          <Route path="business-website-details" element={<OnboardingBusinessWebsite />} />
          <Route
            path="api-keys"
            element={
              <RouteGuard
                additionalCondition={(user) =>
                  (!!getProvidedChannels(user).length ||
                    !!getIntentChannels(user, ['business_website'] as Platform[])?.length) && // Either an user channel has been verified or user has intent of Website channel (as in FTUX1.5)
                  (user.isFtuxEnabled ||
                    ((user.isProductLedOnboardingRZP || user.isApiKeysRevampEnabled) &&
                      user.activated))
                }
              >
                <ApiKeysAndPlugins isFullScreenMode={true} />
              </RouteGuard>
            }
          />
          <Route path="*" element={<Navigate to="/" />} />
        </Route>
      </Routes>
    </ErrorBoundary>
  );
};

export default OnboardingRoutesWrapper;
