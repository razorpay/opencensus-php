import React, { useEffect } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { fetchOndemandRestrictions as fnFetchOndemandRestrictions } from 'merchant/reducers/home';
import { openModal as fnOpenModal, closeModal } from 'merchant_common/reducers/modals';
import SettleNowButton from 'merchant/views/Settlements/Settlements/components/SettleNowButton';
import OndemandModal from 'merchant/views/Settlements/Settlements/components/Modals/OndemandModal';
import PopoverComponent, { PopoverBody } from 'common/ui/Popover';
import {
  trackOndemand,
  EVENT_CATEGORY_DASHBOARD_EARLY_SETTLEMENT,
} from 'merchant/views/Settlements/Settlements/ga';
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
    isNodalAccountLowBalanceBlocked,
    showLeftBorder = true,
    odsConfigState,
  } = props;
  const isLoading = odsConfigState.loading || current_balance.loading;
  const isOdsDisabled = !!odsConfigState.data?.disable;

  const isOnDemandDisabled = () => {
    return restrictedFeatures.some((feature) => user.isFeatureEnabled(feature));
  };
  const settlementRestricted =
    user.isOndemandSettlementsRestricted || isOnDemandDisabled() || isNodalAccountLowBalanceBlocked;

  const attemptsLeft =
    settlementRestricted && ondemand_restrictions && ondemand_restrictions.data.attempts_left;

  const isOndemandRestrictionsLoading =
    settlementRestricted && ondemand_restrictions && ondemand_restrictions.loading;

  const settlableAmount =
    settlementRestricted && ondemand_restrictions && ondemand_restrictions.data.settlable_amount;

  const isSettleNowRestricted =
    settlementRestricted && (!attemptsLeft || !settlableAmount || isOndemandRestrictionsLoading);

  const isEsOnDemandBlocked = user.isEsOnDemandBlocked;

  let balance = current_balance.data.balance || 0;

  if (balance < 0) {
    balance = Math.abs(balance);
  }

  const checkIfSettlementDisabled =
    isSettleNowRestricted ||
    balance < 100 ||
    isOnDemandDisabled() ||
    isEsOnDemandBlocked ||
    isOdsDisabled;

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

  const getTooltipContent = () => {
    if (isLoading) {
      return 'Loading...';
    }
    if (isOdsDisabled) {
      return 'On-demand Settlements are being limited due to high usage. Please try again the next working day.';
    }
    if (isEsOnDemandBlocked) {
      return 'Settle now is temporarily unavailable. Please try again at 8:00 AM tomorrow.';
    }
    return null;
  };

  const tooltipMsg =
    getTooltipContent() ||
    settleNowRestrictionMsgFn(
      settlementRestricted,
      ondemand_restrictions,
      isOnDemandDisabled,
      user,
      isNodalAccountLowBalanceBlocked,
    );

  useEffect(() => {
    fetchRestrictionsIfAny();
  }, []);

  return (
    <div className={showLeftBorder ? 'box-left-pad10-inline' : ''}>
      <SettleNowButton
        // TODO: Ideally we should use loading indicator instead of disabling CTA(existing pattern used by checkIfSettlementDisabled and SettleNowButton).
        disabled={isLoading || checkIfSettlementDisabled}
        merchantId={user.current}
        fromWhere="Settlements"
        settlementExists={settlementExists}
        esOndemandSettlementEnabled={esOndemandSettlementEnabled}
        showOndemandSettlementForm={showOndemandSettlementForm}
        checkIfFirstEverSettlement={checkIfFirstEverSettlement}
      />
      {tooltipMsg && (
        <PopoverComponent
          align="top"
          parentQuerySelector=".settle-btn .settle-now--list"
          theme="dark"
        >
          <PopoverBody>{tooltipMsg}</PopoverBody>
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
    odsConfigState: state.settlement.settleNowButtonDisabled,
  };
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    { openModal: fnOpenModal, closeModal, fetchOndemandRestrictions: fnFetchOndemandRestrictions },
    dispatch,
  );
};

export default connect(mapStateToProps, mapDispatchToProps)(SettleNow);
