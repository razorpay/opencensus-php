import React, { useEffect } from 'react';
import { Amount } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import PopoverComponent, { PopoverBody } from 'common/ui/Popover';
import { fetchOndemandRestrictions as fnFetchOndemandRestrictions } from 'merchant/reducers/home';
import {
  getIsGlobalLimitBreached,
  getIsMerchantLimitBreached,
  getIsGlobalLimitBreachedNew,
  getIsMerchantLimitBreachedNew,
  getIsOdsMigrationEnabled,
} from 'merchant/views/Settlements/InstantSettlements/utils/common';
import { OnDemandModalEntry } from 'merchant/views/Settlements/Settlements/components/Modals/OnDemandModalEntry';
import SettleNowButton from 'merchant/views/Settlements/Settlements/components/SettleNowButton';
import {
  trackOndemand,
  EVENT_CATEGORY_DASHBOARD_EARLY_SETTLEMENT,
} from 'merchant/views/Settlements/Settlements/ga';
import { openModal as fnOpenModal, closeModal } from 'merchant_common/reducers/modals';

import { restrictedFeatures, settleNowRestrictionMsgFn } from './utils';
import { useNewODSConfig } from 'merchant/views/Settlements/InstantSettlements/query-hooks/useNewODSConfig';
import { useODSConfig } from 'merchant/views/Settlements/InstantSettlements/query-hooks/useODSConfig';

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
    showLeftBorder = true,
    fromWhere = 'Settlements',
  } = props;
  const currencyCode = user.merchant.currency || 'INR';

  const isOdsExpEnabled = getIsOdsMigrationEnabled(user);

  const odsQuery = isOdsExpEnabled ? useNewODSConfig() : useODSConfig();

  const isNodalAccountLowBalanceBlocked = odsQuery.data?.blocked;
  const isLoading = odsQuery.isFetching || current_balance.loading;
  const isOdsDisabled = isOdsExpEnabled
    ? !!odsQuery.data?.limit_breached
    : !!odsQuery.data?.disable;

  const isOnDemandDisabled = () => {
    return restrictedFeatures.some((feature) => user.isFeatureEnabled(feature));
  };
  const settlementRestricted =
    user.isOndemandSettlementsRestricted || isOnDemandDisabled() || isNodalAccountLowBalanceBlocked;

  const attemptsLeft = isOdsExpEnabled
    ? settlementRestricted && odsQuery.data?.restricted_config?.remaining_attempts
    : settlementRestricted && ondemand_restrictions && ondemand_restrictions.data.attempts_left;

  const isOndemandRestrictionsLoading = isOdsExpEnabled
    ? settlementRestricted && odsQuery.isLoading
    : settlementRestricted && ondemand_restrictions && ondemand_restrictions.loading;

  const settlableAmount = isOdsExpEnabled
    ? settlementRestricted && odsQuery.data?.restricted_config?.remaining_settlement_amount
    : settlementRestricted && ondemand_restrictions && ondemand_restrictions.data.settlable_amount;

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
    if (!isOdsExpEnabled && settlementRestricted) {
      fetchOndemandRestrictions();
    }
  };

  const showOndemandSettlementForm = (e) => {
    trackOndemand.trackSettleNow('Settlements');
    openModal({
      component: (
        <OnDemandModalEntry
          animatedSettlemnetBtn={!settlementExists && esOndemandSettlementEnabled}
          settlableAmount={settlableAmount}
          currentBalance={balance}
          fromWhere={e.clickOrigin ? 'Announcement' : 'Settlements'}
          goBackToInitialModalView={showOndemandSettlementForm}
          eventCategory={EVENT_CATEGORY_DASHBOARD_EARLY_SETTLEMENT}
          checkIfFirstEverSettlement={checkIfFirstEverSettlement}
        />
      ),
      isNew: true,
      size: 'small',
      disableClose: true,
    });
  };

  const getMerchantLimitBreached = isOdsExpEnabled
    ? getIsMerchantLimitBreachedNew(odsQuery.data)
    : getIsMerchantLimitBreached(odsQuery.data);

  const maxLimit = odsQuery.data
    ? isOdsExpEnabled
      ? odsQuery?.data.max_limit_per_working_day
      : odsQuery?.data.max_limit
    : 0;

  const globalLimitBreached = isOdsExpEnabled
    ? getIsGlobalLimitBreachedNew(odsQuery.data)
    : getIsGlobalLimitBreached(odsQuery.data);

  const getTooltipContent = () => {
    if (isLoading) {
      return 'Loading...';
    }
    if (!user.isOndemandSettlementsRestricted && getMerchantLimitBreached) {
      return (
        <>
          You’ve already settled your maximum allowed limit of{' '}
          <Amount
            size="small"
            color="surface.text.staticWhite.normal"
            currency={currencyCode}
            value={(maxLimit || 0) / 100}
          />{' '}
          for the day.
        </>
      );
    }
    if (globalLimitBreached) {
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
      odsQuery,
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
        fromWhere={fromWhere}
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
  };
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    { openModal: fnOpenModal, closeModal, fetchOndemandRestrictions: fnFetchOndemandRestrictions },
    dispatch,
  );
};

export default connect(mapStateToProps, mapDispatchToProps)(SettleNow);
