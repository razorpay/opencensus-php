import React, { Component } from 'react';
import { connect } from 'react-redux';
import { NavLink, Outlet } from 'react-router-dom';
import { bindActionCreators } from 'redux';
import { withRouter } from 'common/deprecated/withRouter';
import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import { withSplitzService } from 'common/splitz';
import Amount from 'common/ui/Amount';
import DashboardBanner from 'common/ui/DashboardBanner';
import PopoverComponent, { PopoverBody } from 'common/ui/Popover';
import Time from 'common/ui/Time';
import { analyticsTrack } from 'common/utils/analytics';
import { getItem } from 'common/utils/localStorage';
import { getMobileOperatingSystem, getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';
import EasterEgg from 'merchant/components/EasterEgg';
import { isMobileDevice } from 'merchant/components/Home/data';
import { MobilePopup, UseAppFooter } from 'merchant/components/MobilePopup';
import ScheduledNitroBanner from 'merchant/components/ScheduledNitroBanner';
import ShowWhen from 'merchant/components/ShowWhen';
import TestModeBanner from 'merchant/components/TestModeBanner';
import { fetchOpen as fnFetchOpenDisputes } from 'merchant/reducers/disputes/details';
import { fetchSettlementAmount as fnFetchSettlementAmount } from 'merchant/reducers/home';
import { fetchTerminalProviders } from 'merchant/reducers/navigator/details';
import { fetchSettlementConfig as fnFetchSettlementConfig } from 'merchant/reducers/settlements/details';
import SettlementDetail from 'merchant/views/Settlements/Settlements/components/SettlementDetail';
import { withI18Service } from 'common/i18';

import { closeModal, openModal } from 'merchant_common/reducers/modals';

import { trackSuccessRateEvents, visitSuccessRate } from './SuccessRate/trackEvents';

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
    const {
      fetchSettlementAmount,
      fetchOpenDisputes,
      fetchSettlementConfig,
      fetchProviders,
      user,
    } = this.props;
    if (user?.isSingleReconEnabled && user?.isOptimizerEnabled) {
      fetchProviders();
    }
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
    const {
      user,
      mode,
      openDisputes,
      settlementConfig,
      splitz,
      i18: { isConfigTagEnabled },
    } = this.props;
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
          <ShowWhen additionalCondition={(usr) => usr.isProjectNitroEnabled}>
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
              end
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
            <ShowWhen
              additionalCondition={(usr) =>
                usr.isAllowedView('refunds') && !isConfigTagEnabled('refunds.refund')
              }
            >
              <NavLink
                to="/refunds"
                end
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
            <ShowWhen
              additionalCondition={(usr) =>
                usr.isAllowedView('refunds_batch_uploads') && !isConfigTagEnabled('refunds.refund')
              }
            >
              <NavLink
                to="/refunds/batchuploads"
                className={
                  ['/refunds/batchupload', '/refunds/batchuploads'].includes(pathname)
                    ? 'active'
                    : ''
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
            <ShowWhen additionalCondition={() => !isConfigTagEnabled('disputes.disputes')}>
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
                Disputes&nbsp;
                {openDisputes !== 0 ? (
                  <div className="open-disputes">
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
                ) : null}
              </NavLink>
            </ShowWhen>
            <ShowWhen
              additionalCondition={(currentUser) =>
                mode === 'live' &&
                currentUser.isSuccessRateEnabled &&
                currentUser.isAllowedView('success_rate')
              }
            >
              <NavLink
                to="/success-rate"
                onClick={() => {
                  trackSuccessRateEvents(
                    visitSuccessRate({
                      tabName: 'success rate',
                    }),
                    splitz,
                  );
                }}
              >
                Success Rate
              </NavLink>
            </ShowWhen>
            <ShowWhen
              additionalCondition={(user) =>
                user.international && user.isAllowedView('b2b_payments')
              }
            >
              <NavLink
                to="/payments/b2b-exports"
                onClick={() => {
                  analyticsTrack({
                    objectName: 'transactions tab',
                    actionName: 'clicked',
                    screen: 'transactions',
                    properties: {
                      tabName: 'b2b payments',
                      ...getCommonAnalyticsProperties(window.rzp_user),
                    },
                  });
                }}
              >
                Upload Invoices
              </NavLink>
            </ShowWhen>
            <ShowWhen
              featureEnabled="opgsp_import_flow"
              additionalCondition={(usr) => usr.isAllowedView('b2b_payments')}
            >
              <NavLink
                to="/payments/invoices"
                onClick={() => {
                  analyticsTrack({
                    objectName: 'transactions tab',
                    actionName: 'clicked',
                    screen: 'transactions',
                    properties: {
                      tabName: 'opgsp upload invoice',
                      ...getCommonAnalyticsProperties(window.rzp_user),
                    },
                  });
                }}
              >
                Invoices
              </NavLink>
            </ShowWhen>
            {no_settlement &&
            (pathname === '/payments' || pathname === '/refunds' || pathname === '/orders') &&
            mode === 'live' &&
            this.props.payments &&
            this.props.payments.items.length > 0 ? (
              <div className="text-right settlement-caption">
                {no_settlement.caption}
                {no_settlement.reason && (
                  <span>
                    <i className="i i-info-circle" />
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
              <div className="inline-block text-right full-width no-margin">
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
                    <i className="i i-info-circle" />
                    <PopoverComponent theme="dark" align="left">
                      <PopoverBody>
                        <div>{this.props.settlement_amount.data.reason_for_delay}</div>
                      </PopoverBody>
                    </PopoverComponent>
                  </div>
                )}
                <span
                  className="btn-link"
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
              <Outlet />
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
      fetchProviders: fetchTerminalProviders,
    },
    dispatch,
  );
};

export default withSplitzService(
  withRouter(connect(mapStateToProps, mapDispatchToProps)(withI18Service(TransactionsContainer))),
);
