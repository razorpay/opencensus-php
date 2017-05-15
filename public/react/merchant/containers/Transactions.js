import React, { Component } from 'react';
import { Route, NavLink, withRouter } from 'react-router-dom';
import Modal from 'react-modal';

import PaymentsList from 'merchant/containers/Payments/List';
import PaymentDetails from 'merchant/containers/Payments/Details';
import RefundsList from 'merchant/containers/Refunds/List';
import RefundDetails from 'merchant/containers/Refunds/Details';
import OrdersList from 'merchant/containers/Orders/List';
import OrderDetails from 'merchant/containers/Orders/Details';

@withRouter
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
      </tabbed-container>
    );
  }
}
