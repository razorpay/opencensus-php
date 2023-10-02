import React from 'react';
import { connect } from 'react-redux';
import { Route, Routes, Navigate } from 'react-router-dom';

import { withRouter } from 'common/deprecated/withRouter';
import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import { fetchRules, fetchRule, fetchTerminalProviders } from 'merchant/reducers/navigator/details';
import lazy from 'merchant/routes/LazyLoader';
import { loadCheckout } from 'merchant/utils/fetchKeysAndCheckout';
import { shouldShowRules, shouldShowOnBoarding } from 'merchant/views/Navigator/components/util';
import { RouteGuard } from 'merchant/components/ShowWhen';

const AddProvider = lazy(() =>
  import(/* webpackChunkName: 'AddProvider' */ 'merchant/views/Navigator/components/AddProvider'),
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

@connect(
  (state) => {
    return {
      user: state.session.user,
    };
  },
  {
    fetchRule,
    fetchRules,
    fetchTerminalProviders,
  },
)
class Navigator extends React.Component {
  componentDidMount() {
    const { fetchRules, fetchTerminalProviders } = this.props;
    fetchRules();
    fetchTerminalProviders();
    loadCheckout(window.api_host);
  }

  render() {
    const { user } = this.props;

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

export default withRouter(Navigator);
