/* eslint-disable consistent-return */
import React from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { withRouter } from 'react-router-dom';
import Pager from 'common/ui/Pager';
import Alert from 'common/ui/Forms/Alert';
import ListContainer from 'merchant/containers/ListContainer';
import SettlementsList from 'merchant/views/Settlements/Settlements/components/List';
import SettlementsListFilter from 'merchant/views/Settlements/Settlements/components/ListFilter';
import SettlementBreakupModal from 'merchant/views/Settlements/Settlements/components/Modals/BreakupModal';
import HeaderAction from 'common/ui/HeaderAction';
import { fetchSettlements as fetchAll } from 'merchant/reducers/collection';
import * as ModalActions from 'merchant_common/reducers/modals';
import TestModeBanner from 'merchant/components/TestModeBanner';
import { getKeysSeparatedByPipe, getFormattedAmountNew } from 'common/utils/rzp-utils';
import RequestEarlyAccessForm from 'merchant/components/Announcements/EarlySettlements/Modal';
import PopoverComponent, { PopoverBody } from 'common/ui/Popover';
import { showNotification } from 'merchant_common/reducers/notifications';
import OnHoldBanner from 'common/ui/OnHoldBanner';
import {
  trackEarlySettlementRequests,
  trackOndemand,
  EVENT_CATEGORY_DASHBOARD_SETTLEMENTS,
  EVENT_CATEGORY_DASHBOARD_EARLY_SETTLEMENT,
} from './ga';
import {
  fetchCurrentBalance as fnFetchCurrentBalance,
  fetchSettlementAmount as fnFetchSettlementAmount,
  fetchBalanceConfig as fnFetchBalanceConfig,
  fetchOndemandRestrictions,
} from 'merchant/reducers/home';
import {
  fetchSchedule as fnFetchSchedule,
  fetchHolidayList as fnFetchHolidayList,
} from 'merchant/reducers/settlements/details';
import OndemandModal from 'merchant/views/Settlements/Settlements/components/Modals/OndemandModal';
import Amount from 'common/ui/Amount';
import ScheduledBanner from 'merchant/views/Settlements/Settlements/components/ScheduledBanner';
import SettlementSchedule from 'merchant/views/Settlements/Settlements/components/SettlementSchedule';
import SettlementDetail from 'merchant/views/Settlements/Settlements/components/SettlementDetail';
import Time from 'common/ui/Time';
import SettlementGuideText from 'merchant_common/components/SettlementGuideText';
import SettleNowButton from 'merchant/views/Settlements/Settlements/components/SettleNowButton';
import { handleAnalytics } from './analytics';

class SettlementsListContainer extends ListContainer {
  state = {
    openAutoModal: false,
  };

  restrictedFeatures = [
    'disable_ondemand_for_loc',
    'disable_ondemand_for_card',
    'disable_ondemand_for_loan',
  ];

  featureName = {
    disable_ondemand_for_loc: 'LOC',
    disable_ondemand_for_card: 'Card',
    disable_ondemand_for_loan: 'Loan',
  };
  get isOnDemandDisabled() {
    const { user } = this.props;

    return this.restrictedFeatures.some((feature) => user.isFeatureEnabled(feature));
  }

  get settlementRestricted() {
    return this.props.user.isFeatureEnabled('es_on_demand_restricted') || this.isOnDemandDisabled;
  }

