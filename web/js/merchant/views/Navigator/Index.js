import React from 'react';
import { connect } from 'react-redux';
import { Route, Switch, Redirect } from 'react-router-dom';

import store from 'merchant/store';
import { loadCheckout } from 'merchant/utils/fetchKeysAndCheckout';
import { fetchRules, fetchRule, fetchTerminalProviders } from 'merchant/reducers/navigator/details';
import lazy from 'merchant/routes/LazyLoader';
import { ShowWhenRoute } from 'merchant/components/ShowWhen';
import { ShowWhenRoute as showWhenRouteWithDefaultPath } from 'merchant_common/components/ShowWhen';

import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';

import { shouldShowRules, shouldShowOnBoarding } from 'merchant/views/Navigator/components/util';

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
export default class Navigator extends React.Component {
  componentDidMount() {
    const { fetchRules, fetchTerminalProviders } = this.props;
    fetchRules();
    fetchTerminalProviders();
    loadCheckout(window.api_host);
  }

  render() {
    const { user } = this.props;
    const ShowWhenRouteWithRulesDefaultPath = showWhenRouteWithDefaultPath(
      store,
      '/optimizer/onboarding',
    );
    const redirectionURL = shouldShowRules(user) ? '/optimizer/rules' : '/optimizer/onboarding';

    return (
      <div className="routing-navigator">
        <ErrorBoundary resetOnProps>
          <SuspenseWithLoader type="center">
            <Switch>
              <Route path="/optimizer/add-provider" component={AddProvider} />
              <Route path="/optimizer/update-provider/:id" component={AddProvider} />
              <Route path="/optimizer/create-rule" component={CreateRule} />
              <ShowWhenRouteWithRulesDefaultPath
                path="/optimizer/rules"
                component={RuleList}
                additionalCondition={shouldShowRules}
              />
              <Route path="/optimizer/update-rule/:id" component={CreateRule} />
              <ShowWhenRoute
                path="/optimizer/onboarding"
                component={OnBoarding}
                additionalCondition={(user) => shouldShowOnBoarding(user) || shouldShowRules(user)}
              />
              <Redirect to={redirectionURL} />
            </Switch>
          </SuspenseWithLoader>
        </ErrorBoundary>
      </div>
    );
  }
}
