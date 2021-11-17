/* eslint-disable */
import React from 'react';
import { connect } from 'react-redux';
import { withRouter, Redirect } from 'react-router-dom';
import ListContainer from 'merchant/containers/ListContainer';
import { fetchCurrentBalance, fetchOndemandRestrictions } from 'merchant/reducers/home';
import { fetchHolidayList } from 'merchant/reducers/settlements/details';
import { fetchInstantSettlements as fetchAll } from 'merchant/reducers/collection';
import { showNotification } from 'merchant_common/reducers/notifications';
import * as ModalActions from 'merchant_common/reducers/modals';
import Pager from 'common/ui/Pager';
import InstantSettlementsList from 'merchant/views/Settlements/InstantSettlements/InstantSettlements/List';
import SettlementGuideText from 'merchant_common/components/SettlementGuideText';
import OndemandModal from 'merchant/views/Settlements/Settlements/components/Modals/OndemandModal';
import PlaceholderLoader from 'common/ui/PlaceholderLoader';
import Alert from 'common/ui/Forms/Alert';
import HeaderAction from 'common/ui/HeaderAction';
import SettlementSchedule from 'merchant/views/Settlements/Settlements/components/SettlementSchedule';
import ScheduledBanner from 'merchant/views/Settlements/Settlements/components/ScheduledBanner';
import EmptySettleNow from 'merchant/views/Settlements/InstantSettlements/InstantSettlements/EmptySettleNow';
import CurrentBalance from 'merchant/views/Settlements/InstantSettlements/InstantSettlements/CurrentBalance';
import SettlementMessage from 'merchant/views/Settlements/InstantSettlements/InstantSettlements/SettlementMessage';
import InstantSettlementListFilter from 'merchant/views/Settlements/InstantSettlements/InstantSettlements/ListFilter';
import trackIS, {
  EVENT_CATEGORY_DASHBOARD_INSTANT_SETTLEMENT,
} from 'merchant/views/Settlements/InstantSettlements/ga';
import { getFormattedAmountNew } from 'common/utils/rzp-utils';
import { trackOnDemandSearchClick } from '../../trackEvents';
import { bindActionCreators } from 'redux';

