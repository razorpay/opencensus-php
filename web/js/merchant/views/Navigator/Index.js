import { connect } from 'react-redux';
import { Route, Switch, NavLink, Link, Redirect } from 'react-router-dom';
import { Fragment } from 'react';
import CreateRule from './components/CreateRule';
import RuleList from './components/RuleList';
import AddProvider from './components/AddProvider';
import { fetchRules, fetchRule, fetchRuleProviders, fetchTerminalProviders } from 'merchant/reducers/navigator/details';
import { loadCheckout } from 'merchant/utils/fetchKeysAndCheckout';
import { ShowWhenRoute } from 'merchant/components/ShowWhen';

@connect(
  (state) => {
    return {
      user: state.session.user,
    };
  },
  {
    fetchRule: fetchRule,
    fetchRuleProviders: fetchRuleProviders,
    fetchRules: fetchRules,
    fetchTerminalProviders: fetchTerminalProviders,
  },
)
export default class Navigator extends React.Component {
  componentDidMount() {
    this.props.fetchRules();
    this.props.fetchRuleProviders();
    if(this.props.user.isAddProviderEnabled) {
      this.props.fetchTerminalProviders();
    }
    loadCheckout(window.api_host);
  }
  render() {
    return (
      <Fragment>
        <div class="routing-navigator">
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
        </div>
      </Fragment>
    );
  }
}
