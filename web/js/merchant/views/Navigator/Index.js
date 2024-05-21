import React from 'react';
import { connect } from 'react-redux';
import { Route, Routes, Navigate } from 'react-router-dom';
import { compose } from 'redux';

import { withRouter } from 'common/deprecated/withRouter';
import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import { withSplitzService } from 'common/splitz';
import { isExperimentEnabled } from 'common/splitz/utils';
import { RouteGuard } from 'merchant/components/ShowWhen';
import { fetchRules, fetchRule, fetchTerminalProviders } from 'merchant/reducers/navigator/details';
import lazy from 'merchant/routes/LazyLoader';
import { loadCheckout } from 'merchant/utils/fetchKeysAndCheckout';
import { shouldShowRules, shouldShowOnBoarding } from 'merchant/views/Navigator/components/util';
import { isIntegrationAuditEnabled } from 'merchant/views/Optimizer/AddProvider/utils';

const AddProvider = lazy(() =>
  import(/* webpackChunkName: 'AddProvider' */ 'merchant/views/Navigator/components/AddProvider'),
);

const AddProviderV2 = lazy(() =>
  import(/* webpackChunkName: 'AddProviderV2' */ 'merchant/views/Optimizer/AddProvider'),
);

const ProviderDetailsV2 = lazy(() =>
  import(
    /* webpackChunkName: "componentsProviderDetailsV2" */ 'merchant/views/Optimizer/AddProvider/ProviderView'
  ),
);

const CreateRule = lazy(() =>
  import(/* webpackChunkName: 'CreateRule' */ 'merchant/views/Navigator/components/CreateRule'),
);

const RuleList = lazy(() =>
  import(/* webpackChunkName: 'RuleList' */ 'merchant/views/Navigator/components/RuleList'),
);

const OnBoarding = lazy(() =>
  import(/* webpackChunkName: 'OnBoarding' */ 'merchant/views/Navigator/components/OnBoarding'),
);

const addProviderRevamp = (splitz) => {
  const { abExperiments } = splitz || { abExperiments: { add_provider_revamp: undefined } };

  if (!abExperiments?.add_provider_revamp) return false;

  return isExperimentEnabled(abExperiments.add_provider_revamp);
};

class Navigator extends React.Component {
  componentDidMount() {
    const { fetchRules, fetchTerminalProviders } = this.props;
    fetchRules();
    fetchTerminalProviders();
    loadCheckout(window.api_host);
  }

  render() {
    const { user, splitz } = this.props;
    const addProviderExpEnabled = addProviderRevamp(splitz);
    const integrationAuditFlowEnabled = isIntegrationAuditEnabled(splitz);

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
                    {addProviderExpEnabled ? <AddProviderV2 /> : <AddProvider />}
                  </RouteGuard>
                }
              />
              <Route
                path="update-provider/:id/*"
                element={
                  <RouteGuard>
                    {addProviderExpEnabled ? <AddProviderV2 /> : <AddProvider />}
                  </RouteGuard>
                }
              />

              <Route
                path="provider/:id/*"
                element={
                  <RouteGuard>
                    {integrationAuditFlowEnabled ? <ProviderDetailsV2 /> : null}
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
                    <RuleList />
                  </RouteGuard>
                }
              />

              <Route path="update-rule/:id/*" element={<CreateRule />} />

              <Route
                path="onboarding/*"
                element={
                  <RouteGuard
                    additionalCondition={(user) =>
                      shouldShowOnBoarding(user) || shouldShowRules(user)
                    }
                  >
                    <OnBoarding />
                  </RouteGuard>
                }
              />

              <Route path="*" element={<Navigate to={redirectionURL} replace />} />
            </Routes>
          </SuspenseWithLoader>
        </ErrorBoundary>
      </div>
    );
  }
}

export default compose(
  withSplitzService,
  connect(
    (state) => ({
      user: state.session.user,
    }),
    {
      fetchRule,
      fetchRules,
      fetchTerminalProviders,
    },
  ),
)(withRouter(Navigator));
