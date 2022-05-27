import React, { useState, useEffect } from 'react';
import { connect } from 'react-redux';
import LoaderDots from 'common/ui/LoaderDots';
import { fetchFunctionalWithdrawalConfigByMerchantID as fnFetchWithdrawalConfig } from 'merchant/reducers/capital/withdrawals';
import SettlementsUpsellBanner from 'merchant/views/Settlements/Settlements/components/SettlementsUpsellBanner';
import ApplicationBanner from 'merchant/views/Capital/CashAdvanceNudges/components/ApplicationBanner';
import SamedayUpselling from './Modals/ScheduledModal/components/Upselling';
import { resolvePath } from 'common/utils/rzp-utils';
import { DEFAULT_MIN_WITHDRAW_AMOUNT, VIEWS } from './constants';
import { RZP_MERCHANT_OWNER_TYPE } from 'merchant/views/Capital/CashAdvance/constants';
import { SAMEDAY_MODAL_LOCATIONS } from './Modals/ScheduledModal/constants';

const UpsellBanners = (props) => {
  const {
    user: {
      isLOCEnabled,
      isLOSEnabled,
      isOndemandSettlementEnabled,
      isWithdrawFeatureEnabled,
      isAutomaticSettlementEnabled,
      isAutomaticSettlementRestricted,
      current,
    },
    withdrawalConfiguration,
    closeModal,
    hideCloseButton,
    fetchWithdrawalConfig,
  } = props;

  const isLoading = withdrawalConfiguration?.loading;
  const [view, setView] = useState();

  const hasWithdrawalConfig = !!withdrawalConfiguration?.data;
  const getInternalCreditBalance = () => {
    if (!hasWithdrawalConfig) return 0;

    const {
      data: {
        configuration: { internal_credit_limit = 0 } = {},
        principal_outstanding_balance = 0,
      },
    } = withdrawalConfiguration;

    const internalBalance =
      parseInt(internal_credit_limit, 10) - parseInt(principal_outstanding_balance, 10);

    return internalBalance > 0 ? internalBalance : 0;
  };
  const internalCreditBalance = getInternalCreditBalance();
  const isMerchantEligibileForLoc = !!(isLOCEnabled && isLOSEnabled && isOndemandSettlementEnabled);
  const minWithdrawAmount = resolvePath(
    withdrawalConfiguration,
    'data.configuration.min_withdraw_amount',
    DEFAULT_MIN_WITHDRAW_AMOUNT,
  );
  const isBalanceAvailable =
    isMerchantEligibileForLoc &&
    hasWithdrawalConfig &&
    internalCreditBalance >= Number(minWithdrawAmount);

  useEffect(() => {
    if (isMerchantEligibileForLoc && !hasWithdrawalConfig && !withdrawalConfiguration?.error) {
      fetchWithdrawalConfig({
        owner_id: current,
        owner_type: RZP_MERCHANT_OWNER_TYPE,
      });
    }
  }, [hasWithdrawalConfig]);

  useEffect(() => {
    if (!isLoading) {
      if (isBalanceAvailable && isWithdrawFeatureEnabled) {
        setView(VIEWS.LOC_BALANCE_AVAILABLE);
      } else if (isMerchantEligibileForLoc) {
        setView(VIEWS.LOC_ELIGIBLE);
      } else if (!isAutomaticSettlementEnabled && !isAutomaticSettlementRestricted) {
        setView(VIEWS.SAMEDAY_ELIGIBLE);
      }
    }
  }, [isBalanceAvailable, isLoading, isMerchantEligibileForLoc]);

  if (isLoading)
    return (
      <div className="custom-loader">
        <LoaderDots />
      </div>
    );

  const renderContent = () => {
    switch (view) {
      case VIEWS.LOC_BALANCE_AVAILABLE:
        return (
          <SettlementsUpsellBanner closeModal={closeModal} hideCloseButton={hideCloseButton} />
        );

      case VIEWS.SAMEDAY_ELIGIBLE:
        return <SamedayUpselling showDiscount from={SAMEDAY_MODAL_LOCATIONS.ONDEMAND} />;

      case VIEWS.LOC_ELIGIBLE:
        return <ApplicationBanner />;

      default:
        return null;
    }
  };

  return renderContent();
};

const mapStateToProps = (state) => ({
  user: state.session.user,
  withdrawalConfiguration: state.withdrawals.withdrawalConfiguration,
});

export default connect(mapStateToProps, {
  fetchWithdrawalConfig: fnFetchWithdrawalConfig,
})(UpsellBanners);
