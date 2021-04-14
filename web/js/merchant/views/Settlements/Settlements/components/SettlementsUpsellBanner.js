import React, { useEffect } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { withRouter } from 'react-router-dom';
import PropTypes from 'prop-types';

import Button from 'common/new-ui/Button';
import Amount from 'common/ui/Amount';
import LoaderDots from 'common/ui/LoaderDots';
import { fetchProducts, getApplications } from 'merchant/reducers/capital';
import { fetchFunctionalWithdrawalConfigByMerchantID } from 'merchant/reducers/capital/withdrawals';
import track from 'common/utils/googleAnalytics';
import { CAPITAL_PRODUCT_CODES } from 'merchant/views/Capital/Loans/constants';

const SettlementsUpsell = ({
  user,
  fetchProducts,
  getApplications,
  fetchFunctionalWithdrawalConfigByMerchantID,
  closeModal,
  products,
  applications,
  history,
  withdrawalConfigurationDetails,
  hideCloseButton,
}) => {
  const hasWithdrawalConfig = !!withdrawalConfigurationDetails.data;
  const applicationsAlreadyFetched = applications && applications.data && applications.data.length;

  const getProductDetails = () => {
    return products.find((p) => p.name === CAPITAL_PRODUCT_CODES.CASH_ADVANCE);
  };

  // fetch Applications
  const fetchApplications = async () => {
    const haveNoProducts = !products || !products.data.length;

    if (haveNoProducts || applicationsAlreadyFetched || applications.errors) return;

    const productDetails = getProductDetails();

    try {
      await getApplications({
        owner_type: 'MERCHANT',
        owner_id: user.current,
        product_id: productDetails.id,
      });
    } catch (e) {}
  };

  // fetch Products
  const getProducts = async () => {
    const productsAlreadyFetched = products && products.data && products.data.length;

    if (productsAlreadyFetched || products.errors) return;
    return fetchProducts();
  };

  useEffect(() => {
    getProducts();
    fetchApplications();
  }, [products.data]);

  // get withdrawal config
  useEffect(() => {
    if (!hasWithdrawalConfig && !withdrawalConfigurationDetails.errors) {
      fetchFunctionalWithdrawalConfigByMerchantID({
        owner_id: user.current,
        owner_type: 'RZP_MERCHANT',
      });
    }
  }, [hasWithdrawalConfig]);

  const getInternalCreditBalance = () => {
    if (!hasWithdrawalConfig) return 0;

    const {
      data: {
        configuration: { internal_credit_limit = 0 } = {},
        principal_outstanding_balance = 0,
      },
    } = withdrawalConfigurationDetails;

    const internalBalance =
      parseInt(internal_credit_limit, 10) - parseInt(principal_outstanding_balance, 10);

    return internalBalance > 0 ? internalBalance : 0;
  };

  const goToCashAdvance = () => {
    track({
      eventCategory: 'Settle Now - Banner',
      eventAction: 'Withdraw Funds',
      eventLabel: 'Click | Withdraw Funds',
    });
    closeModal();
    history.push({
      pathname: '/capital/cash-advance',
    });
  };

  const internalCreditBalance = getInternalCreditBalance();
  const isMerchantEligibile =
    user.isWithdrawFeatureEnabled && user.isLOSEnabled && user.isOndemandSettlementEnabled;
  const isLoading =
    applications.loading || products.loading || withdrawalConfigurationDetails.loading;
  const showWithdrawNowBanner =
    isMerchantEligibile &&
    hasWithdrawalConfig &&
    internalCreditBalance >= 1000 &&
    applicationsAlreadyFetched &&
    !isLoading;

  useEffect(() => {
    if (showWithdrawNowBanner) hideCloseButton();
  }, [showWithdrawNowBanner]);

  if (isLoading)
    return (
      <div className="custom-loader">
        <LoaderDots />
      </div>
    );
  else if (showWithdrawNowBanner) {
    return (
      <div
        className="cash-advance-upsell-banner"
        style={{
          backgroundImage: 'url("/dist/css/assets/capital/withdrawals-upsell-banner-bg.svg")',
        }}
      >
        <div className="banner-content">
          <p>
            <i className="fa fa-rupee" />
            Need more cash?
          </p>
          <p>
            You have{' '}
            <strong>
              <Amount value={Number(internalCreditBalance)} parentQuerySelector=".modal-body" />
            </strong>{' '}
            available in the withdrawable balance of your credit line with Razorpay Cash Advance.
          </p>
          <Button onClick={goToCashAdvance}>Withdraw Funds</Button>
        </div>
      </div>
    );
  }

  return null;
};

SettlementsUpsell.propTypes = {
  user: PropTypes.object,
  fetchProducts: PropTypes.func,
  getApplications: PropTypes.func,
  fetchFunctionalWithdrawalConfigByMerchantID: PropTypes.func,
  closeModal: PropTypes.func,
  history: PropTypes.object,
  eventCategory: PropTypes.string,
  applications: PropTypes.shape({
    loading: PropTypes.bool,
    data: PropTypes.array,
    error: PropTypes.array,
  }),
  products: PropTypes.shape({
    loading: PropTypes.bool,
    data: PropTypes.array,
    error: PropTypes.array,
  }),
  withdrawalConfigurationDetails: PropTypes.shape({
    loading: PropTypes.bool,
    data: PropTypes.object,
    error: PropTypes.array,
  }),
};

SettlementsUpsell.defaultProps = {
  applications: {
    loading: false,
    data: null,
    errors: null,
  },
  products: {
    loading: false,
    data: null,
    errors: null,
  },
  withdrawalConfigurationDetails: {
    loading: false,
    data: null,
    errors: null,
  },
};

const mapStateToProps = (state, ownProps) => {
  const {
    session: { user },
    home: { current_balance },
    loanApplicationDetails: {
      applications: {
        loading: applicationsLoading = false,
        data: { applications = [] } = {},
        errors: applicationsError = null,
      } = {},
      products,
    },
    withdrawals: { withdrawalConfiguration },
  } = state;

  return {
    user,
    current_balance,
    applications: {
      loading: applicationsLoading,
      data: applications,
      errors: applicationsError,
    },
    products,
    withdrawalConfigurationDetails: withdrawalConfiguration,
    ...ownProps,
  };
};

const mapDispatchToProps = (dispatch) => ({
  fetchProducts: bindActionCreators(fetchProducts, dispatch),
  getApplications: bindActionCreators(getApplications, dispatch),
  fetchFunctionalWithdrawalConfigByMerchantID: bindActionCreators(
    fetchFunctionalWithdrawalConfigByMerchantID,
    dispatch,
  ),
});

export default withRouter(connect(mapStateToProps, mapDispatchToProps)(SettlementsUpsell));
