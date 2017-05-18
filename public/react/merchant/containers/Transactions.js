import React, { Component } from 'react';
import { Route, NavLink } from 'react-router-dom';

import TransactionSlider from 'merchant/containers/TransactionSlider';

import PaymentsList from 'merchant/containers/Payments/List';
import RefundsList from 'merchant/containers/Refunds/List';
import OrdersList from 'merchant/containers/Orders/List';
import BatchUpload from 'merchant/containers/Refunds/BatchUpload';
import BatchUploads from 'merchant/containers/Refunds/BatchList';

export default class TransactionsContainer extends Component {
  render() {
    return (
      <tabbed-container>
        <header id="transactions-header">
          <NavLink to="/app/payments">Payments</NavLink>
          <NavLink to="/app/refunds">Refunds</NavLink>
          <NavLink to="/app/orders">Orders</NavLink>
        </header>

        <Route path="/app/payments" component={PaymentsList} />

        <Route path="/app/refunds(/rfnd_.+)*" exact component={RefundsList} />
        <Route path="/app/refunds/batchupload" component={BatchUpload} />
        <Route path="/app/refunds/batchuploads" component={BatchUploads} />
        <Route path="/app/orders" component={OrdersList} />

        <Route
          path="/app/:entity/:id(rfnd_.+|pay_.+|order_.+)"
          component={TransactionSlider}
        />

      </tabbed-container>
    );
  }
}
