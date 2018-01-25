import React, { Component } from 'react';
import { Route, Switch, NavLink } from 'react-router-dom';
import ShowWhen from 'merchant/components/ShowWhen';
import TestModeBanner from 'merchant/containers/TestModeBanner';
import PaymentsList from 'merchant/containers/Payments/List';
import RefundsList from 'merchant/containers/Refunds/List';
import BatchUpload from 'merchant/containers/Refunds/BatchUpload';
import BatchUploads from 'merchant/containers/Refunds/BatchList';
import OrdersList from 'merchant/containers/Orders/List';
import DisputesList from 'merchant/containers/Disputes/List';

export default class TransactionsContainer extends Component {
  render() {
    return (
      <tabbed-container>
        <header id="transactions-header">
          <NavLink to="/payments">Payments</NavLink>
          <NavLink to="/refunds" exact>
            Refunds
          </NavLink>
          <ShowWhen
            featureEnabled="Batchrefunds"
            myRole="owner manager operations admin finance"
          >
            <NavLink
              to="/refunds/batchuploads"
              isActive={(match, { pathname }) =>
                pathname === '/refunds/batchupload' ||
                pathname === '/refunds/batchuploads'
              }
            >
              Batch Refunds
            </NavLink>
          </ShowWhen>
          <NavLink to="/orders">Orders</NavLink>
          <NavLink to="/disputes">Disputes</NavLink>
        </header>
        <TestModeBanner />
        <content>
          <Switch>
            <Route path="/refunds/batchupload" component={BatchUpload} />
            <Route path="/refunds/batchuploads" component={BatchUploads} />
            <Route path="/refunds" component={RefundsList} />
            <Route path="/orders" component={OrdersList} />
            <Route path="/payments" component={PaymentsList} />
            <Route path="/disputes" component={DisputesList} />
          </Switch>
        </content>
      </tabbed-container>
    );
  }
}
