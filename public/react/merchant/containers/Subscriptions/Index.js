import React, { Component } from 'react';
import { Route, Switch, NavLink } from 'react-router-dom';

import SubscriptionsList from 'merchant/containers/Subscriptions/List';
import PlansList from 'merchant/containers/Plans/List';

export default class PaymentLinksContainer extends Component {
  render() {
    return (
      <tabbed-container>
        <header id="subscriptions-header">
          <NavLink to="/subscriptions">Subscriptions</NavLink>
          <NavLink to="/plans">Plans</NavLink>
        </header>

        <content>
          <Switch>
            <Route path="/subscriptions" component={SubscriptionsList} />
            <Route path="/plans" component={PlansList} />
          </Switch>
        </content>
      </tabbed-container>
    );
  }
}
