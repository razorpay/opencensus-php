import React, { Component } from 'react';
import { Route, Switch, NavLink } from 'react-router-dom';
import ShowWhen from 'merchant/components/ShowWhen';

import PaymentsList from 'merchant/containers/Payments/List';
import RefundsList from 'merchant/containers/Refunds/List';
import BatchUpload from 'merchant/containers/Refunds/BatchUpload';
import BatchUploads from 'merchant/containers/Refunds/BatchList';
import OrdersList from 'merchant/containers/Orders/List';

export default class TransactionsContainer extends Component {
  render() {
    return (
      <tabbed-container>
        <header id="transactions-header">
          <NavLink to="/payments">Payments</NavLink>
          <NavLink to="/refunds">Refunds</NavLink>
          <NavLink to="/orders">Orders</NavLink>
        </header>

        <Switch>
          <Route path="/refunds/batchupload" component={BatchUpload} />
          <Route path="/refunds/batchuploads" component={BatchUploads} />
          <Route path="/refunds" component={RefundsList} />
          <Route path="/orders" component={OrdersList} />
          <Route path="/payments" component={PaymentsList} />
        </Switch>
      </tabbed-container>
    );
  }
}
