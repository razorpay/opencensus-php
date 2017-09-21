import React, { Component } from 'react';
import { Route, Switch, NavLink } from 'react-router-dom';
import TestModeBanner from 'merchant/containers/TestModeBanner';

import SubscriptionsList from 'merchant/containers/Subscriptions/List';
import PlansList from 'merchant/containers/Plans/List';
import AddOnsList from 'merchant/containers/AddOns/List';

export default class PaymentLinksContainer extends Component {
  render() {
    return (
      <tabbed-container>
        <header id="subscriptions-header">
          <NavLink to="/subscriptions">Subscriptions</NavLink>
          <NavLink to="/plans">Plans</NavLink>
          {/*<NavLink to="/addons">Add Ons</NavLink>*/}
        </header>
        <TestModeBanner />
        <content>
          <Switch>
            <Route path="/subscriptions" component={SubscriptionsList} />
            <Route path="/plans" component={PlansList} />
            {/*<Route path="/addons" component={AddOnsList} />*/}
          </Switch>
        </content>
      </tabbed-container>
    );
  }
}
