import { connect } from 'react-redux';
import { Route, Switch, NavLink, Link, Redirect } from 'react-router-dom';
import { Fragment } from 'react';
import CreateRule from './components/CreateRule';
import RuleList from './components/RuleList';
import { fetchRules, fetchRule, fetchRuleProviders } from 'merchant/reducers/navigator/details';
import { loadCheckout } from 'merchant/utils/fetchKeysAndCheckout';

@connect(
  (state) => {
    return state;
  },
  {
    fetchRule: fetchRule,
    fetchRuleProviders: fetchRuleProviders,
    fetchRules: fetchRules,
  },
)
export default class Navigator extends React.Component {
  componentDidMount() {
    this.props.fetchRules();
    this.props.fetchRuleProviders();
    loadCheckout(window.api_host);
  }
  render() {
    return (
      <Fragment>
        <div class="routing-navigator">
          <Switch>
            <Route path="/navigator/create-rule" component={CreateRule} />
            <Route path="/navigator/rules" component={RuleList} />
            <Route path="/navigator/update-rule/:id" component={CreateRule} />
            <Redirect to="/navigator/rules" />
          </Switch>
        </div>
      </Fragment>
    );
  }
}
