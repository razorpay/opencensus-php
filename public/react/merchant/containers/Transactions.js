import React, { Component } from 'react';
import { Route, Switch, NavLink } from 'react-router-dom';
import ShowWhen from 'merchant/components/ShowWhen';

import PaymentsList from 'merchant/containers/Payments/List';
import PaymentsDetails from 'merchant/containers/Payments/Details';

import RefundsList from 'merchant/containers/Refunds/List';
import RefundDetails from 'merchant/containers/Refunds/Details';
import BatchUpload from 'merchant/containers/Refunds/BatchUpload';
import BatchUploads from 'merchant/containers/Refunds/BatchList';

import OrdersList from 'merchant/containers/Orders/List';
import OrderDetails from 'merchant/containers/Orders/Details';

export default class TransactionsContainer extends Component {
  render() {
    return (
      <tabbed-container>
        <header id="transactions-header">
          <NavLink to="/app/payments">Payments</NavLink>
          <NavLink to="/app/refunds">Refunds</NavLink>
          <NavLink to="/app/orders">Orders</NavLink>
        </header>

        <Switch>
          <Route path="/app/refunds/batchupload" component={BatchUpload} />
          <Route path="/app/refunds/batchuploads" component={BatchUploads} />
          <Route path="/app/refunds/:id" component={RefundDetails} />
          <Route path="/app/refunds" component={RefundsList} />

          <Route path="/app/orders/:id" component={OrderDetails} />
          <Route path="/app/orders" component={OrdersList} />

          <Route path="/app/payments/:id" component={PaymentsDetails} />
          <Route path="/app/payments" component={PaymentsList} />
        </Switch>
      </tabbed-container>
    );
  }
}
