import React, { useState, useEffect } from 'react';
import { withRouter } from 'react-router-dom';
import { connect } from 'react-redux';

import Amount from 'common/ui/Amount';
import Button, { AsyncBtn } from 'common/new-ui/Button';
import Input from 'common/new-ui/Input';
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
  trackSettlementAmountUpdated,
} from '../TrackEvents/trackEvents';

function updateRepaymentData(data, onResolve, onReject) {
  const repayment = new Repayments();

  return repayment.updateRepayment(data).then(onResolve).catch(onReject);
}

const RepayMethod = ({
  merchantId,
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
  const [customAmountInput, setCustomAmountInput] = useState(settlementBalance.customAmount / 100);

  useEffect(() => {
    const amount = repayAmount > balance ? balance : repayAmount;
    setCustomAmountInput(amount / 100);
  }, [repayAmount, balance]);

  const isNextRepayableRepayType = repayType === REPAY_AMOUNT_TYPES.NEXT_REPAYABLE;
  const isTotalOwedRepayType = repayType === REPAY_AMOUNT_TYPES.TOTAL_OWED;

  const handleRepayClick = () => {
    const RepaymentInstance = new Repayments();
    const paymentParams = {
      // merchant_id: merchantId,
      credit_id: merchantId,
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
        const razorpayInstance = new Razorpay({
          order_id,
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

  const handleCustomAmountDoneClick = () => {
    setSettlementBalance({
      ...settlementBalance,
      isCustomAmountActive: false,
      amount: customAmountInput * 100,
    });
  };

  const handleEditClick = () => {
    setSettlementBalance({ ...settlementBalance, isCustomAmountActive: true, active: true });
    if (repayInputType === 'radio') {
      setBankBalance({ ...bankBalance, active: false });
    }
  };

  const handleCustomAmountChange = (event) => {
    let error = '';
    let errorStr;
    const value = parseInt(event.target.value);

    if (value < 10 || event.target.value === '') {
      errorStr = `Min. amount can be selected ₹ 10`;
      error = (
        <p>
          Min. amount can be selected <Amount value={1000} currency="INR" />
        </p>
      );
    } else if (value > repayAmount / 100) {
      errorStr = `Max. amount can be selected ₹ ${(repayAmount / 100).toFixed(2)}`;
      error = (
        <p>
          Max. amount can be selected <Amount value={repayAmount} currency="INR" />
        </p>
      );
    } else if (value > balance / 100) {
      errorStr = `Max. Available Balance is ₹ ${(balance / 100).toFixed(2)}`;
      error = (
        <p>
          Max. Available Balance is <Amount value={balance} currency="INR" />
        </p>
      );
    }
    trackSettlementAmountUpdated(pathname, {
      error: errorStr,
      amount: event.target.value,
    });
    setCustomAmountInput(event.target.value);
    setSettlementBalance({
      ...settlementBalance,
      error,
    });
  };

  const handleCustomAmountCloseClick = () => {
    setSettlementBalance({
      ...settlementBalance,
      customAmount: settlementBalance.amount,
      isCustomAmountActive: false,
      error: '',
    });
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
          }`}
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
            <div
              onClick={() => handleSettlementBalanceSelect(!settlementBalance.active)}
              className="repay--type-title cursor-pointer"
            >
              Settlement Balance
            </div>
            {settlementBalance.isCustomAmountActive ? (
              <div className="custom-input mt-8">
                <Input
                  addonBefore="₹"
                  type="number"
                  className="settlement-balance--input"
                  addonAfter={
                    <div className="settlement-balance--input-actions">
                      {!settlementBalance.error && (
                        <Button.Transparent className="mr-12" onClick={handleCustomAmountDoneClick}>
                          Done
                        </Button.Transparent>
                      )}
                      <span
                        className="settlement-balance--close"
                        onClick={handleCustomAmountCloseClick}
                      >
                        <i className="i i-close" />
                      </span>
                    </div>
                  }
                  name="amount"
                  value={customAmountInput}
                  onChange={handleCustomAmountChange}
                />
                <div className="text-danger error-message mt-5">
                  {settlementBalance.error && settlementBalance.error}
                </div>
              </div>
            ) : (
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
                    <Button.Transparent className="edit-btn" onClick={handleEditClick}>
                      Edit
                    </Button.Transparent>
                  </div>
                )}
              </div>
            )}
          </div>
        </div>
        <div
          className={`action mr-24 ${settlementBalance.isCustomAmountActive ? 'ml--1' : ''} ${
            bankBalance.active ? 'active' : ''
          } cursor-pointer ${settlementBalance.active ? 'bank__disable' : ''}`}
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
            disabled={
              settlementBalance.error || settlementBalance.isCustomAmountActive || repayAmount === 0
            }
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

export default withRouter(
  connect((state, ownProps) => {
    const {
      session: {
        user: { current: merchantId },
      },
    } = state;

    return {
      merchantId,
      ...ownProps,
    };
  })(RepayMethod),
);
