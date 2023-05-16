import React from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import lazy from 'merchant/routes/LazyLoader';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';

//redux actions
import { showNotification } from 'merchant_common/reducers/notifications';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import {
  fetchAccountBalance,
  createPayout,
  createPayoutPending,
  createPayoutSuccess,
  fetchBeneficiaryDetailsSuccess as fetchBeneficiaryDetailsSuccessAction,
} from 'merchant/reducers/b2bExports/actions';

//analytics
import {
  trackCheckBalanceClicked,
  trackCheckBalanceFailed,
  trackSubmitPayoutRequest,
  trackSubmitPayoutSuccess,
  trackSubmitPayoutFailed,
  trackWithdrawClicked,
} from 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/analytics';

//types
import { AccountBalancePropsInterface } from 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/types';

//components
import { Button } from '@razorpay/blade/components';

const GlobalBankWithdrawModal = lazy(
  () =>
    import(
      /* webpackChunkName: "GlobalBankWithdrawModal" */ 'merchant/views/Settings/PaymentMethods/components/GlobalBankWithdrawModal'
    ),
);

const ConfirmWithdrawalModal = lazy(
  () =>
    import(
      /* webpackChunkName: "ConfirmWithdrawalModal" */ 'merchant/views/Settings/PaymentMethods/components/GlobalBankWithdrawModal/ConfirmWithdrawalModal'
    ),
);

const StatusModal = lazy(
  () =>
    import(
      /* webpackChunkName: "StatusModal" */ 'merchant/views/Settings/PaymentMethods/components/GlobalBankWithdrawModal/StatusModal'
    ),
);

const AccountBalance: React.FC<AccountBalancePropsInterface> = ({
  vaCurrency,
  isLoading,
  accountBalance,
  fetchAccountBalance,
  showNotification,
  openModal,
  closeModal,
  createPayoutPending,
  createPayoutSuccess,
  fetchBeneficiaryDetailsSuccess,
}) => {
  const closeStatusModal = () => {
    closeModal();
    fetchBeneficiaryDetailsSuccess({
      amount: '',
      reason: '',
    });
  };

  const openWithdrawModal = (currency) => {
    openModal({
      size: 'large',
      component: (
        <SuspenseWithLoader>
          <GlobalBankWithdrawModal
            currency={currency}
            onClose={closeModal}
            balance={accountBalance?.[currency]?.balance}
            onSubmit={openConfirmWithdrawalModal}
          />
        </SuspenseWithLoader>
      ),
    });
    trackWithdrawClicked();
  };

  const openStatusModal = (type) => {
    openModal({
      size: 'medium',
      component: (
        <SuspenseWithLoader>
          <StatusModal type={type} onClose={closeStatusModal} onTryAgain={openWithdrawModal} />
        </SuspenseWithLoader>
      ),
    });
  };

  const submitForPayout = async ({ amount, reason, currency }) => {
    try {
      createPayoutPending();
      trackSubmitPayoutRequest();
      const response = await createPayout({
        amount,
        reason,
        currency,
      });
      if (response.success) {
        openStatusModal('success');
        trackSubmitPayoutSuccess();
      } else {
        throw Error('Failed');
      }
    } catch ({ errors, message }) {
      if (Array.isArray(errors)) {
        showNotification({
          type: 'error',
          message: errors[0],
        });
      } else {
        showNotification({
          type: 'error',
          message,
        });
      }
      openStatusModal('error');
      trackSubmitPayoutFailed();
    } finally {
      createPayoutSuccess();
    }
  };

  function openConfirmWithdrawalModal(beneDetails) {
    openModal({
      size: 'medium',
      component: (
        <SuspenseWithLoader>
          <ConfirmWithdrawalModal
            onClose={closeModal}
            onSubmit={() => submitForPayout(beneDetails)}
          />
        </SuspenseWithLoader>
      ),
    });
  }

  const getBalance = async (currency) => {
    try {
      trackCheckBalanceClicked();
      await fetchAccountBalance(currency);
    } catch {
      showNotification({
        type: 'error',
        message: 'Failed to check account balance. Try again after sometime.',
      });
      trackCheckBalanceFailed();
    }
  };

  return (
    <div className="account-balance-container" data-testid="account-balance-container">
      {accountBalance?.[vaCurrency] ? (
        <div className="list-item">
          <div>
            <p className="info">Available Balance</p>
            <p>$ {accountBalance[vaCurrency]?.amount}</p>
          </div>
          <div className="list-cta">
            <Button
              variant="tertiary"
              isLoading={isLoading}
              onClick={() => getBalance(vaCurrency)}
              size="small"
              isFullWidth
            >
              Refresh
            </Button>
          </div>
        </div>
      ) : (
        <div className="list-item">
          <p className="info">Check the available balance in your bank account.</p>
          <div className="list-cta">
            <Button
              variant="primary"
              isLoading={isLoading}
              size="small"
              isFullWidth
              onClick={() => getBalance(vaCurrency)}
            >
              Check Balance
            </Button>
          </div>
        </div>
      )}
      <div className="list-item">
        <p className="info">Withdraw funds from your account</p>
        <div className="list-cta">
          <Button
            variant="primary"
            size="small"
            isFullWidth
            onClick={() => openWithdrawModal(vaCurrency)}
          >
            Withdraw Money
          </Button>
        </div>
      </div>
    </div>
  );
};

const mapStateToProps = (state) => ({
  isLoading: state.b2bExportsAccountBalance.isLoading,
  accountBalance: state.b2bExportsAccountBalance.data,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      fetchAccountBalance,
      showNotification,
      openModal,
      closeModal,
      createPayoutPending,
      createPayoutSuccess,
      fetchBeneficiaryDetailsSuccess: fetchBeneficiaryDetailsSuccessAction,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(AccountBalance);
