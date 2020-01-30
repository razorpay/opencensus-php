import React, { Fragment } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router';
import { NavLink, Link } from 'react-router-dom';
import Pager from 'common/ui/Pager';
import Alert from 'common/ui/Forms/Alert';
import ListContainer from 'merchant/containers/ListContainer';
import SettlementsList from 'merchant/views/Settlements/components/List';
import SettlementsListFilter from 'merchant/views/Settlements/components/ListFilter';
import SettlementBreakupModal from 'merchant/views/Settlements/components/Modals/BreakupModal';
import HeaderAction from 'common/ui/HeaderAction';
import { fetchSettlements as fetchAll } from 'merchant/reducers/collection';
import * as ModalActions from 'merchant_common/reducers/modals';
import TestModeBanner from 'merchant/containers/TestModeBanner';
import EnableSettlementsBanner from 'merchant/components/EnableSettlementsBanner';
import { getKeysSeparatedByPipe } from 'common/utils/rzp-utils';
import EarlySettlementsAnnouncement from 'merchant/components/Announcements/EarlySettlements';
import RequestEarlyAccessForm from 'merchant/components/Announcements/EarlySettlements/Modal';
import Popover, { PopoverBody } from 'common/ui/Popover';
import { showNotification } from 'merchant_common/reducers/notifications';
import OnHoldBanner from 'common/ui/OnHoldBanner';
import { trackEarlySettlementRequests, trackOndemand } from './ga';
import {
  fetchCurrentBalance,
  fetchSettlementAmount,
} from 'merchant/reducers/home';
import {
  fetchSchedule,
  fetchHolidayList,
} from 'merchant/reducers/settlements/details';
import OndemandModal from 'merchant/views/Settlements/components/Modals/OndemandModal';
import NegativeBalanceBanner from 'merchant/components/Announcement';
import Amount from 'common/ui/Amount';
import Button from 'common/new-ui/Button';
import ScheduledBanner from 'merchant/views/Settlements/components/ScheduledBanner';
import SettlementSchedule from 'merchant/views/Settlements/components/SettlementSchedule';
import SettlementDetail from 'merchant/views/Settlements/components/SettlementDetail';
import Time from 'common/ui/Time';
import { fetchBalanceConfig } from 'merchant/reducers/home';
import { handleNegativeBalanceLimit } from 'common/utils/rzp-utils';

@withRouter
@connect(
  state => ({
    user: state.session.user,
    mode: state.session.mode,
    schedule: state.settlement.schedule,
    settlement_amount: state.home.settlement_amount,
    holidayList: state.settlement.holidayList,
    config: state.config.config,
    ...state.home,
    ...state.settlements,
  }),
  {
    fetchAll,
    showNotification,
    ...ModalActions,
    fetchCurrentBalance,
    fetchSchedule,
    fetchSettlementAmount,
    fetchHolidayList,
    fetchBalanceConfig,
  }
)
export default class SettlementsListContainer extends ListContainer {
  state = {
    openAutoModal: false,
  };

  componentWillReceiveProps(nextProps) {
    if (
      nextProps.location.hash !== this.props.location.hash &&
      nextProps.location.hash === '#requestearlyaccess'
    ) {
      this.showRequestEarySettlementForm();
    }
  }

  componentDidUpdate() {
    this.popupIfSettle();
  }

  popupIfSettle() {
    if (this.props.location.hash === '#settlenow') {
      this.resetHash();
      let balance = this.props.current_balance.data.balance || 0;
      if (balance < 100) {
        this.props.showNotification({
          type: 'error',
          message: 'Current balance is not sufficient for instant settlement',
          hidePrevious: true,
        });
        return;
      }
      this.showOndemandSettlementForm({ clickOrigin: 'Announcement' });
    } else if (this.props.location.hash === '#automaticsettle') {
      this.resetHash();
      this.setState({ openAutoModal: true });
    }
  }

