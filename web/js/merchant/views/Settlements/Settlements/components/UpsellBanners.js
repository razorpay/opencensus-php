import React, { useState, useEffect } from 'react';
import { connect } from 'react-redux';
import LoaderDots from 'common/ui/LoaderDots';
import SettlementsUpsellBanner from 'merchant/views/Settlements/Settlements/components/SettlementsUpsellBanner';
import SamedayUpselling from './Modals/ScheduledModal/components/Upselling';
import { resolvePath } from 'common/utils/rzp-utils';
import { DEFAULT_MIN_WITHDRAW_AMOUNT, VIEWS } from './constants';

const UpsellBanners = (props) => {
  const { user, withdrawalConfiguration, closeModal, hideCloseButton } = props;

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
  const isMerchantEligibileForLoc = !!(
    user.isLOCEnabled &&
    user.isLOSEnabled &&
    user.isOndemandSettlementEnabled
  );
  const minWithdrawAmount = resolvePath(
    withdrawalConfiguration,
    'data.configuration.min_withdraw_amount',
    DEFAULT_MIN_WITHDRAW_AMOUNT,
  );
  const isBalanceAvailable =
    hasWithdrawalConfig && internalCreditBalance >= Number(minWithdrawAmount);

  useEffect(() => {
    if (!isLoading && isMerchantEligibileForLoc) {
      if (isBalanceAvailable && user.isWithdrawFeatureEnabled) {
        setView(VIEWS.LOC_BALANCE_AVAILABLE);
      } else if (!user.isAutomaticSettlementEnabled && !user.isAutomaticSettlementRestricted) {
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
        return <SamedayUpselling showDiscount />;

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

export default connect(mapStateToProps)(UpsellBanners);
