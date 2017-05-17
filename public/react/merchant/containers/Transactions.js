import React, { Component } from 'react';
import { Route, NavLink } from 'react-router-dom';

import TransactionSlider from 'merchant/containers/TransactionSlider';

import PaymentsList from 'merchant/containers/Payments/List';
import RefundsList from 'merchant/containers/Refunds/List';
import OrdersList from 'merchant/containers/Orders/List';

export default class TransactionsContainer extends Component {
  render() {
    return (
      <tabbed-container>
        <header>
          <NavLink to="/app/payments">Payments</NavLink>
          <NavLink to="/app/refunds">Refunds</NavLink>
          <NavLink to="/app/orders">Orders</NavLink>
          <NavLink to="/app/batch-refunds">Batch Refunds</NavLink>
        </header>

        <Route path="/app/payments" component={PaymentsList} />
        <Route path="/app/refunds" component={RefundsList} />
        <Route path="/app/orders" component={OrdersList} />

        <Route path="/app/:entity/:id" component={TransactionSlider} />
      </tabbed-container>
    );
  }
}
