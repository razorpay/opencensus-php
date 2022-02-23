/* eslint-disable */
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
import { fetchSettlements as fetchAll } from 'merchant/reducers/collection';
import * as ModalActions from 'merchant_common/reducers/modals';
import { getKeysSeparatedByPipe, getFormattedAmountNew } from 'common/utils/rzp-utils';
import RequestEarlyAccessForm from 'merchant/components/Announcements/EarlySettlements/Modal';
import { showNotification } from 'merchant_common/reducers/notifications';
import {
  trackEarlySettlementRequests,
  trackOndemand,
  EVENT_CATEGORY_DASHBOARD_SETTLEMENTS,
  EVENT_CATEGORY_DASHBOARD_EARLY_SETTLEMENT,
} from './ga';
import {
  fetchSettlementAmount as fnFetchSettlementAmount,
  fetchBalanceConfig as fnFetchBalanceConfig,
  fetchOndemandRestrictions,
} from 'merchant/reducers/home';
import {
  fetchSchedule as fnFetchSchedule,
  fetchHolidayList as fnFetchHolidayList,
} from 'merchant/reducers/settlements/details';
import OndemandModal from 'merchant/views/Settlements/Settlements/components/Modals/OndemandModal';
import SettlementScheduleV2 from 'merchant/views/Settlements/components/SettlementScheduleV2';
import SettlementGuideText from 'merchant_common/components/SettlementGuideText';
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
      fetchSchedule,
      fetchBalanceConfig,
      fetchSettlementAmount,
      fetchHolidayList,
      location,
    } = this.props;

    window.rzpAnalytics?.({
      eventCategory: EVENT_CATEGORY_DASHBOARD_SETTLEMENTS,
      eventAction: 'Go To - Settlements',
    });

    window.addEventListener('remove-req-es-button', this.removeRequestESButton, false);

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
      window.rzpAnalytics?.({
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
    window.rzpAnalytics?.({
      eventCategory: EVENT_CATEGORY_DASHBOARD_SETTLEMENTS,
      eventAction: 'Clear Search Params - Settlements',
    });

    handleAnalytics('clear', 'clicked');
  };

  settlementBreakupOnMount = (id) => {
    window.rzpAnalytics?.({
      eventCategory: EVENT_CATEGORY_DASHBOARD_SETTLEMENTS,
      eventAction: 'Show - Settlement Breakup',
      eventLabel: `settlement_id=${id}`,
    });
  };

  settlementBreakupOnUnmount = (id) => {
    window.rzpAnalytics?.({
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
      component: <SettlementScheduleV2 />,
    });

    window.rzpAnalytics?.({
      eventCategory: 'Settlement Revamp',
      eventAction: 'View Settlement Cycle',
      eventLabel: `Settlements`,
    });
    handleAnalytics('settlement cycle', 'clicked');
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
      .catch(({ errors }) => {
        const failureProps = {
          searchTerm: args.id,
          resultsReturned: false,
          status: 'failure',
          failureReason: errors?.join(', '),
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
    const { loading, items, error, current_balance } = this.props;

    let balance = current_balance.data.balance || 0;

    if (balance < 0) {
      balance = Math.abs(balance);
    }

    return (
      <content>
        <div class="content-wrapper">
          <SettlementsListFilter
            form="settlementsListFilter"
            count={this.state.count}
            onSubmit={this.handleSearch}
            onSearchAnalytics={this.onSearchAnalytics}
            onClearAnalytics={this.onClearAnalytics}
          />

          <div className="clearfix" />

          {error && <Alert type="error" message={error} />}

          <SettlementsList settlements={items} isLoading={loading} showBreakup={this.showBreakup} />

          <Pager
            count={this.state.count}
            skip={this.state.skip}
            length={items.length}
            onClick={this.handlePagination}
          />

          <SettlementGuideText />
        </div>
      </content>
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
    settlementConfig: state.settlement.config,
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
