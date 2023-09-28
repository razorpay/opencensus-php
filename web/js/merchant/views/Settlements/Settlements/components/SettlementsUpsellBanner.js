import React, { useEffect } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { withRouter } from 'common/deprecated/withRouter';
import PropTypes from 'prop-types';
import Button from 'common/new-ui/Button';
import Amount from 'common/ui/Amount';
import LoaderDots from 'common/ui/LoaderDots';
import {
  fetchProducts as fnFetchProducts,
  getApplications as fnGetApplications,
} from 'merchant/reducers/capital';
import { fetchFunctionalWithdrawalConfigByMerchantID as fnFetchFunctionalWithdrawalConfigByMerchantID } from 'merchant/reducers/capital/withdrawals';
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
  const haveProductsData = products && products.data && products.data.length;
  const isMerchantEligibile = !!(
    user.isWithdrawFeatureEnabled &&
    user.isLOSEnabled &&
    user.isOndemandSettlementEnabled
  );

  const getProductDetails = () => {
    const { data = [] } = products;

    return data.find((p) => p.name === CAPITAL_PRODUCT_CODES.CASH_ADVANCE);
  };

  // fetch Applications
  const fetchApplications = async () => {
    if (!haveProductsData || applicationsAlreadyFetched || applications.errors) return;

    const productDetails = getProductDetails();

    try {
      await getApplications({
        owner_type: 'MERCHANT',
        owner_id: user.current,
        product_id: productDetails.id,
      });
    } catch (e) {
      // empty catch
    }
  };

  // fetch Products
  // eslint-disable-next-line require-await
  const getProducts = async () => {
    const productsAlreadyFetched = products && products.data && products.data.length;

    if (productsAlreadyFetched || products.errors) return;
    // eslint-disable-next-line consistent-return
    return fetchProducts();
  };

  useEffect(() => {
    if (!isMerchantEligibile) return;

    getProducts();
    fetchApplications();
  }, [products.data]);

  // get withdrawal config
  useEffect(() => {
    // eslint-disable-next-line
    if (!isMerchantEligibile) return;
    else if (!hasWithdrawalConfig && !withdrawalConfigurationDetails.errors) {
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
      eventAction: `Withdraw Funds | ${user.current}`,
      eventLabel: 'Click | Withdraw Funds',
    });
    closeModal();
    history.push({
      pathname: '/capital/cash-advance',
    });
  };

  const internalCreditBalance = getInternalCreditBalance();
  const isLoading =
    applications.loading || products.loading || withdrawalConfigurationDetails.loading;
  const showWithdrawNowBanner =
    isMerchantEligibile &&
    hasWithdrawalConfig &&
    internalCreditBalance >= 1000 &&
    applicationsAlreadyFetched &&
    !isLoading;

  useEffect(() => {
    if (showWithdrawNowBanner) {
      track({
        eventCategory: 'Settle Now - Banner',
        eventAction: `Banner visible | ${user.current}`,
        eventLabel: 'Eligible Merchant | Banner visible',
      });
      hideCloseButton();
    }
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
  // eslint-disable-next-line react/no-unused-prop-types
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
  fetchProducts: bindActionCreators(fnFetchProducts, dispatch),
  getApplications: bindActionCreators(fnGetApplications, dispatch),
  fetchFunctionalWithdrawalConfigByMerchantID: bindActionCreators(
    fnFetchFunctionalWithdrawalConfigByMerchantID,
    dispatch,
  ),
});

export default withRouter(connect(mapStateToProps, mapDispatchToProps)(SettlementsUpsell));
