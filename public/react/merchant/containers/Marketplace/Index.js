import React, { Component } from 'react';
import { Route, Switch, NavLink } from 'react-router-dom';

import PaymentsList from 'merchant/containers/Marketplace/Payments/List';
import AccountsList from 'merchant/containers/Marketplace/Accounts/List';

export default class TransactionsContainer extends Component {
  render() {
    return (
      <tabbed-container>
        <header id="marketplace-header">
          <NavLink to="/marketplace/payments">Payments</NavLink>
          <NavLink to="/marketplace/accounts">Accounts</NavLink>
        </header>

        <Switch>
          <Route path="/marketplace/payments" component={PaymentsList} />
          <Route path="/marketplace/accounts" component={AccountsList} />
        </Switch>
      </tabbed-container>
    );
  }
}
