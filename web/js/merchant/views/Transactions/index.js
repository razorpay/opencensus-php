import React, { Component } from 'react';
import { connect } from 'react-redux';
import { Route, Switch, NavLink } from 'react-router-dom';
import ShowWhen, { ShowWhenRoute } from 'merchant/components/ShowWhen';
import { getMobileOperatingSystem, getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import TestModeBanner from 'merchant/components/TestModeBanner';
import PaymentsList from 'merchant/views/Transactions/Payments/List';
import RefundsList from 'merchant/views/Transactions/Refunds/List';
import OrdersList from 'merchant/views/Transactions/Orders/List';
import DisputesList from 'merchant/views/Transactions/Disputes/List';
import BatchPaymentsList from 'merchant/views/Transactions/BatchPayments/List';
import BatchRefundsList from 'merchant/views/Transactions/BatchRefunds/List';
import BatchRefundsUpload from 'merchant/views/Transactions/BatchRefunds/BatchUpload';
import { fetchSettlementAmount as fnFetchSettlementAmount } from 'merchant/reducers/home';
import { fetchSettlementConfig as fnFetchSettlementConfig } from 'merchant/reducers/settlements/details';
import Amount from 'common/ui/Amount';
import SettlementDetail from 'merchant/views/Settlements/Settlements/components/SettlementDetail';
import { closeModal, openModal } from 'merchant_common/reducers/modals';
import Time from 'common/ui/Time';
import PopoverComponent, { PopoverBody } from 'common/ui/Popover';
import ScheduledNitroBanner from 'merchant/components/ScheduledNitroBanner';
import CatalystCampaignBanner from 'merchant/components/Announcements/CatalystCampaignBanner';
import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';
import { isMobileDevice } from 'merchant/components/Home/data';
import { MobilePopup, UseAppFooter } from 'merchant/components/MobilePopup';
import { getItem } from 'common/utils/localStorage';
import { analyticsTrack } from 'common/utils/analytics';
import { fetchOpen as fnFetchOpenDisputes } from 'merchant/reducers/disputes/details';
import { bindActionCreators } from 'redux';
import EasterEgg from 'merchant/components/EasterEgg';
import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import DashboardBanner from '../../../common/ui/DashboardBanner';

let url = 'https://play.google.com/store/apps/details?id=com.razorpay.payments.app';
if (getMobileOperatingSystem() == 'iOS') {
  url = 'https://apps.apple.com/in/app/razorpay-payments-dashboard/id1497250144';
}

class TransactionsContainer extends Component {
  constructor(props) {
    super(props);
    this.state = {
      showPopup: false,
      showFooter: false,
      url,
    };
  }

  componentDidMount() {
    const { fetchSettlementAmount, fetchOpenDisputes, fetchSettlementConfig } = this.props;
    fetchSettlementAmount();
    fetchOpenDisputes();
    fetchSettlementConfig();

    const mwebPopupLS = !!getItem('transactions_mweb_popup'); // Check if popup is already shown to user once.
    const mwebPopupSS = !!getItem('payment links_mweb_popup'); // Check if popup is shown in session on another scrren.
    let showPopup = isMobileDevice();
    if (mwebPopupLS) {
      showPopup = false;
    } else if (mwebPopupSS) {
      showPopup = false;
    }
    // eslint-disable-next-line react/no-did-mount-set-state
    this.setState({ showPopup });
  }

  closePopup = () => {
    this.setState({ showFooter: true, showPopup: false });
  };

  closeFooter = () => {
    this.setState({ showFooter: false, showPopup: false });
  };

  showMobilePopup = () => {
    this.props.openModal({
      size: 'xlarge',
      component: (
        <MobilePopup
          title="Tracking Payments Is Better in the Mobile App"
          subtitle="Switch to the app for better ways to track payments, issue refunds, and more."
          screen="Transactions"
          url={this.state.url}
          notNowClicked={this.closePopup}
          closeModal={this.props.closeModal}
        />
      ),
      className: 'mobile-app-popup',
    });
  };

  render() {
    const { user, mode, openDisputes, settlementConfig } = this.props;
    const { role, activation_status } = user;

    /* Added a check for if the settlement_amount is present or not otherwile it will be false as default*/
    const nextSettlement = !this.props?.settlement_amount?.data?.next_settlement_time;
    const { no_settlement } = this.props.settlement_amount?.data;

    const isOnTemporaryHold = settlementConfig?.data?.config?.features?.hold?.status;
    const isOnHold = no_settlement?.on_hold;
    const isSettlementOnHold = isOnTemporaryHold || isOnHold;

    const pathname = this.props.location.pathname;

    return (
      <React.Fragment>
        <div className="banner-container">
          <ShowWhen
            additionalCondition={(usr) =>
              usr.isProjectNitroEnabled || usr.isProjectNitroCorporateCard
            }
          >
            <AnnouncementBanner
              title="Exclusive Offer For You"
              canBeClosed={false}
              card_id="nitro-transactions-banner"
            >
              <ScheduledNitroBanner
                fromWhere="transactions"
                url="https://lp.razorpay.com/razorpayxca-pymnts1"
              />
            </AnnouncementBanner>
          </ShowWhen>
          <DashboardBanner />
          <ShowWhen additionalCondition={(usr) => usr.isCatalystCampaignEnabled}>
            <CatalystCampaignBanner productName="Transactions" />
          </ShowWhen>
        </div>
        <tabbed-container>
          {/* To make the header scrollable we just need to add this new class to the header component */}
          <header id="transactions-header" className="scrollable-tab-header">
            <NavLink
              to="/payments"
              onClick={() => {
                analyticsTrack({
                  objectName: 'transactions tab',
                  actionName: 'clicked',
                  screen: 'transactions',
                  properties: {
                    tabName: 'payments',
                    ...getCommonAnalyticsProperties(window.rzp_user),
                  },
                });
              }}
              exact
            >
              Payments
            </NavLink>
            <ShowWhen
              featureEnabled="direct_debit"
              additionalCondition={(usr) => usr.isAllowedView('payments_batch_uploads')}
            >
              <NavLink
                to="/payments/batchuploads"
                onClick={() => {
                  analyticsTrack({
                    objectName: 'transactions tab',
                    actionName: 'clicked',
                    screen: 'transactions',
                    properties: {
                      tabName: 'batch payments',
                      ...getCommonAnalyticsProperties(window.rzp_user),
                    },
                  });
                }}
              >
                Batch Payments
              </NavLink>
            </ShowWhen>
            <ShowWhen additionalCondition={(usr) => usr.isAllowedView('refunds')}>
              <NavLink
                to="/refunds"
                exact
                onClick={() => {
                  analyticsTrack({
                    objectName: 'transactions tab',
                    actionName: 'clicked',
                    screen: 'transactions',
                    properties: {
                      tabName: 'refunds',
                      ...getCommonAnalyticsProperties(window.rzp_user),
                    },
                  });
                }}
              >
                Refunds
              </NavLink>
            </ShowWhen>
            <ShowWhen additionalCondition={(usr) => usr.isAllowedView('refunds_batch_uploads')}>
              <NavLink
                to="/refunds/batchuploads"
                isActive={(match, { pathname: path }) =>
                  path === '/refunds/batchupload' || path === '/refunds/batchuploads'
                }
                onClick={() => {
                  analyticsTrack({
                    objectName: 'transactions tab',
                    actionName: 'clicked',
                    screen: 'transactions',
                    properties: {
                      tabName: 'batch refunds',
                      ...getCommonAnalyticsProperties(window.rzp_user),
                    },
                  });
                }}
              >
                Batch Refunds
              </NavLink>
            </ShowWhen>
            <ShowWhen additionalCondition={(usr) => usr.isAllowedView('orders')}>
              <NavLink
                to="/orders"
                onClick={() => {
                  analyticsTrack({
                    objectName: 'transactions tab',
                    actionName: 'clicked',
                    screen: 'transactions',
                    properties: {
                      tabName: 'orders',
                      ...getCommonAnalyticsProperties(window.rzp_user),
                    },
                  });
                }}
              >
                Orders
              </NavLink>
            </ShowWhen>
            <NavLink
              to="/disputes"
              onClick={() => {
                analyticsTrack({
                  objectName: 'transactions tab',
                  actionName: 'clicked',
                  screen: 'transactions',
                  properties: {
                    tabName: 'disputes',
                    ...getCommonAnalyticsProperties(window.rzp_user),
                  },
                });
              }}
            >
              Disputes{' '}
              {openDisputes !== 0 ? (
                <div class="open-disputes">
                  <span>{openDisputes}</span>
                  <PopoverComponent theme="dark" align="bottom">
                    <PopoverBody>
                      <div>
                        There are {openDisputes} pending disputes. Take action immediately before
                        the deadline
                      </div>
                    </PopoverBody>
                  </PopoverComponent>
                </div>
              ) : (
                ''
              )}
            </NavLink>
            {no_settlement &&
            (pathname === '/payments' || pathname === '/refunds' || pathname === '/orders') &&
            mode === 'live' &&
            this.props.payments &&
            this.props.payments.items.length > 0 ? (
              <div class="text-right settlement-caption">
                {no_settlement.caption}
                {no_settlement.reason && (
                  <span>
                    <i class="i i-info-circle" />
                    <PopoverComponent theme="dark" align="left">
                      <PopoverBody>
                        <div>{no_settlement.reason}</div>
                      </PopoverBody>
                    </PopoverComponent>
                  </span>
                )}
              </div>
            ) : null}
            {!isSettlementOnHold &&
            !no_settlement &&
            !nextSettlement &&
            (pathname === '/payments' || pathname === '/refunds' || pathname === '/orders') ? (
              <div class="text-right full-width no-margin">
                <strong className="pr-5">
                  <Amount
                    value={this.props.settlement_amount.data.settlement_amount}
                    currency="INR"
                  />
                </strong>
                <span className="pr-5">will be settled on</span>
                <Time
                  className="pr-5"
                  value={this.props.settlement_amount.data.next_settlement_time}
                  format="DD MMM YYYY, hh:mm a"
                />
                {this.props.settlement_amount.data.reason_for_delay && (
                  <div style={{ display: 'inline' }}>
                    <i class="i i-info-circle" />
                    <PopoverComponent theme="dark" align="left">
                      <PopoverBody>
                        <div>{this.props.settlement_amount.data.reason_for_delay}</div>
                      </PopoverBody>
                    </PopoverComponent>
                  </div>
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

                    window.rzpAnalytics?.({
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

          <TestModeBanner />

          <content>
            <ErrorBoundary resetOnProps>
              <Switch>
                <Route path="/refunds/batchupload" component={BatchRefundsUpload} />
                <Route path="/refunds/batchuploads" component={BatchRefundsList} />
                <Route path="/refunds" component={RefundsList} />
                <ShowWhenRoute
                  path="/orders"
                  component={OrdersList}
                  additionalCondition={(usr) => usr.isAllowedView('orders')}
                />
                <Route path="/payments/batchuploads/:mode" component={BatchPaymentsList} />
                <Route path="/payments/batchuploads" component={BatchPaymentsList} />
                <Route path="/payments" component={PaymentsList} />
                <Route path="/disputes" component={DisputesList} />
              </Switch>
            </ErrorBoundary>
          </content>

          {this.state.showPopup &&
            activation_status === 'activated' &&
            (role === 'owner' || role === 'admin' || role === 'manager' || role === 'operations') &&
            this.showMobilePopup()}
        </tabbed-container>
        {this.state.showFooter &&
          activation_status === 'activated' &&
          (role === 'owner' || role === 'admin' || role === 'manager' || role === 'operations') && (
            <UseAppFooter
              screen="Transactions"
              url={this.state.url}
              closeFooter={this.closeFooter}
            />
          )}
        <EasterEgg extraClass="ftx-transaction-page" page="Transactions" />
      </React.Fragment>
    );
  }
}

const mapStateToProps = (state) => {
  return {
    ...state.session,
    settlement_amount: state.home.settlement_amount,
    config: state.config.config,
    payments: state.payments,
    openDisputes: state.dispute.openDisputes,
    settlementConfig: state.settlement.config,
  };
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      fetchSettlementAmount: fnFetchSettlementAmount,
      openModal,
      closeModal,
      fetchOpenDisputes: fnFetchOpenDisputes,
      fetchSettlementConfig: fnFetchSettlementConfig,
    },
    dispatch,
  );
};

export default connect(mapStateToProps, mapDispatchToProps)(TransactionsContainer);
