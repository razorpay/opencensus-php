import React, { Component } from 'react';
import { Route, NavLink } from 'react-router-dom';

import TransactionSlider from 'merchant/containers/TransactionSlider';

import PaymentsList from 'merchant/containers/Payments/List';
import RefundsList from 'merchant/containers/Refunds/List';
import OrdersList from 'merchant/containers/Orders/List';
import BatchUpload from 'merchant/containers/Refunds/BatchUpload';
import BatchUploads from 'merchant/containers/Refunds/BatchList';

import PaymentsModal from 'merchant/containers/Payments/Details';

import { getURLQueryParams } from 'rzp/utils/rzp-utils';

const wrapperPaymentsModal = props => {
  return (
    <div class="content-wrapper content-sm">
      <PaymentsModal {...props} />
    </div>
  );
};

export default class TransactionsContainer extends Component {
  render() {
    const queryParams = getURLQueryParams(this.props['location']['search']);

    return (
      <tabbed-container>
        <header id="transactions-header">
          <NavLink to="/app/payments">Payments</NavLink>
          <NavLink to="/app/refunds">Refunds</NavLink>
          <NavLink to="/app/orders">Orders</NavLink>
        </header>

        {queryParams.type !== 'fullview'
          ? <Route path="/app/payments/:id?" component={PaymentsList} />
          : <Route
              path="/app/payments/:id?"
              component={wrapperPaymentsModal}
            />}

        <Route path="/app/refunds(/rfnd_.+)*" exact component={RefundsList} />
        <Route path="/app/refunds/batchupload" component={BatchUpload} />
        <Route path="/app/refunds/batchuploads" component={BatchUploads} />
        <Route path="/app/orders" component={OrdersList} />

        {queryParams.type === 'fullview'
          ? null
          : <Route
              path="/app/:entity/:id(rfnd_.+|pay_.+|order_.+)"
              component={TransactionSlider}
            />}
      </tabbed-container>
    );
  }
}
