import React, { useEffect } from 'react';
import { connect } from 'react-redux';
import { Route, Routes, Navigate } from 'react-router-dom';
import { compose, bindActionCreators } from 'redux';

import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import { withSplitzService } from 'common/splitz';
import { RouteGuard } from 'merchant/components/ShowWhen';
import { fetchRules, fetchRule, fetchTerminalProviders } from 'merchant/reducers/navigator/details';
import lazy from 'merchant/routes/LazyLoader';
import { shouldShowRules } from 'merchant/views/Navigator/components/util';
import { isIntegrationAuditEnabled } from 'merchant/views/Optimizer/AddProvider/utils';
import { isSMEOnboardingEnabled } from './utils';

const AddProvider = lazy(
  () =>
    import(/* webpackChunkName: 'OptimizerAddProvider' */ 'merchant/views/Optimizer/AddProvider'),
);

const ProviderDetailsV2 = lazy(
  () =>
    import(
      /* webpackChunkName: "OptimizerProviderDetailsV2" */ 'merchant/views/Optimizer/AddProvider/ProviderView'
    ),
);

const CreateRule = lazy(
  () =>
    import(
      /* webpackChunkName: 'OptimizerCreateRule' */ 'merchant/views/Optimizer/Rules/CreateRule'
    ),
);

const LandingPage = lazy(
  () =>
    import(/* webpackChunkName: 'OptimizerLandingPage' */ 'merchant/views/Optimizer/LandingPage'),
);

const OnBoarding = lazy(
  () =>
    import(
      /* webpackChunkName: 'OptimizerOnBoarding' */ 'merchant/views/Navigator/components/OnBoarding'
    ),
);

const SMEOnBoarding = lazy(
  () => import(/* webpackChunkName: 'OptimizerOnBoarding' */ 'merchant/views/Optimizer/OnBoarding'),
);

const Optimizer = ({ fetchRules, fetchTerminalProviders, user, splitz }): JSX.Element => {
  useEffect(() => {
    fetchRules();
    fetchTerminalProviders();
  }, []);

  const isIntegrationAuditFlowEnabled = isIntegrationAuditEnabled(splitz);

  const redirectionURL = shouldShowRules(user) ? '/optimizer/rules' : '/optimizer/onboarding';

  return (
    <div className="routing-navigator">
      <ErrorBoundary resetOnProps>
        <SuspenseWithLoader type="center">
          <Routes>
            <Route
              path="add-provider/*"
              element={
                <RouteGuard>
                  <AddProvider />
                </RouteGuard>
              }
            />
            <Route
              path="update-provider/:id/*"
              element={
                <RouteGuard>
                  <AddProvider />
                </RouteGuard>
              }
            />

            <Route
              path="provider/:id/*"
              element={
                <RouteGuard>
                  {isIntegrationAuditFlowEnabled ? <ProviderDetailsV2 /> : null}
                </RouteGuard>
              }
            />

            <Route
              path="create-rule/*"
              element={
                <RouteGuard>
                  <CreateRule />
                </RouteGuard>
              }
            />

            <Route
              path="rules/*"
              element={
                <RouteGuard defaultPath="onboarding/*" additionalCondition={shouldShowRules}>
                  <LandingPage />
                </RouteGuard>
              }
            />

            <Route path="update-rule/:id/*" element={<CreateRule />} />

            <Route
              path="onboarding/*"
              element={
                <RouteGuard
                  additionalCondition={(user) =>
                    user.isOptimizerOnboardingEnabled && !user.isOptimizerEnabled
                  }
                >
                  {isSMEOnboardingEnabled(splitz) ? <SMEOnBoarding /> : <OnBoarding />}
                </RouteGuard>
              }
            />

            <Route path="*" element={<Navigate to={redirectionURL} replace />} />
          </Routes>
        </SuspenseWithLoader>
      </ErrorBoundary>
    </div>
  );
};

const mapStateToProps = (state) => {
  const { session } = state;
  return {
    user: session?.user,
  };
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      fetchRule,
      fetchRules,
      fetchTerminalProviders,
    },
    dispatch,
  );
};

export default compose<any>(
  withSplitzService,
  connect(mapStateToProps, mapDispatchToProps),
)(Optimizer);
