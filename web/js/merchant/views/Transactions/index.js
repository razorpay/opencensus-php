import React, { Component } from 'react';
import { connect } from 'react-redux';
import { Route, Switch, NavLink } from 'react-router-dom';
import { ShowWhenRoute } from 'merchant/components/ShowWhen';
import { getURLQueryParams } from 'common/utils/rzp-utils';
import ShowWhen from 'merchant/components/ShowWhen';
import TestModeBanner from 'merchant/components/TestModeBanner';
import PaymentsList from 'merchant/views/Transactions/Payments/List';
import RefundsList from 'merchant/views/Transactions/Refunds/List';
import OrdersList from 'merchant/views/Transactions/Orders/List';
import DisputesList from 'merchant/views/Transactions/Disputes/List';
import BatchPaymentsList from 'merchant/views/Transactions/BatchPayments/List';
import BatchRefundsList from 'merchant/views/Transactions/BatchRefunds/List';
import BatchRefundsUpload from 'merchant/views/Transactions/BatchRefunds/BatchUpload';
import HeaderAction from 'common/ui/HeaderAction';
import OnHoldBanner from 'common/ui/OnHoldBanner';
import { fetchSettlementAmount } from 'merchant/reducers/home';
import Amount from 'common/ui/Amount';
import SettlementDetail from 'merchant/views/Settlements/components/SettlementDetail';
import { openModal } from 'merchant_common/reducers/modals';
import Time from 'common/ui/Time';
import Popover, { PopoverBody } from 'common/ui/Popover';
import ScheduledNitroBanner from 'merchant/components/ScheduledNitroBanner';
import SettlementSchedule from 'merchant/views/Settlements/components/SettlementSchedule';

@connect(
  (state) => {
    return {
      ...state.session,
      settlement_amount: state.home.settlement_amount,
      config: state.config.config,
      payments: state.payments,
    };
  },
  { fetchSettlementAmount, openModal }
)
export default class TransactionsContainer extends Component {
  constructor(props) {
    super(props);
    this.state = {
      openAutoModal: false,
    };
  }

  componentDidMount() {
    this.props.fetchSettlementAmount();
  }

  viewSettlementCycle = () => {
    this.props.openModal({
      size: 'medium',
      component: <SettlementSchedule holidayList={this.props.holidayList} />,
    });

    window.rzpAnalytics({
      eventCategory: 'Settlement Revamp',
      eventAction: 'View Settlement Cycle',
      eventLabel: `Settlements`,
    });
  };

