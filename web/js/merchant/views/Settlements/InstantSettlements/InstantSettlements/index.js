import React from 'react';
import { connect } from 'react-redux';
import { Navigate } from 'react-router-dom';
import { withRouter } from 'common/deprecated/withRouter';
import ListContainer from 'merchant/containers/ListContainer';
import { fetchOndemandRestrictions } from 'merchant/reducers/home';
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
import EmptySettleNow from 'merchant/views/Settlements/InstantSettlements/InstantSettlements/EmptySettleNow';
import InstantSettlementListFilter from 'merchant/views/Settlements/InstantSettlements/InstantSettlements/ListFilter';
import trackIS, {
  EVENT_CATEGORY_DASHBOARD_INSTANT_SETTLEMENT,
} from 'merchant/views/Settlements/InstantSettlements/ga';
import { getFormattedAmountNew } from 'common/utils/rzp-utils';
import { trackOnDemandSearchClick } from 'merchant/views/Settlements/trackEvents';
import { bindActionCreators } from 'redux';
import TriggerOnQueryParamMatch from 'common/ui/TriggerOnQueryParamMatch';
import {
  INSTANT_SETTLEMENT,
  ACTION_QUERY_PARAM_KEY,
} from 'merchant/views/Settlements/deeplink-constants';

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

  /* istanbul ignore next */
  get settleNowRestrictionMsg() {
    const { user } = this.props;
    const isEsOnDemandBlocked = user.isEsOnDemandBlocked;

    if (!this.settlementRestricted) return null;

    const { attempts_left, settlable_amount, max_amount_limit, settlements_count_limit } =
      this.props.ondemand_restrictions.data;
    if (isEsOnDemandBlocked) {
      return `Temporary Downtime: We're experiencing some technical difficulties, and the Settle Now feature is temporarily unavailable. Our team is working on resolving the issue. We apologize for the inconvenience.`;
    } else if (this.isOnDemandDisabled) {
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
              <span className="highlight-tooltip" key={`${item}_${i}`}>
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
    } else return null;
  }

  componentDidMount() {
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
      queryParams: {
        [ACTION_QUERY_PARAM_KEY]: INSTANT_SETTLEMENT,
      },
    });
  };

  render() {
    const {
      user,
      loading,
      items,
      error,
      location: { search },
    } = this.props;
    const { count } = this.state;

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

    if (!user.isOndemandSettlementEnabled) return <Navigate to="/settlements" replace />;

    return (
      <content>
        <TriggerOnQueryParamMatch
          queryParamsMapping={[
            {
              key: ACTION_QUERY_PARAM_KEY,
              value: INSTANT_SETTLEMENT,
              trigger: this.showOndemandSettlementForm,
            },
          ]}
        />
        <div className="content-wrapper">
          {renderSettlementFilterView}
          {renderSettlementView}
        </div>
      </content>
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
      fetchHolidayList,
      fetchOndemandRestrictions,
    },
    dispatch,
  );
};

export default withRouter(connect(mapStateToProps, mapDispatchToProps)(InstantSettlements));