  componentDidMount() {
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Settlements',
      eventAction: 'Go To - Settlements',
    });

    window.addEventListener(
      'remove-req-es-button',
      this.removeRequestESButton,
      false
    );

    this.props.fetchCurrentBalance();
    this.props.fetchSchedule();
    this.props.fetchBalanceConfig();

    if (this.props.location.hash === '#requestearlyaccess') {
      this.showRequestEarySettlementForm();
    }

    this.popupIfSettle();
    this.props.fetchSettlementAmount();
    this.props.fetchHolidayList();
  }

  onSearchAnalytics = params => {
    const label = getKeysSeparatedByPipe(params);
    if (label && label.length > 0) {
      window.rzpAnalytics({
        eventCategory: 'Dashboard - Settlements',
        eventAction: 'Search - Settlements',
        eventLabel: label,
      });
    }
  };

  resetHash = () => {
    this.props.history.push({
      pathname: this.props.history.location.pathname,
      hash: '',
    });
  };

  onClearAnalytics = () => {
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Settlements',
      eventAction: 'Clear Search Params - Settlements',
    });
  };

  settlementBreakupOnMount = id => {
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Settlements',
      eventAction: 'Show - Settlement Breakup',
      eventLabel: `settlement_id=${id}`,
    });
  };

  settlementBreakupOnUnmount = id => {
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Settlements',
      eventAction: 'Hide - Settlement Breakup',
      eventLabel: `settlement_id=${id}`,
    });
  };

  showBreakup = settlement => {
    this.props.openModal({
      component: (
        <SettlementBreakupModal
          settlementId={settlement.id}
          onMount={this.settlementBreakupOnMount}
          onUnmount={this.settlementBreakupOnUnmount}
        />
      ),
    });
  };

  removeRequestESButton = () => {
    window.removeEventListener(
      'remove-req-es-button',
      this.removeRequestESButton,
      false
    );
  };

  showRequestEarySettlementForm = () => {
    trackEarlySettlementRequests();

    this.props.openModal({
      component: <RequestEarlyAccessForm />,
      size: 'large',
    });
  };

  showOndemandSettlementForm = e => {
    trackOndemand.trackSettleNow('Settlements');
    let balance = this.props.current_balance.data.balance;

    this.props.openModal({
      component: (
        <OndemandModal
          currentBalance={balance}
          fromWhere={e.clickOrigin ? 'Announcement' : 'Settlements'}
        />
      ),
      size: 'small',
      disableClose: true,
    });
  };

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
    let { loading, items, error, current_balance, user, mode } = this.props,
      { showInstantActivation, isSubmitted } = user;
    let balance = current_balance.data.balance || 0;
    let negativeBalanceClassName = '';

    if (balance < 0) {
      balance = Math.abs(balance);
      negativeBalanceClassName = 'negative-balance';
    }

    const nextSettlement = this.props.settlement_amount.data
      .next_settlement_time;

    const { no_settlement } = this.props.settlement_amount.data;

    const { settlement_ux_revamp } = this.props.config;

    return (
      <React.Fragment>
        {/* instant settlements banner */}
        {user.isISBannerEnabled && (
          <EarlySettlementsAnnouncement userId={user.current} />
        )}

        {current_balance.data.balance < 0 && (
          <NegativeBalanceBanner
            title="Add Funds"
            theme="warning"
            canBeClosed={true}
          >
            Your balance went into negative value. Add funds to avoid the
            transaction failures.{' '}
            <Link to={'/addfunds'} target="_blank">
              {' '}
              Add Funds
            </Link>
          </NegativeBalanceBanner>
        )}

        {handleNegativeBalanceLimit(
          this.props.merchantBalanceConfigs,
          this.props.current_balance.data.balance
        ) && (
          <NegativeBalanceBanner
            title="On Hold!"
            theme="danger"
            canBeClosed={true}
          >
            Your current balance had reached the maximum negative limit.
            Transactions will start to fail now. Please add funds to avoid
            transaction failures.{' '}
            <Link to={'/addfunds'} target="_blank">
              {' '}
              Add Funds
            </Link>
          </NegativeBalanceBanner>
        )}

        <tabbed-container
          style={{ paddingTop: user.isISBannerEnabled ? '0px' : '20px' }}
        >
          <header>
            <NavLink to="/settlements">Settlements</NavLink>
          </header>

          {showInstantActivation && mode === 'live' && !isSubmitted ? (
            <EnableSettlementsBanner />
          ) : (
            <TestModeBanner />
          )}
          {nextSettlement === null &&
          no_settlement &&
          no_settlement.on_hold === true ? (
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
                  eventLabel: `Settlements`,
                });
              }}
            />
          ) : null}
          <content>
            <div class="content-wrapper">
              <HeaderAction>
                <React.Fragment>
                  {
                    <div
                      class="btn btn-link settlement-doc-btn"
                      onClick={this.viewSettlementCycle}
                    >
                      <span
                        class="icon i-info-outline"
                        style={{
                          marginRight: '5px',
                          position: 'relative',
                          top: '2px',
                        }}
                      />
                      View Settlement Cycle
                    </div>
                  }
                  {this.props.user.isOndemandSettlementEnabled &&
                    this.props.user.isAllowedView('early_settlement') && (
                      <div className="box-left-pad10-inline">
                        <ScheduledBanner
                          onExit={() => {
                            this.setState({ openAutoModal: false });
                          }}
                          openAutoModal={this.state.openAutoModal}
                          fromWhere={
                            this.state.openAutoModal
                              ? 'Announcement'
                              : 'Settlements'
                          }
                        />
                      </div>
                    )}
                </React.Fragment>
              </HeaderAction>
              <SettlementsListFilter
                form="settlementsListFilter"
                additionalClass="settle-list-filter"
                count={this.state.count}
                onSubmit={this.search}
                onSearchAnalytics={this.onSearchAnalytics}
                onClearAnalytics={this.onClearAnalytics}
              />

              <div className="pull-right">
                {this.props.user.isOrgAllowedFunctionality(
                  'current_balance'
                ) && (
                  <Fragment>
                    <div class="flex text-right">
                      <div>
                        <span class="settlement-balance-amount">
                          Current Balance:{' '}
                          <Amount
                            value={balance}
                            currency={'INR'}
                            className={negativeBalanceClassName}
                          />
                          {this.props.user.isOndemandSettlementEnabled &&
                            this.props.user.isAllowedView(
                              'early_settlement'
                            ) && (
                              <div className="box-left-pad10-inline">
                                <Button.Primary
                                  class="settle-btn"
                                  onClick={this.showOndemandSettlementForm}
                                  disabled={
                                    current_balance.loading || balance < 100
                                  }
                                >
                                  <i className="i i-early-settlement settle-now-early" />
                                  Settle Now
                                </Button.Primary>
                              </div>
                            )}
                          {this.props.user.isAutomaticSettlementEnabled && (
                            <>
                              <i className="i i-early-settlement settle-current-icon">
                                <Popover align="left" theme="dark">
                                  <PopoverBody>
                                    <span>
                                      Early Settlment has been enabled with your
                                      account.
                                    </span>
                                  </PopoverBody>
                                </Popover>
                              </i>
                            </>
                          )}
                        </span>
                        <br />
                        {no_settlement && (
                          <span style={{ fontSize: '13px' }}>
                            {no_settlement.caption}
                            {no_settlement.reason && (
                              <>
                                <i class="i i-info-circle" />
                                <Popover theme="dark" align="left">
                                  <PopoverBody>
                                    <div>{no_settlement.reason}</div>
                                  </PopoverBody>
                                </Popover>
                              </>
                            )}
                          </span>
                        )}
                        {nextSettlement &&
                          !no_settlement && (
                            <span style={{ fontSize: '13px' }}>
                              <span>&nbsp;</span>
                              <strong>
                                <Amount
                                  value={
                                    this.props.settlement_amount.data
                                      .settlement_amount
                                  }
                                  currency={'INR'}
                                />
                              </strong>{' '}
                              will be settled on{' '}
                              <Time
                                value={
                                  this.props.settlement_amount.data
                                    .next_settlement_time
                                }
                                format={'DD MMM YYYY, hh:mm:ss a'}
                              />
                              {this.props.settlement_amount.data
                                .reason_for_delay && (
                                <>
                                  <i class="i i-info-circle" />
                                  <Popover theme="dark" align="left">
                                    <PopoverBody>
                                      <div>
                                        {
                                          this.props.settlement_amount.data
                                            .reason_for_delay
                                        }
                                      </div>
                                    </PopoverBody>
                                  </Popover>
                                </>
                              )}
                              <span
                                onClick={() => {
                                  this.props.openModal({
                                    size: 'medium',
                                    component: (
                                      <SettlementDetail
                                        settlementAmount={
                                          this.props.settlement_amount.data
                                        }
                                      />
                                    ),
                                  });

                                  window.rzpAnalytics({
                                    eventCategory: 'Settlement Revamp',
                                    eventAction: 'Know more - Next Settlement',
                                    eventLabel: `Settlements`,
                                  });
                                }}
                                class="btn-link pointer"
                                style={{ marginLeft: '5px' }}
                              >
                                <b>Know More</b>
                              </span>
                            </span>
                          )}
                      </div>
                    </div>
                  </Fragment>
                )}
              </div>

              <div class="clearfix" />

              {error && <Alert type="error" message={error} />}

              <SettlementsList
                settlements={items}
                isLoading={loading}
                showBreakup={this.showBreakup}
              />

              <Pager
                count={this.state.count}
                skip={this.state.skip}
                length={items.length}
                onClick={this.paginate}
              />

              <div class="settlement-row">
                <div class="col-md-6 col-md-offset-3 col-sm-12 text-center">
                  <div>
                    The amount that gets settled to your bank account will show
                    up here.
                  </div>
                  <div>
                    {user.isOrgAllowedFunctionality('external_links') ? (
                      <a
                        class="btn-link"
                        target="_blank"
                        href="http://razorpay.com/settlement"
                      >
                        See our Settlements Guide
                      </a>
                    ) : (
                      'See the Settlements Guide'
                    )}{' '}
                    to understand how it works.
                  </div>
                </div>
              </div>
            </div>
          </content>
        </tabbed-container>
      </React.Fragment>
    );
  }
}
