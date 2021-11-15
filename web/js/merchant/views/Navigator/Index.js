import React from 'react';
import { connect } from 'react-redux';
import { Route, Switch, Redirect } from 'react-router-dom';
import CreateRule from './components/CreateRule';
import RuleList from './components/RuleList';
import AddProvider from './components/AddProvider';
import {
  fetchRules,
  fetchRule,
  fetchRuleProviders,
  fetchTerminalProviders,
} from 'merchant/reducers/navigator/details';
import { loadCheckout } from 'merchant/utils/fetchKeysAndCheckout';
import { ShowWhenRoute } from 'merchant/components/ShowWhen';
import ErrorBoundary from 'common/new-ui/ErrorBoundary';

@connect(
  (state) => {
    return {
      user: state.session.user,
    };
  },
  {
    fetchRule,
    fetchRuleProviders,
    fetchRules,
    fetchTerminalProviders,
  },
)
export default class Navigator extends React.Component {
  componentDidMount() {
    this.props.fetchRules();
    this.props.fetchRuleProviders();
    if (this.props.user.isAddProviderEnabled) {
      this.props.fetchTerminalProviders();
    }
    loadCheckout(window.api_host);
  }
  render() {
    return (
      <div className="routing-navigator">
        <ErrorBoundary resetOnProps>
          <Switch>
            <ShowWhenRoute
              path="/optimizer/add-provider"
              component={AddProvider}
              additionalCondition={(user) => user.isAddProviderEnabled}
            />
            <ShowWhenRoute
              path="/optimizer/update-provider/:id"
              component={AddProvider}
              additionalCondition={(user) => user.isAddProviderEnabled}
            />
            <Route path="/optimizer/create-rule" component={CreateRule} />
            <Route path="/optimizer/rules" component={RuleList} />
            <Route path="/optimizer/update-rule/:id" component={CreateRule} />
            <Redirect to="/optimizer/rules" />
          </Switch>
        </ErrorBoundary>
      </div>
    );
  }
}