class InstantSettlements extends ListContainer {
  state = {
    count: 25,
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

    const { attempts_left, settlable_amount, max_amount_limit, settlements_count_limit } =
      this.props.ondemand_restrictions.data;
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
    }
    if (!attempts_left && !settlable_amount) {
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

  componentDidMount() {
    this.props.fetchCurrentBalance();
    this.props.fetchHolidayList();
    this.fetchRestrictionsIfAny();
  }

  fetchRestrictionsIfAny = () => {
    if (this.settlementRestricted) {
      this.props.fetchOndemandRestrictions();
    }
  };

  onSearchAnalytics(params) {
    trackIS.clickCTAISSearch();
    trackOnDemandSearchClick(params);
  }

  onClearAnalytics() {
    trackIS.clickCTAISClear();
  }

  showOndemandSettlementForm = () => {
    const {
      current_balance,
      ondemand_restrictions,
      openModal,
      checkIfFirstEverSettlement,
      settlementExists,
      esOndemandSettlementEnabled,
    } = this.props;

    const balance = current_balance.data.balance || 0;
    const settlableAmount =
      this.settlementRestricted &&
      ondemand_restrictions &&
      ondemand_restrictions.data.settlable_amount;

    openModal({
      component: (
        <OndemandModal
          animatedSettlemnetBtn={!settlementExists && esOndemandSettlementEnabled}
          currentBalance={balance}
          settlableAmount={settlableAmount}
          fromWhere="Instant Settlements"
          goBackToInitialModalView={this.showOndemandSettlementForm}
          eventCategory={EVENT_CATEGORY_DASHBOARD_INSTANT_SETTLEMENT}
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
      component: <SettlementSchedule holidayList={this.props.holidayList} />,
    });

    window.rzpAnalytics({
      eventCategory: 'Settlement Revamp',
      eventAction: 'View Settlement Cycle',
      eventLabel: 'Settlements',
    });
  };

  render() {
    const {
      user,
      current_balance,
      holidayList,
      openModal,
      loading,
      items,
      error,
      ondemand_restrictions,
      location: { search },
      settlementExists,
      checkIfFirstEverSettlement,
    } = this.props;
    const { count } = this.state;
    const balance = current_balance.data.balance || 0;
    const updatedAt = current_balance.data.updated_at;

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

    let renderSettlementView = (
      <EmptySettleNow showOndemandSettlementForm={this.showOndemandSettlementForm} />
    );

    let renderSettlementFilterView = null;

    if ((items && items.length > 0) || search) {
      renderSettlementFilterView = (
        <InstantSettlementListFilter
          form="instantSettlementListFilter"
          count={count}
          onSubmit={this.search}
          onSearchAnalytics={this.onSearchAnalytics}
          onClearAnalytics={this.onClearAnalytics}
        />
      );
      renderSettlementView = (
        <>
          {error && <Alert type="error" message={error} />}
          <InstantSettlementsList settlements={items} isLoading={loading} />

          <Pager
            count={count}
            length={items.length}
            skip={this.state.skip}
            onClick={this.paginate}
          />

          <SettlementGuideText />
        </>
      );
    }

    if (loading) {
      renderSettlementView = PlaceholderLoader;
    }

    if (!user.isOndemandSettlementEnabled) return <Redirect to="/settlements" />;

    return (
      <>
        <div className="payout-details">
          <CurrentBalance
            showOndemandSettlementForm={this.showOndemandSettlementForm}
            updatedAt={updatedAt}
            balance={balance}
            isBalanceLoading={current_balance.loading}
            isSettleNowRestricted={isSettleNowRestricted}
            settleNowRestrictionMsg={this.settleNowRestrictionMsg}
            settlementExists={settlementExists}
            esOndemandSettlementEnabled={this.props.esOndemandSettlementEnabled}
            merchantId={user.current}
            isOndemandSettlementEnabled={user.isOndemandSettlementEnabled}
            checkIfFirstEverSettlement={checkIfFirstEverSettlement}
            isOnDemandDisabled={this.isOnDemandDisabled}
          />
          <SettlementMessage
            user={user}
            holidayList={holidayList}
            openModal={openModal}
            balance={balance}
          />
        </div>
        <content>
          <div className="content-wrapper">
            {/* Passing the new props to the HeaderAction component to support the m-web view */}
            <HeaderAction responsive>
              <div className="settlement-actions-wrapper">
                {
                  <div
                    className="btn btn-link settlement-doc-btn"
                    onClick={this.viewSettlementCycle}
                  >
                    <span
                      className="icon i-info-outline"
                      style={{
                        marginRight: '5px',
                        position: 'relative',
                        top: '2px',
                      }}
                    />
                    View Settlement Cycle
                  </div>
                }
                {user.isOndemandSettlementEnabled &&
                  !this.settlementRestricted &&
                  user.isAllowedView('early_settlement') && (
                    <div className="box-left-pad10-inline">
                      <ScheduledBanner
                        openAutoModal={false}
                        fromWhere="Instant Settlements"
                        eventCategory={EVENT_CATEGORY_DASHBOARD_INSTANT_SETTLEMENT}
                      />
                    </div>
                  )}
              </div>
            </HeaderAction>
            {renderSettlementFilterView}
            {renderSettlementView}
          </div>
        </content>
      </>
    );
  }
}

const mapStateToProps = (state) => {
  return {
    user: state.session.user,
    holidayList: state.settlement.holidayList,
    ...state.home,
    ...state.instantSettlements,
  };
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      fetchAll,
      showNotification,
      ...ModalActions,
      fetchCurrentBalance,
      fetchHolidayList,
      fetchOndemandRestrictions,
    },
    dispatch,
  );
};

export default withRouter(connect(mapStateToProps, mapDispatchToProps)(InstantSettlements));
