import React, { useState } from 'react';
import Amount from 'common/ui/Amount';
import Spinner from 'common/ui/Spinner';
import Button from 'common/new-ui/Button';
import Input from 'common/new-ui/Input';
import { REPAYMENT_VIEWS, REPAY_AMOUNT_TYPES } from 'merchant/views/Capital/CashAdvance/constants';
import {
  getPrincipalAmount,
  getInterestAmount,
  handleDecimalFigure,
} from 'merchant/views/Capital/CashAdvance/OverviewFooter/utils';

const Loader = () => {
  return (
    <div className="page-spinner-container">
      <Spinner />
    </div>
  );
};

const RepayAmount = ({
  setView,
  customAmount,
  balance,
  currentOutstandingTotalAmount,
  totalOwedAmount,
  currentOutstandingInterestAmount,
  currentOutstandingPrincipalAmount,
  setRepayType,
  repayType,
  setCustomAmount,
  setRepayAmount,
  totalPrincipalAmount,
  totalInterestAmount,
  loading,
  currency,
  amountPendingToday,
}) => {
  const isCurrentOutstandingRepayType = repayType === REPAY_AMOUNT_TYPES.CURRENT_OUTSTANDING;
  const isTotalOwedRepayType = repayType === REPAY_AMOUNT_TYPES.TOTAL_OWED;
  const isCustomRepayType = repayType === REPAY_AMOUNT_TYPES.CUSTOM;
  const [customAmountError, setCustomAmountError] = useState('');
  const [tempCustomAmount, setTempCustomAmount] = useState(
    customAmount ? Math.round(customAmount / 100) : null,
  );
  const [isCustomAmountActive, setIsCustomAmountActive] = useState(false);

  const handleConfirmClick = () => {
    setView(REPAYMENT_VIEWS.REPAY_METHOD);
  };

  const handleEnterAmountClick = () => {
    if (!isCustomRepayType) setRepayType(REPAY_AMOUNT_TYPES.CUSTOM);
    setIsCustomAmountActive(true);
  };

  const handleRadioSelect = (type, amount) => {
    setRepayType(type);
    if (type === REPAY_AMOUNT_TYPES.CUSTOM) {
      setIsCustomAmountActive(true);
    } else {
      setIsCustomAmountActive(false);
    }
    setRepayAmount(amount);
  };

  const handleEditClick = () => {
    setTempCustomAmount(customAmount / 100);
    if (!isCustomRepayType) setRepayType(REPAY_AMOUNT_TYPES.CUSTOM);
    setIsCustomAmountActive(true);
  };

  const handleCustomAmountChange = (e) => {
    const value = parseInt(e.currentTarget.value, 10);
    if (value > totalOwedAmount / 100)
      setCustomAmountError(
        <p>
          Max. amount can be repaid <Amount currency={currency} value={totalOwedAmount} />
        </p>,
      );
    else if (value < 10)
      setCustomAmountError(
        <p>
          Min. amount can be repaid <Amount currency={currency} value={1000} />
        </p>,
      );
    else {
      setCustomAmountError('');
    }
    setTempCustomAmount(e.currentTarget.value);
  };

  const handleCustomAmountCloseClick = () => {
    setIsCustomAmountActive(false);
    setTempCustomAmount(customAmount / 100);
    setCustomAmountError('');
  };

  const handleCustomAmountDoneClick = () => {
    setIsCustomAmountActive(false);
    const value = handleDecimalFigure({
      tempCustomAmount,
      amountPendingToday,
      totalOwedAmount,
    });
    // All new installments will be in Rs and we don't want paisa to be attributed to them.
    // We also have to collect paisa for older plans so by restricting paisa figure,
    // we will attribute paisa to older plans and new plans will only get amount in Rs.
    setCustomAmount(value);
    setRepayAmount(value);
    if (!isCustomRepayType) setRepayType(REPAY_AMOUNT_TYPES.CUSTOM);
  };

  const principalAmount = getPrincipalAmount({
    isCurrentOutstandingRepayType,
    currentOutstandingPrincipalAmount,
    isTotalOwedRepayType,
    totalPrincipalAmount,
  });
  const interestAmount = getInterestAmount({
    isCurrentOutstandingRepayType,
    currentOutstandingInterestAmount,
    isTotalOwedRepayType,
    totalInterestAmount,
  });

  const isRepayCTADisable =
    (repayType === REPAY_AMOUNT_TYPES.CUSTOM &&
      (customAmountError || !tempCustomAmount || isCustomAmountActive)) ||
    totalOwedAmount === 0;

  if (loading) {
    return (
      <div className="repay-container repay-amount repay">
        <Loader />
      </div>
    );
  }
  return (
    <div className="repay-container repay-amount repay">
      <div className="repay-text">I want to repay</div>
      <div className="flex repay-actions">
        <div
          className={`action repay-actions-box ${
            repayType === REPAY_AMOUNT_TYPES.CURRENT_OUTSTANDING ? 'active' : ''
          } cursor-pointer`}
          onClick={() =>
            handleRadioSelect(REPAY_AMOUNT_TYPES.CURRENT_OUTSTANDING, currentOutstandingTotalAmount)
          }
        >
          <div className="mr-7">
            <input
              type="radio"
              key={REPAY_AMOUNT_TYPES.CURRENT_OUTSTANDING}
              value={REPAY_AMOUNT_TYPES.CURRENT_OUTSTANDING}
              checked={repayType === REPAY_AMOUNT_TYPES.CURRENT_OUTSTANDING}
            />
          </div>
          <div>
            <div className="repay--type-title mb-4">Current Outstanding Amount</div>
            <div className="mt-4">
              <Amount
                className="repay--amount"
                currency={currency}
                value={currentOutstandingTotalAmount}
              />
            </div>
          </div>
        </div>
        <div
          className={`action repay-actions-box ml--1 ${
            repayType === REPAY_AMOUNT_TYPES.TOTAL_OWED ? 'active' : ''
          } cursor-pointer`}
          onClick={() => handleRadioSelect(REPAY_AMOUNT_TYPES.TOTAL_OWED, totalOwedAmount)}
        >
          <div className="mr-7">
            <input
              type="radio"
              key={REPAY_AMOUNT_TYPES.TOTAL_OWED}
              value={REPAY_AMOUNT_TYPES.TOTAL_OWED}
              checked={repayType === REPAY_AMOUNT_TYPES.TOTAL_OWED}
            />
          </div>
          <div>
            <div className="repay--type-title">Total Outstanding Amount</div>
            <div className="mt-4">
              <Amount className="repay--amount" currency={currency} value={totalOwedAmount} />
            </div>
          </div>
        </div>
        <div
          className={`action repay-actions-box ml--1 mr-24 ${
            repayType === REPAY_AMOUNT_TYPES.CUSTOM && !customAmountError ? 'active' : ''
          } ${repayType === REPAY_AMOUNT_TYPES.CUSTOM && customAmountError ? 'error' : ''}`}
        >
          <div className="mr-7">
            <input
              type="radio"
              key={REPAY_AMOUNT_TYPES.CUSTOM}
              value={REPAY_AMOUNT_TYPES.CUSTOM}
              onChange={() => handleRadioSelect(REPAY_AMOUNT_TYPES.CUSTOM, customAmount)}
              checked={repayType === REPAY_AMOUNT_TYPES.CUSTOM}
            />
          </div>
          <div>
            <div
              className="repay--type-title cursor-pointer"
              onClick={() => handleRadioSelect(REPAY_AMOUNT_TYPES.CUSTOM, customAmount)}
            >
              Custom
            </div>
            {isCustomAmountActive ? (
              <div className="custom-input mt-8">
                <Input
                  addonBefore="₹"
                  type="number"
                  className="settlement-balance--input"
                  addonAfter={
                    <div className="settlement-balance--input-actions">
                      {!customAmountError && tempCustomAmount && (
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
                  value={tempCustomAmount}
                  onChange={handleCustomAmountChange}
                />

                <div className="text-danger error-message mt-5">
                  {customAmountError && customAmountError}
                </div>
              </div>
            ) : (
              <div className="mt-4">
                {customAmount !== 0 && !customAmount ? (
                  <Button.Transparent onClick={handleEnterAmountClick}>
                    Enter Amount
                  </Button.Transparent>
                ) : (
                  <div>
                    <Amount className="repay--amount" currency={currency} value={customAmount} />
                    <Button.Transparent className="edit-btn" onClick={handleEditClick}>
                      Edit
                    </Button.Transparent>
                  </div>
                )}
              </div>
            )}
          </div>
        </div>
        <div>
          <div className="flex wrapper-alignment">
            <Button.Secondary
              disabled={isRepayCTADisable}
              className="mr-24"
              onClick={handleConfirmClick}
            >
              <strong>Repay</strong>
            </Button.Secondary>
          </div>
        </div>
      </div>
      <div className="flex wrapper-alignment">
        {!isCustomRepayType && (
          <div className="flex">
            <div className="principal">
              <div className="amount--title">Principal</div>
              <Amount className="amount" currency={currency} value={principalAmount} />
            </div>
            <div>
              <div className="amount--title">Interest</div>
              <Amount className="amount" currency={currency} value={interestAmount} />
            </div>
          </div>
        )}
        {balance === 0 && (
          <div className="hint flex">
            <i className="i i-info-outline mr-4 mt-2" />
            <p>Amount can be repaid using Netbanking/UPI</p>
          </div>
        )}
        {isCustomRepayType && (
          <div className="hint flex">
            <i className="i i-info-outline mr-4 mt-2" />
            <p>Decimal figure will be auto-adjusted</p>
          </div>
        )}
      </div>
    </div>
  );
};

export default RepayAmount;
