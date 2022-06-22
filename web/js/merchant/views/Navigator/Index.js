import React from 'react';
import { connect } from 'react-redux';
import { Route, Switch, Redirect } from 'react-router-dom';
import store from 'merchant/store';
import CreateRule from './components/CreateRule';
import RuleList from './components/RuleList';
import AddProvider from './components/AddProvider';
import { fetchRules, fetchRule, fetchTerminalProviders } from 'merchant/reducers/navigator/details';
import { loadCheckout } from 'merchant/utils/fetchKeysAndCheckout';
import { ShowWhenRoute } from 'merchant/components/ShowWhen';
import { ShowWhenRoute as showWhenRouteWithDefaultPath } from 'merchant_common/components/ShowWhen';
import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import OnBoarding from './components/OnBoarding';
import { removeItem } from 'common/utils/localStorage';
import {
  getOptimizerOnboardingStorageKey,
  shouldShowRules,
  shouldShowOnBoarding,
} from './components/util';

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
    user?.isOptimizerEnabled && removeItem(getOptimizerOnboardingStorageKey(user));

    return (
      <div className="routing-navigator">
        <ErrorBoundary resetOnProps>
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
        </ErrorBoundary>
      </div>
    );
  }
}