  render() {
    const { user, mode } = this.props,
      { showInstantActivation, isSubmitted } = user;

    const nextSettlement = !this.props.settlement_amount.data.next_settlement_time;

    const { no_settlement } = this.props.settlement_amount.data;

    const pathname = this.props.location.pathname;

    return (
      <tabbed-container>
        <header id="transactions-header">
          <NavLink to="/payments" exact>
            Payments
          </NavLink>
          <ShowWhen
            featureEnabled="direct_debit"
            additionalCondition={(user) => user.isAllowedView('payments_batch_uploads')}
          >
            <NavLink to="/payments/batchuploads">Batch Payments</NavLink>
          </ShowWhen>
          <ShowWhen additionalCondition={(user) => user.isAllowedView('refunds')}>
            <NavLink to="/refunds" exact>
              Refunds
            </NavLink>
          </ShowWhen>
          <ShowWhen
            featureEnabled="Batchrefunds"
            additionalCondition={(user) => user.isAllowedView('refunds_batch_uploads')}
          >
            <NavLink
              to="/refunds/batchuploads"
              isActive={(match, { pathname }) =>
                pathname === '/refunds/batchupload' || pathname === '/refunds/batchuploads'
              }
            >
              Batch Refunds
            </NavLink>
          </ShowWhen>
          <ShowWhen additionalCondition={(user) => user.isAllowedView('orders')}>
            <NavLink to="/orders">Orders</NavLink>
          </ShowWhen>
          <NavLink to="/disputes">Disputes</NavLink>
          {no_settlement &&
          (pathname === '/payments' || pathname === '/refunds' || pathname === '/orders') &&
          mode === 'live' &&
          this.props.payments &&
          this.props.payments.items.length > 0 ? (
            <div class="text-right settlement-caption">
              {no_settlement.caption}
              {no_settlement.reason && (
                <React.Fragment>
                  <div style={{ display: 'inline' }}>
                    <i class="i i-info-circle" />
                    <Popover theme="dark" align="left">
                      <PopoverBody>
                        <div>{no_settlement.reason}</div>
                      </PopoverBody>
                    </Popover>
                  </div>
                </React.Fragment>
              )}
            </div>
          ) : null}
          {!no_settlement &&
          !nextSettlement &&
          (pathname === '/payments' || pathname === '/refunds' || pathname === '/orders') ? (
            <div class="text-right" style={{ width: '100%' }}>
              <strong>
                <Amount
                  value={this.props.settlement_amount.data.settlement_amount}
                  currency={'INR'}
                />
              </strong>{' '}
              will be settled on{' '}
              <Time
                value={this.props.settlement_amount.data.next_settlement_time}
                format={'DD MMM YYYY, hh:mm:ss a'}
              />{' '}
              {this.props.settlement_amount.data.reason_for_delay && (
                <React.Fragment>
                  <div style={{ display: 'inline' }}>
                    <i class="i i-info-circle" />
                    <Popover theme="dark" align="left">
                      <PopoverBody>
                        <div>{this.props.settlement_amount.data.reason_for_delay}</div>
                      </PopoverBody>
                    </Popover>
                  </div>
                </React.Fragment>
              )}
              <span
                class="btn-link"
                style={{ marginLeft: '5px' }}
                onClick={() => {
                  this.props.openModal({
                    size: 'medium',
                    component: (
                      <SettlementDetail
                        user={user}
                        settlementAmount={this.props.settlement_amount.data}
                      />
                    ),
                  });

                  window.rzpAnalytics({
                    eventCategory: 'Settlement Revamp',
                    eventAction: 'Know more - Next Settlement',
                    eventLabel: `Payments`,
                  });
                }}
              >
                Know more
              </span>
            </div>
          ) : null}
        </header>
        <HeaderAction>
          <div class="settlement-actions-wrapper">
            <div
              class="btn btn-link settlement-doc-btn"
              onClick={this.viewSettlementCycle}
            >
              <span class="icon i-info-outline settlement-announcement" />
              View Settlement Cycle
            </div>
            <ShowWhen additionalCondition={user => user.isProjectNitroEnabled}>
              <div class="box-left-pad10-inline">
                <ScheduledNitroBanner fromWhere="transaction" url="https://lp.razorpay.com/razorpayxca-pymnts1"/>
              </div>
            </ShowWhen>
          </div>
        </HeaderAction>
        <TestModeBanner />

        {mode === 'live' && nextSettlement && no_settlement && no_settlement.on_hold === true ? (
          <OnHoldBanner
            payments={this.props.payments}
            user={user}
            ctaOnClick={() => {
              this.props.openModal({
                size: 'medium',
                component: (
                  <SettlementDetail
                    user={user}
                    settlementAmount={this.props.settlement_amount.data}
                  />
                ),
              });

              window.rzpAnalytics({
                eventCategory: 'Settlement Revamp',
                eventAction: 'View details - Funds on Hold',
                eventLabel: `Payments`,
              });
            }}
          />
        ) : null}

        <content>
          <Switch>
            <Route path="/refunds/batchupload" component={BatchRefundsUpload} />
            <Route path="/refunds/batchuploads" component={BatchRefundsList} />
            <Route path="/refunds" component={RefundsList} />
            <ShowWhenRoute
              path="/orders"
              component={OrdersList}
              additionalCondition={(user) => user.isAllowedView('orders')}
            />
            <Route path="/payments/batchuploads/:mode" component={BatchPaymentsList} />
            <Route path="/payments/batchuploads" component={BatchPaymentsList} />
            <Route path="/payments" component={PaymentsList} />
            <Route path="/disputes" component={DisputesList} />
          </Switch>
        </content>
      </tabbed-container>
    );
  }
}
