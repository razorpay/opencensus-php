import React, { useEffect, useState } from 'react';
import PropTypes from 'prop-types';
import { withRouter } from 'react-router-dom';
import { connect } from 'react-redux';
import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';
import ShowWhen from 'merchant/components/ShowWhen';
import ScheduledNitroBanner from 'merchant/components/ScheduledNitroBanner';
import Button from 'common/new-ui/Button';
import Amount from 'common/ui/Amount';
import { fetchProducts, getApplications } from 'merchant/reducers/capital';
import { CAPITAL_PRODUCT_NAME_CODE_MAP } from 'merchant/views/Capital/Loans/constants';
import { fetchFunctionalWithdrawalConfigByMerchantID } from 'merchant/reducers/capital/withdrawals';

const EVENT_CATEGORY_CA_BANNER = 'Cash Advance Banner - Settlements';

const gaCABannerEventDispatcher = (eventObject) => {
  eventObject.eventCategory = EVENT_CATEGORY_CA_BANNER;
  window.rzpAnalytics?.(eventObject);
};

const CashAdvanceOrNitroBanner = ({
  user,
  fetchProducts,
  getApplications,
  fetchFunctionalWithdrawalConfigByMerchantID,
  loanApplicationDetails,
  history,
  withdrawalConfigurationDetails,
}) => {
  const [hasMerchantApplied, setHasMerchantApplied] = useState(false);
  const [isApplicationsLoading, setIsApplicationsLoading] = useState(true);
  const getProductCode = () => {
    return CAPITAL_PRODUCT_NAME_CODE_MAP['cash-advance'];
  };

  const getProductDetails = () => {
    return loanApplicationDetails.products.data.find((p) => p.name === getProductCode());
  };

  const fetchApplications = async () => {
    if (
      !loanApplicationDetails.products.data ||
      !Array.isArray(loanApplicationDetails.products.data)
    )
      return;
    const productDetails = getProductDetails();
    try {
      const { errors = null, data: { applications = [] } = {} } = await getApplications({
        owner_type: 'MERCHANT',
        owner_id: user.current,
        product_id: productDetails.id,
      });

      if (!errors && applications && applications.length > 0) {
        setHasMerchantApplied(true);
      }
    } catch (e) {
      console.error(e);
    } finally {
      setIsApplicationsLoading(false);
    }
  };

  useEffect(() => {
    fetchFunctionalWithdrawalConfigByMerchantID({
      owner_id: user.current,
      owner_type: 'RZP_MERCHANT',
    });
  }, []);

  useEffect(() => {
    async function fetchApplicationStatus() {
      if (
        loanApplicationDetails.products.data &&
        Array.isArray(loanApplicationDetails.products.data)
      )
        return;
      await fetchProducts();
    }
    fetchApplicationStatus();
    fetchApplications();
  }, [loanApplicationDetails.products.data]);

  function goToCashAdvance() {
    history.push({
      pathname: '/capital/cash-advance',
      state: { eventCategory: EVENT_CATEGORY_CA_BANNER },
    });
  }

  function handleApplyNowClick() {
    gaCABannerEventDispatcher({
      eventAction: 'Click Apply Now',
      eventLabel: 'Clicks | Apply Now',
    });
    goToCashAdvance();
  }

  function handleWithdrawFundsClick() {
    gaCABannerEventDispatcher({
      eventAction: 'Click Withdraw Funds',
      eventLabel: 'Clicks | Withdraw Funds',
    });
    goToCashAdvance();
  }

  const hasWithdrawalConfiguration = !!withdrawalConfigurationDetails.data;

  const getInternalCreditBalance = () => {
    if (!hasWithdrawalConfiguration) return 0;
    const internalBalance =
      // eslint-disable-next-line radix
      parseInt(withdrawalConfigurationDetails.data.configuration.internal_credit_limit) -
      // eslint-disable-next-line radix
      parseInt(withdrawalConfigurationDetails.data.principal_outstanding_balance || 0);
    return internalBalance > 0 ? internalBalance : 0;
  };

  const internalCreditBalance = getInternalCreditBalance();

  const isMerchantEligibile = user.isLOCEnabled && user.isLOSEnabled;

  const showApplyNowBanner =
    isMerchantEligibile &&
    !user.isWithdrawFeatureEnabled &&
    !hasMerchantApplied &&
    !isApplicationsLoading;

  const showWithdrawNowBanner =
    isMerchantEligibile &&
    user.isWithdrawFeatureEnabled &&
    hasWithdrawalConfiguration &&
    internalCreditBalance >= 500000 &&
    hasMerchantApplied &&
    !isApplicationsLoading;

  if (showApplyNowBanner) {
    return (
      <AnnouncementBanner
        card_id="apply-for-cash-advance-banner"
        title="Need more money!"
        theme="primary"
      >
        Apply for Cash Advance to withdraw additional money instantly whenever you need, day or
        night!
        <Button.Secondary
          className="btn-border scheduled-btn-act ml-16"
          onClick={handleApplyNowClick}
        >
          <strong>Apply Now</strong>
        </Button.Secondary>
      </AnnouncementBanner>
    );
  } else if (showWithdrawNowBanner) {
    return (
      <AnnouncementBanner card_id="withdraw-funds-banner" title="Need more money!" theme="primary">
        You have <Amount value={internalCreditBalance} className="ca-banner__withdraw-amount" />{' '}
        available in the withdrawable balance of your credit line with Razorpay Cash Advance.
        <Button.Secondary
          className="btn-border scheduled-btn-act ml-16"
          onClick={handleWithdrawFundsClick}
        >
          <strong>Withdraw Funds</strong>
        </Button.Secondary>
      </AnnouncementBanner>
    );
  } else if (user.isProjectNitroEnabled || user.isProjectNitroCorporateCard) {
    return (
      <ShowWhen
        additionalCondition={(user) =>
          user.isProjectNitroEnabled || user.isProjectNitroCorporateCard
        }
      >
        <AnnouncementBanner
          card_id="nitro-settlements-banner"
          title="Exclusive Offer For You"
          canBeClosed={false}
        >
          <ScheduledNitroBanner
            fromWhere="settlements"
            url="https://lp.razorpay.com/razorpayxca-sttlmnts1"
          />
        </AnnouncementBanner>
      </ShowWhen>
    );
  }

  return null;
};

CashAdvanceOrNitroBanner.propTypes = {
  user: PropTypes.object,
  // eslint-disable-next-line react/no-typos
  fetchProducts: PropTypes.function,
  // eslint-disable-next-line react/no-typos
  getApplications: PropTypes.function,
  // eslint-disable-next-line react/no-typos
  fetchFunctionalWithdrawalConfigByMerchantID: PropTypes.function,
  loanApplicationDetails: PropTypes.object,
  history: PropTypes.object,
  withdrawalConfigurationDetails: PropTypes.object,
};

export default withRouter(
  connect(
    (state) => ({
      user: state.session.user,
      current_balance: state.home.current_balance,
      loanApplicationDetails: state.loanApplicationDetails,
      withdrawalConfigurationDetails: state.withdrawals.withdrawalConfiguration,
    }),
    { fetchProducts, getApplications, fetchFunctionalWithdrawalConfigByMerchantID },
  )(CashAdvanceOrNitroBanner),
);
