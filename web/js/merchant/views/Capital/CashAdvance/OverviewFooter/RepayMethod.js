import React from 'react';
import { withRouter } from 'react-router-dom';
import { connect } from 'react-redux';

import Amount from 'common/ui/Amount';
import Button, { AsyncBtn } from 'common/new-ui/Button';
import Repayments from 'merchant/models/Capital/Repayments';
import { loadCheckoutScript } from '../../utils/index';
import {
  REPAYMENT_VIEWS,
  REPAY_METHOD_TYPES,
  COLLECTIONS_PAYMENT_REFERENCE_TYPE,
  COLLECTIONS_PRODUCT_TYPES,
  REPAY_AMOUNT_TYPES,
  PAYMENT_MODES,
} from '../constants';
import { getPrincipalAmount, getInterestAmount } from './utils';
import {
  trackChangeAmount,
  trackCheckoutFlowCancel,
  trackCheckoutFlowSuccess,
  trackRepayCancel,
  trackRepayConfirm,
} from '../TrackEvents/trackEvents';

function updateRepaymentData(data, onResolve, onReject) {
  const repayment = new Repayments();

  return repayment.updateRepayment(data).then(onResolve).catch(onReject);
}

const RepayMethod = ({
  user,
  setView,
  repayAmount,
  balance,
  setSettlementBalance,
  settlementBalance,
  setBankBalance,
  bankBalance,
  repayInputType,
  isSettlementBalanceLessThanRepayAmount,
  setResultAmounts,
  nextRepayInterestAmount,
  nextRepayPrincipalAmount,
  totalPrincipalAmount,
  totalInterestAmount,
  repayType,
  location: { pathname = '' },
}) => {
  const isNextRepayableRepayType = repayType === REPAY_AMOUNT_TYPES.NEXT_REPAYABLE;
  const isTotalOwedRepayType = repayType === REPAY_AMOUNT_TYPES.TOTAL_OWED;

  const handleRazorpayCheckoutPayment = (RepaymentInstance, paymentParams) => {
    const requests = [
      loadCheckoutScript(),
      RepaymentInstance.createRepayment({
        ...paymentParams,
        payment_reference_type: COLLECTIONS_PAYMENT_REFERENCE_TYPE.ORDER,
        amount: Number(bankBalance.amount),
      }),
    ];

    return Promise.all(requests).then(([_, repaymentDetails]) => {
      const { data: { payment_reference_id: order_id } = {} } = repaymentDetails;
      if (!order_id) return Promise.reject(new Error('No Order Id found'));

      return new Promise((resolve, reject) => {
        const razorpayInstance = new window.Razorpay({
          order_id,
          prefill: {
            name: user.name,
            email: user.email,
            contact: user.contact_mobile,
          },
          handler: (response) => {
            trackCheckoutFlowSuccess(pathname);
            updateRepaymentData(response, resolve, reject);
          },
          modal: {
            ondismiss: () => {
              trackCheckoutFlowCancel(pathname, repayAmount);
              reject();
            },
          },
        });
        razorpayInstance.open();
      });
    });
  };

  const handleRepayClick = () => {
    const RepaymentInstance = new Repayments();
    const paymentParams = {
      credit_id: user.current,
      product_type: COLLECTIONS_PRODUCT_TYPES.CASH_ADVANCE,
      currency: 'INR',
    };
    const requests = [];
    const isSettlementActiveAndHasBalance = !!(
      settlementBalance &&
      settlementBalance.active &&
      settlementBalance.amount
    );
    const isBankBalanceActiveAndHasBalance = !!(
      bankBalance &&
      bankBalance.active &&
      bankBalance.amount
    );

    trackRepayConfirm(pathname, {
      repayAmount,
      nextRepayInterestAmount,
      nextRepayPrincipalAmount,
      totalPrincipalAmount,
      totalInterestAmount,
      repayType,
      isSettlementActiveAndHasBalance,
      isBankBalanceActiveAndHasBalance,
      settlementBalance,
      bankBalance,
    });

    if (isSettlementActiveAndHasBalance) {
      requests.push(() =>
        RepaymentInstance.createRepayment({
          ...paymentParams,
          payment_reference_type: COLLECTIONS_PAYMENT_REFERENCE_TYPE.CREDIT_REPAYMENT,
          amount: Number(settlementBalance.amount),
        }),
      );
    }

    if (isBankBalanceActiveAndHasBalance) {
      requests.push(() => handleRazorpayCheckoutPayment(RepaymentInstance, paymentParams));
    }
    let userRepayMethod = '';
    return requests
      .reduce((acc, currentAction) => {
        return acc.then(currentAction);
      }, Promise.resolve())
      .then(({ data }) => {
        if (data.payment_mode === PAYMENT_MODES.MANUAL && data.payment_meta)
          userRepayMethod = data.payment_meta.method || '';
        setView(REPAYMENT_VIEWS.RESULT_SUCCESS);
      })
      .catch(() => {
        setView(REPAYMENT_VIEWS.RESULT_FAILURE);
      })
      .finally(() => {
        const principalAmount = getPrincipalAmount({
          isNextRepayableRepayType,
          nextRepayPrincipalAmount,
          isTotalOwedRepayType,
          totalPrincipalAmount,
        });
        const interestAmount = getInterestAmount({
          isNextRepayableRepayType,
          nextRepayInterestAmount,
          isTotalOwedRepayType,
          totalInterestAmount,
        });
        setResultAmounts({
          settlementAmount: settlementBalance.active ? settlementBalance.amount : 0,
          bankAmount: bankBalance.active ? bankBalance.amount : 0,
          repayAmount,
          remainingSettlementBalance: settlementBalance.active
            ? balance - settlementBalance.amount
            : balance,
          principalAmount,
          interestAmount,
          userRepayMethod,
        });
      });
  };

  const handleCancelClick = () => {
    const isSettlementActiveAndHasBalance = !!(
      settlementBalance &&
      settlementBalance.active &&
      settlementBalance.amount
    );
    const isBankBalanceActiveAndHasBalance = !!(
      bankBalance &&
      bankBalance.active &&
      bankBalance.amount
    );
    trackRepayCancel(pathname, {
      repayAmount,
      nextRepayInterestAmount,
      nextRepayPrincipalAmount,
      totalPrincipalAmount,
      totalInterestAmount,
      repayType,
      isSettlementActiveAndHasBalance,
      isBankBalanceActiveAndHasBalance,
      settlementBalance,
      bankBalance,
    });
    setView(REPAYMENT_VIEWS.SUMMARY);
  };

  const handleChangeAmountClick = () => {
    trackChangeAmount();
    setView(REPAYMENT_VIEWS.REPAY_AMOUNT);
  };

  const handleSettlementBalanceSelect = (active) => {
    if (repayInputType === 'radio') {
      setBankBalance({ ...bankBalance, active: false });
    }
    setSettlementBalance({
      ...settlementBalance,
      active: repayInputType === 'radio' ? true : active,
      isCustomAmountActive: active && repayInputType === 'checkbox',
    });
  };

  const handleBankSelect = (active) => {
    if (repayInputType === 'radio') {
      setSettlementBalance({ ...settlementBalance, active: false });
      setBankBalance({ ...bankBalance, active: true });
    } else setBankBalance({ ...bankBalance, active });
  };

  const isBalanceZero = balance === 0;

  return (
    <div className="repay-container repay-method repay">
      <div className="repay-text">I want to repay using</div>
      <div className="flex repay-actions">
        <div
          className={`action ml--1 ${settlementBalance.isCustomAmountActive ? '' : 'mr-24'} ${
            settlementBalance.active && !settlementBalance.error ? 'active' : ''
          } ${settlementBalance.active && settlementBalance.error ? 'error' : ''} ${
            balance === 0 ? 'disabled' : ''
          } cursor-pointer`}
          onClick={() => handleSettlementBalanceSelect(!settlementBalance.active)}
        >
          <div className="mr-7">
            <input
              type={repayInputType}
              key={REPAY_METHOD_TYPES.SETTLEMENT_BALANCE}
              value={REPAY_METHOD_TYPES.SETTLEMENT_BALANCE}
              checked={settlementBalance.active}
              onChange={(e) => handleSettlementBalanceSelect(e.target.checked)}
            />
          </div>
          <div>
            <div className="repay--type-title">Settlement Balance</div>
            <div className="mt-4">
              {balance === 0 ? (
                <div className="repay--type-description">
                  Not Available, As balance is{' '}
                  <Amount
                    className="repay--amount"
                    currency="INR"
                    value={settlementBalance.amount}
                  />
                </div>
              ) : (
                <div className="repay--type-description">
                  Use{' '}
                  <Amount
                    className="repay--amount"
                    currency="INR"
                    value={settlementBalance.amount}
                  />{' '}
                  from balance.
                </div>
              )}
            </div>
          </div>
        </div>
        <div
          className={`action mr-24 ${settlementBalance.isCustomAmountActive ? 'ml--1' : ''} ${
            bankBalance.active ? 'active' : ''
          } ${
            (isSettlementBalanceLessThanRepayAmount && repayInputType === 'checkbox') ||
            balance === 0
              ? 'bank__disable'
              : ''
          } cursor-pointer`}
          onClick={() => handleBankSelect(!bankBalance.active)}
        >
          <div className="mr-7">
            <input
              type={repayInputType}
              key={REPAY_METHOD_TYPES.BANK}
              value={REPAY_METHOD_TYPES.BANK}
              checked={bankBalance.active}
            />
          </div>
          <div>
            <div className="repay--type-title mb-4">Netbanking / UPI</div>
            <div className="mt-4 repay--type-description">
              {balance === 0 || !bankBalance.active || !settlementBalance.active ? (
                <p>Using Razorpay Checkout</p>
              ) : (
                <p>
                  Remaining{' '}
                  <Amount className="repay--amount" currency="INR" value={bankBalance.amount} />{' '}
                  will repay using
                </p>
              )}
            </div>
          </div>
        </div>
        <div>
          <AsyncBtn.Primary
            disabled={settlementBalance.error || repayAmount === 0}
            className="btn btn-primary mr-24"
            onClick={handleRepayClick}
          >
            Repay
          </AsyncBtn.Primary>
          <Button.Transparent onClick={handleCancelClick}>Cancel</Button.Transparent>
        </div>
      </div>
      {isBalanceZero || !isSettlementBalanceLessThanRepayAmount ? (
        <div>
          Amount to be repaid{' '}
          <Amount className="repay--amount" currency="INR" value={repayAmount} />
        </div>
      ) : (
        <div>
          <Amount className="repay--amount" currency="INR" value={repayAmount} /> will be the
          Repayable amount
        </div>
      )}
      {!settlementBalance.isCustomAmountActive && (
        <div>
          <Button.Transparent onClick={handleChangeAmountClick}>Change Amount</Button.Transparent>
        </div>
      )}
    </div>
  );
};

const mapStateToProps = (state, ownProps) => ({
  user: state.session.user,
  ...ownProps,
});

export default withRouter(connect(mapStateToProps)(RepayMethod));
