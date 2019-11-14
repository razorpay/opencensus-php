import React, { Component } from 'react';
import { connect } from 'react-redux';
import { Route, Switch, NavLink } from 'react-router-dom';
import { ShowWhenRoute } from 'merchant/components/ShowWhen';
import { getURLQueryParams } from 'common/utils/rzp-utils';
import ShowWhen from 'merchant/components/ShowWhen';
import TestModeBanner from 'merchant/containers/TestModeBanner';
import PaymentsList from 'merchant/containers/Payments/List';
import PaymentsBatchList from 'merchant/containers/Payments/BatchList';
import RefundsList from 'merchant/containers/Refunds/List';
import BatchUpload from 'merchant/containers/Refunds/BatchUpload';
import BatchUploads from 'merchant/containers/Refunds/BatchList';
import OrdersList from 'merchant/containers/Orders/List';
import DisputesList from 'merchant/containers/Disputes/List';
import EnableSettlementsBanner from 'merchant/components/EnableSettlementsBanner';
import ScheduledBanner from './Settlements/ScheduledBanner';

@connect(state => state.session)
export default class TransactionsContainer extends Component {
  constructor(props) {
    super(props);
  }

  render() {
    const { user, mode } = this.props,
      { showInstantActivation, isSubmitted } = user;

    return (
      <tabbed-container>
        <header id="transactions-header">
          <NavLink to="/payments" exact>
            Payments
          </NavLink>
          <ShowWhen
            featureEnabled="direct_debit"
            additionalCondition={user =>
              user.isAllowedView('payments_batch_uploads')
            }
          >
            <NavLink to="/payments/batchuploads">Batch Payments</NavLink>
          </ShowWhen>
          <ShowWhen additionalCondition={user => user.isAllowedView('refunds')}>
            <NavLink to="/refunds" exact>
              Refunds
            </NavLink>
          </ShowWhen>
          <ShowWhen
            featureEnabled="Batchrefunds"
            additionalCondition={user =>
              user.isAllowedView('refunds_batch_uploads')
            }
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
          <ShowWhen additionalCondition={user => user.isAllowedView('orders')}>
            <NavLink to="/orders">Orders</NavLink>
          </ShowWhen>
          <NavLink to="/disputes">Disputes</NavLink>
          {/*this.props.user.isOndemandSettlementEnabled && (
            <ScheduledBanner fromWhere="Transactions" />
          )*/}
        </header>
        {showInstantActivation && !isSubmitted && mode === 'live' ? (
          <EnableSettlementsBanner />
        ) : (
          <TestModeBanner />
        )}
        <content>
          <Switch>
            <Route path="/refunds/batchupload" component={BatchUpload} />
            <Route path="/refunds/batchuploads" component={BatchUploads} />
            <Route path="/refunds" component={RefundsList} />
            <ShowWhenRoute
              path="/orders"
              component={OrdersList}
              additionalCondition={user => user.isAllowedView('orders')}
            />
            <Route
              path="/payments/batchuploads/:mode"
              component={PaymentsBatchList}
            />
            <Route
              path="/payments/batchuploads"
              component={PaymentsBatchList}
            />
            <Route path="/payments" component={PaymentsList} />
            <Route path="/disputes" component={DisputesList} />
          </Switch>
        </content>
      </tabbed-container>
    );
  }
}
