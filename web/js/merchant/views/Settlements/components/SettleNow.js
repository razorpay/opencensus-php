import React, { useEffect } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { fetchOndemandRestrictions as fnFetchOndemandRestrictions } from 'merchant/reducers/home';
import { openModal as fnOpenModal, closeModal } from 'merchant_common/reducers/modals';
import SettleNowButton from 'merchant/views/Settlements/Settlements/components/SettleNowButton';
import OndemandModal from 'merchant/views/Settlements/Settlements/components/Modals/OndemandModal';
import PopoverComponent, { PopoverBody } from 'common/ui/Popover';
import { trackOndemand, EVENT_CATEGORY_DASHBOARD_EARLY_SETTLEMENT } from '../Settlements/ga';
import { restrictedFeatures, settleNowRestrictionMsgFn } from './utils';

const SettleNow = (props) => {
  const {
    user,
    ondemand_restrictions,
    current_balance,
    checkIfFirstEverSettlement,
    settlementExists,
    esOndemandSettlementEnabled,
    openModal,
    fetchOndemandRestrictions,
  } = props;

  const isOnDemandDisabled = () => {
    return restrictedFeatures.some((feature) => user.isFeatureEnabled(feature));
  };
  const settlementRestricted =
    user.isFeatureEnabled('es_on_demand_restricted') || isOnDemandDisabled();

  const attemptsLeft =
    settlementRestricted && ondemand_restrictions && ondemand_restrictions.data.attempts_left;

  const isOndemandRestrictionsLoading =
    settlementRestricted && ondemand_restrictions && ondemand_restrictions.loading;

  const settlableAmount =
    settlementRestricted && ondemand_restrictions && ondemand_restrictions.data.settlable_amount;

  const isSettleNowRestricted =
    settlementRestricted && (!attemptsLeft || !settlableAmount || isOndemandRestrictionsLoading);

  let balance = current_balance.data.balance || 0;

  if (balance < 0) {
    balance = Math.abs(balance);
  }
  const checkIfSettlementDisabled =
    isSettleNowRestricted || current_balance.loading || balance < 100 || isOnDemandDisabled();

  const fetchRestrictionsIfAny = () => {
    if (settlementRestricted) {
      fetchOndemandRestrictions();
    }
  };

  const showOndemandSettlementForm = (e) => {
    trackOndemand.trackSettleNow('Settlements');
    openModal({
      component: (
        <OndemandModal
          animatedSettlemnetBtn={!settlementExists && esOndemandSettlementEnabled}
          settlableAmount={settlableAmount}
          currentBalance={balance}
          fromWhere={e.clickOrigin ? 'Announcement' : 'Settlements'}
          goBackToInitialModalView={showOndemandSettlementForm}
          eventCategory={EVENT_CATEGORY_DASHBOARD_EARLY_SETTLEMENT}
          checkIfFirstEverSettlement={checkIfFirstEverSettlement}
        />
      ),
      size: 'small',
      disableClose: true,
    });
  };

  const settleNowRestrictionMsg = settleNowRestrictionMsgFn(
    settlementRestricted,
    ondemand_restrictions,
    isOnDemandDisabled,
    user,
  );

  useEffect(() => {
    fetchRestrictionsIfAny();
  }, []);

  return (
    <div className="box-left-pad10-inline">
      <SettleNowButton
        disabled={checkIfSettlementDisabled}
        merchantId={user.current}
        fromWhere="Settlements"
        settlementExists={settlementExists}
        esOndemandSettlementEnabled={esOndemandSettlementEnabled}
        showOndemandSettlementForm={showOndemandSettlementForm}
        checkIfFirstEverSettlement={checkIfFirstEverSettlement}
      />
      {settleNowRestrictionMsg && (
        <PopoverComponent
          align="top"
          parentQuerySelector=".settle-btn .settle-now--list"
          theme="dark"
        >
          <PopoverBody>{settleNowRestrictionMsg}</PopoverBody>
        </PopoverComponent>
      )}
    </div>
  );
};

const mapStateToProps = (state) => {
  return {
    user: state.session.user,
    mode: state.session.mode,
    ...state.home,
    ...state.instantSettlements,
  };
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    { openModal: fnOpenModal, closeModal, fetchOndemandRestrictions: fnFetchOndemandRestrictions },
    dispatch,
  );
};

export default connect(mapStateToProps, mapDispatchToProps)(SettleNow);