  get settleNowRestrictionMsg() {
    if (!this.settlementRestricted) return;

    const {
      attempts_left,
      settlable_amount,
      max_amount_limit,
      settlements_count_limit,
    } = this.props.ondemand_restrictions.data;
    if (this.isOnDemandDisabled) {
      const restrictedItem = this.restrictedFeatures
        .filter((feat) => this.props.user.isFeatureEnabled(feat))
        .map((feat) => this.featureName[feat]);

      const renderFeatureComponent = () => {
        return restrictedItem.map((item, i) => {
          if (i === restrictedItem.length - 1 && i != 0) {
            return (
              <>
                & <span className="highlight-tooltip"> {item}.</span>
              </>
            );
          } else {
            return (
              <span className="highlight-tooltip">
                {item}
                {i === restrictedItem.length - 1
                  ? '.'
                  : i === restrictedItem.length - 2
                  ? ' '
                  : ', '}
              </span>
            );
          }
        });
      };
      return (
        <div className="disable-ondemand-msg">
          On-demand Instant Settlements have been disabled because you have delayed the repayments
          on {renderFeatureComponent()}
          <br /> <br />
          Please complete the repayments to re-enable Instant Settlements.
        </div>
      );
    } else if (!attempts_left && !settlable_amount) {
      return `You’ve already settled your maximum allowed limit of ${getFormattedAmountNew(
        max_amount_limit,
        true,
      )} for the day.`;
    } else if (!attempts_left) {
      return `You've already settled your maximum allowed limit of ${settlements_count_limit} times for the day.`;
    } else if (!settlable_amount) {
      return `You’ve already settled your maximum allowed limit of ${getFormattedAmountNew(
        max_amount_limit,
        true,
      )} for the day.`;
      /* eslint-disable */
      } else return;
      /* eslint-enable */
  }

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
      const balance = this.props.current_balance.data.balance || 0;
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
    const {
      fetchCurrentBalance,
      fetchSchedule,
      fetchBalanceConfig,
      fetchSettlementAmount,
      fetchHolidayList,
      location,
    } = this.props;

    window.rzpAnalytics({
      eventCategory: EVENT_CATEGORY_DASHBOARD_SETTLEMENTS,
      eventAction: 'Go To - Settlements',
    });

    window.addEventListener('remove-req-es-button', this.removeRequestESButton, false);

    fetchCurrentBalance();
    fetchSchedule();
    fetchBalanceConfig();

    if (location.hash === '#requestearlyaccess') {
      this.showRequestEarySettlementForm();
    }

