import React, { Component } from 'react';
import { connect } from 'react-redux';
import { Route, Switch, NavLink } from 'react-router-dom';
import { ShowWhenRoute } from 'merchant/components/ShowWhen';
import { getURLQueryParams } from 'common/utils/rzp-utils';
import ShowWhen from 'merchant/components/ShowWhen';
import TestModeBanner from 'merchant/containers/TestModeBanner';
import PaymentsList from 'merchant/views/Transactions/Payments/List';
import PaymentsBatchList from 'merchant/views/Transactions/Payments/BatchList';
import RefundsList from 'merchant/views/Transactions/Refunds/List';
import BatchUpload from 'merchant/views/Transactions/Refunds/BatchUpload';
import BatchUploads from 'merchant/views/Transactions/Refunds/BatchList';
import OrdersList from 'merchant/views/Transactions/Orders/List';
import DisputesList from 'merchant/views/Transactions/Disputes/List';
import EnableSettlementsBanner from 'merchant/components/EnableSettlementsBanner';
import ScheduledBanner from 'merchant/views/Settlements/components/ScheduledBanner';
import OnHoldBanner from 'common/ui/OnHoldBanner';
import { fetchSettlementAmount } from 'merchant/reducers/home';
import Amount from 'common/ui/Amount';
import SettlementDetail from 'merchant/views/Settlements/components/SettlementDetail';
import { openModal } from 'merchant_common/reducers/modals';
import Time from 'common/ui/Time';

@connect(
  state => {
    return {
      ...state.session,
      settlement_amount: state.home.settlement_amount,
    };
  },
  { fetchSettlementAmount, openModal }
)
export default class TransactionsContainer extends Component {
  constructor(props) {
    super(props);
  }

  componentDidMount() {
    this.props.fetchSettlementAmount();
  }

  render() {
    const { user, mode } = this.props,
      { showInstantActivation, isSubmitted } = user;

    const nextSettlement = !this.props.settlement_amount.data
      .next_settlement_time;

    return (
      <tabbed-container>
        <header id="transactions-header" class="flex">
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
          {this.props.user.isOndemandSettlementEnabled && (
            <ShowWhen myRole="owner admin finance">
              <ScheduledBanner fromWhere="Transactions" />
            </ShowWhen>
          )}
          {!nextSettlement ? (
            <div class="text-right" style={{ width: '65%' }}>
              <strong>
                <Amount
                  value={this.props.settlement_amount.data.settlement_amount}
                  currency={'INR'}
                />
              </strong>{' '}
              will be settled by
              <Time
                value={this.props.settlement_amount.data.next_settlement_time}
                format={'DD MMM YYYY, hh:mm:ss a'}
              />{' '}
              <span
                class="btn-link"
                style={{ marginLeft: '5px' }}
                onClick={() => {
                  this.props.openModal({
                    size: 'regular',
                    component: (
                      <SettlementDetail
                        settlementAmount={this.props.settlement_amount.data}
                      />
                    ),
                  });

                  window.rzpAnalytics({
                    eventCategory: 'Dashboard - Settlement UI Revamp',
                    eventAction: 'Click Know More',
                  });
                }}
              >
                Know more
              </span>
            </div>
          ) : null}
        </header>
        {showInstantActivation && !isSubmitted && mode === 'live' ? (
          <EnableSettlementsBanner />
        ) : (
          <TestModeBanner />
        )}
        {nextSettlement ? (
          <OnHoldBanner
            ctaOnClick={() => {
              this.props.openModal({
                size: 'regular',
                component: (
                  <SettlementDetail
                    settlementAmount={this.props.settlement_amount.data}
                  />
                ),
              });

              window.rzpAnalytics({
                eventCategory: 'Dashboard - Settlement UI Revamp',
                eventAction: 'Click Know More(On Hold)',
              });
            }}
          />
        ) : null}

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
