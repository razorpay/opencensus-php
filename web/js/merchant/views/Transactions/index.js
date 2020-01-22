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
import OnHoldBanner from 'common/ui/OnHoldBanner';
import { fetchSettlementAmount } from 'merchant/reducers/home';
import Amount from 'common/ui/Amount';
import SettlementDetail from 'merchant/views/Settlements/components/SettlementDetail';
import { openModal } from 'merchant_common/reducers/modals';
import Time from 'common/ui/Time';
import Popover, { PopoverBody } from 'common/ui/Popover';

@connect(
  state => {
    return {
      ...state.session,
      settlement_amount: state.home.settlement_amount,
      config: state.config.config,
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

    const { no_settlement } = this.props.settlement_amount.data;

    const { settlement_ux_revamp } = this.props.config;

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
          {settlement_ux_revamp && no_settlement ? (
            <div class="text-right" style={{ width: '100%' }}>
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
          {settlement_ux_revamp && !no_settlement && !nextSettlement ? (
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
                        <div>
                          {this.props.settlement_amount.data.reason_for_delay}
                        </div>
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
        {showInstantActivation && !isSubmitted && mode === 'live' ? (
          <EnableSettlementsBanner />
        ) : (
          <TestModeBanner />
        )}
        {settlement_ux_revamp && nextSettlement ? (
          <OnHoldBanner
            ctaOnClick={() => {
              this.props.openModal({
                size: 'medium',
                component: (
                  <SettlementDetail
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