    this.popupIfSettle();
    fetchSettlementAmount();
    fetchHolidayList();
    this.fetchRestrictionsIfAny();
  }

  fetchRestrictionsIfAny = () => {
    if (this.settlementRestricted) {
      this.props.fetchOndemandRestrictions();
    }
  };

  onSearchAnalytics = (params) => {
    const label = getKeysSeparatedByPipe(params);
    if (label && label.length > 0) {
      window.rzpAnalytics({
        eventCategory: EVENT_CATEGORY_DASHBOARD_SETTLEMENTS,
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
      eventCategory: EVENT_CATEGORY_DASHBOARD_SETTLEMENTS,
      eventAction: 'Clear Search Params - Settlements',
    });

    handleAnalytics('clear', 'clicked');
  };

  settlementBreakupOnMount = (id) => {
    window.rzpAnalytics({
      eventCategory: EVENT_CATEGORY_DASHBOARD_SETTLEMENTS,
      eventAction: 'Show - Settlement Breakup',
      eventLabel: `settlement_id=${id}`,
    });
  };

  settlementBreakupOnUnmount = (id) => {
    window.rzpAnalytics({
      eventCategory: EVENT_CATEGORY_DASHBOARD_SETTLEMENTS,
      eventAction: 'Hide - Settlement Breakup',
      eventLabel: `settlement_id=${id}`,
    });
  };

  showBreakup = (settlement) => {
    this.props.openModal({
      component: (
        <SettlementBreakupModal
          settlementId={settlement.id}
          onMount={this.settlementBreakupOnMount}
          onUnmount={this.settlementBreakupOnUnmount}
          settlement={settlement}
        />
      ),
      size: 'large',
    });
    const properties = {
      ...settlement.analyticsPayload(),
    };
    handleAnalytics('break up', 'clicked', properties);
  };

  removeRequestESButton = () => {
    window.removeEventListener('remove-req-es-button', this.removeRequestESButton, false);
  };

  showRequestEarySettlementForm = () => {
    trackEarlySettlementRequests();

    this.props.openModal({
      component: <RequestEarlyAccessForm />,
      size: 'large',
    });
  };

  showOndemandSettlementForm = (e) => {
    trackOndemand.trackSettleNow('Settlements');
    const {
      current_balance,
      ondemand_restrictions,
      openModal,
      checkIfFirstEverSettlement,
      settlementExists,
      esOndemandSettlementEnabled,
    } = this.props;

    const balance = current_balance.data.balance;
    const settlableAmount =
      this.settlementRestricted &&
      ondemand_restrictions &&
      ondemand_restrictions.data.settlable_amount;

    openModal({
      component: (
        <OndemandModal
          animatedSettlemnetBtn={!settlementExists && esOndemandSettlementEnabled}
          settlableAmount={settlableAmount}
          currentBalance={balance}
          fromWhere={e.clickOrigin ? 'Announcement' : 'Settlements'}
          goBackToInitialModalView={this.showOndemandSettlementForm}
          eventCategory={EVENT_CATEGORY_DASHBOARD_EARLY_SETTLEMENT}
          checkIfFirstEverSettlement={checkIfFirstEverSettlement}
        />
      ),
      size: 'small',
      disableClose: true,
    });
  };

  viewSettlementCycle = () => {
    this.props.openModal({
      size: 'medium',
      component: <SettlementSchedule holidayList={this.props.holidayList} location="settlements" />,
    });

    window.rzpAnalytics({
      eventCategory: 'Settlement Revamp',
      eventAction: 'View Settlement Cycle',
      eventLabel: `Settlements`,
    });
    handleAnalytics('settlement cycle viewed', 'clicked');
  };

  handleSearch = (args) => {
    const objectName = 'settlements search';
    const searchProps = {
      searchTerm: args.id,
      count: args.count,
    };
    handleAnalytics(objectName, 'clicked', searchProps);
    this.search(args)
      .then(({ data }) => {
        const resultProps = {
          searchTerm: args.id,
          resultsReturned: true,
          numberofResults: data.items.length,
          status: 'success',
        };
        handleAnalytics(objectName, 'result', resultProps);
      })
      .catch((e) => {
        const failureProps = {
          searchTerm: args.id,
          resultsReturned: false,
          status: 'failure',
          failureReason: e.errors[0],
        };
        handleAnalytics(objectName, 'result', failureProps);
      });
  };

  handlePagination = (params, type) => {
    const properties = {
      paginationType: type === 'prev' ? 'previous' : type,
    };
    this.paginate(params, type);
    handleAnalytics('pagination', 'clicked', properties);
  };
  render() {
    const {
      loading,
      items,
      error,
      current_balance,
      user,
      mode,
      ondemand_restrictions,
      settlementExists,
      esOndemandSettlementEnabled,
      checkIfFirstEverSettlement,
    } = this.props;

    const attemptsLeft =
      this.settlementRestricted &&
      ondemand_restrictions &&
      ondemand_restrictions.data.attempts_left;
    const isOndemandRestrictionsLoading =
      this.settlementRestricted && ondemand_restrictions && ondemand_restrictions.loading;
    const settlableAmount =
      this.settlementRestricted &&
      ondemand_restrictions &&
      ondemand_restrictions.data.settlable_amount;
    const isSettleNowRestricted =
      this.settlementRestricted &&
      (!attemptsLeft || !settlableAmount || isOndemandRestrictionsLoading);

    let balance = current_balance.data.balance || 0;
    let currentBalanceClassName = 'amount-current-balance';

    if (balance < 0) {
      balance = Math.abs(balance);
      currentBalanceClassName += ' negative-balance';
    }

    const nextSettlement = this.props?.settlement_amount?.data?.next_settlement_time;
    const checkIfSettlementDisabled =
      isSettleNowRestricted || current_balance.loading || balance < 100 || this.isOnDemandDisabled;

    const { no_settlement } = this.props.settlement_amount.data;

    return (
      <React.Fragment>
        {/* banner */}
        <TestModeBanner />
        {mode === 'live' &&
        nextSettlement === null &&
        no_settlement &&
        no_settlement.on_hold === true ? (
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
                eventLabel: `Settlements`,
              });
            }}
          />
        ) : null}
        <content>
          <div class="content-wrapper">
            <HeaderAction>
              <div class="settlement-actions-wrapper">
                {
                  <div class="btn btn-link settlement-doc-btn" onClick={this.viewSettlementCycle}>
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
                  !this.settlementRestricted &&
                  this.props.user.isAllowedView('early_settlement') && (
                    <div class="box-left-pad10-inline">
                      <ScheduledBanner
                        onExit={() => {
                          this.setState({ openAutoModal: false });
                        }}
                        openAutoModal={this.state.openAutoModal}
                        fromWhere={this.state.openAutoModal ? 'Announcement' : 'Settlements'}
                        eventCategory={EVENT_CATEGORY_DASHBOARD_EARLY_SETTLEMENT}
                      />
                    </div>
                  )}
              </div>
            </HeaderAction>

            <div class="pull-right">
              {this.props.user.isOrgAllowedFunctionality('current_balance') && (
                <div class="flex text-right settlement-balance-amount p-t p-b-15">
                  <div>
                    <div>
                      <strong>
                        Current Balance:{' '}
                        <Amount value={balance} currency="INR" class={currentBalanceClassName} />
                      </strong>
                      {this.props.user.isAutomaticSettlementEnabled && (
                        <i class="i i-early-settlement settle-current-icon">
                          <PopoverComponent align="left" theme="dark">
                            <PopoverBody>
                              <span>Early Settlment has been enabled with your account.</span>
                            </PopoverBody>
                          </PopoverComponent>
                        </i>
                      )}
                    </div>
                    <div>
                      {nextSettlement && !no_settlement && (
                        <span style={{ fontSize: '13px' }}>
                          <span>&nbsp;</span>
                          <strong>
                            <Amount
                              value={this.props.settlement_amount.data.settlement_amount}
                              currency="INR"
                              class="amount-settlement"
                            />
                          </strong>
                          will be settled on{' '}
                          <Time
                            value={this.props.settlement_amount.data.next_settlement_time}
                            format="DD MMM YYYY, hh:mm:ss a"
                          />
                          {this.props.settlement_amount.data.reason_for_delay && (
                            <>
                              <i class="i i-info-circle" />
                              <PopoverComponent theme="dark" align="left">
                                <PopoverBody>
                                  <div>{this.props.settlement_amount.data.reason_for_delay}</div>
                                </PopoverBody>
                              </PopoverComponent>
                            </>
                          )}
                          <span
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
                  {this.props.user.isOndemandSettlementEnabled &&
                    this.props.user.isAllowedView('early_settlement') && (
                      <div className="box-left-pad10-inline">
                        <SettleNowButton
                          disabled={checkIfSettlementDisabled}
                          merchantId={user.current}
                          fromWhere="Settlements"
                          settlementExists={settlementExists}
                          esOndemandSettlementEnabled={esOndemandSettlementEnabled}
                          showOndemandSettlementForm={this.showOndemandSettlementForm}
                          checkIfFirstEverSettlement={checkIfFirstEverSettlement}
                        />
                        {this.settleNowRestrictionMsg && (
                          <PopoverComponent
                            align="top"
                            parentQuerySelector=".settle-btn .settle-now--list"
                            theme="dark"
                          >
                            <PopoverBody>{this.settleNowRestrictionMsg}</PopoverBody>
                          </PopoverComponent>
                        )}
                      </div>
                    )}
                  <br />
                  {mode === 'live' &&
                  no_settlement &&
                  this.props.payments &&
                  this.props.payments.items.length > 0 ? (
                    <span style={{ fontSize: '13px' }}>
                      {no_settlement.caption}
                      {no_settlement.reason && (
                        <>
                          <i class="i i-info-circle" />
                          <PopoverComponent theme="dark" align="left">
                            <PopoverBody>
                              <div>{no_settlement.reason}</div>
                            </PopoverBody>
                          </PopoverComponent>
                        </>
                      )}
                    </span>
                  ) : null}
                </div>
              )}
            </div>

            <SettlementsListFilter
              form="settlementsListFilter"
              count={this.state.count}
              onSubmit={this.handleSearch}
              onSearchAnalytics={this.onSearchAnalytics}
              onClearAnalytics={this.onClearAnalytics}
            />

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
              onClick={this.handlePagination}
            />

            <SettlementGuideText />
          </div>
        </content>
      </React.Fragment>
    );
  }
}

const mapStateToProps = (state) => {
  return {
    user: state.session.user,
    mode: state.session.mode,
    schedule: state.settlement.schedule,
    settlement_amount: state.home.settlement_amount,
    holidayList: state.settlement.holidayList,
    config: state.config.config,
    payments: state.payments,
    ...state.home,
    ...state.settlements,
  };
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      fetchAll,
      showNotification,
      ...ModalActions,
      fetchCurrentBalance: fnFetchCurrentBalance,
      fetchSchedule: fnFetchSchedule,
      fetchSettlementAmount: fnFetchSettlementAmount,
      fetchHolidayList: fnFetchHolidayList,
      fetchBalanceConfig: fnFetchBalanceConfig,
      fetchOndemandRestrictions,
    },
    dispatch,
  );
};

export default withRouter(connect(mapStateToProps, mapDispatchToProps)(SettlementsListContainer));
