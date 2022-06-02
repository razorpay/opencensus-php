import React, { useState } from 'react';
import Amount from 'common/ui/Amount';
import Button from 'common/new-ui/Button';
import Input from 'common/new-ui/Input';
import { REPAYMENT_VIEWS, REPAY_AMOUNT_TYPES } from '../constants';
import { getPrincipalAmount, getInterestAmount } from './utils';

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
}) => {
  const isCurrentOutstandingRepayType = repayType === REPAY_AMOUNT_TYPES.CURRENT_OUTSTANDING;
  const isTotalOwedRepayType = repayType === REPAY_AMOUNT_TYPES.TOTAL_OWED;
  const isCustomRepayType = repayType === REPAY_AMOUNT_TYPES.CUSTOM;

  const [customAmountError, setCustomAmountError] = useState('');
  const [tempCustomAmount, setTempCustomAmount] = useState(
    customAmount ? Math.round(customAmount / 100) : null,
  );
  const [isCustomAmountActive, setIsCustomAmountActive] = useState(false);

  const handleCancelClick = () => {
    setView(REPAYMENT_VIEWS.SUMMARY);
  };

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
    if (!isCustomRepayType) setRepayType(REPAY_AMOUNT_TYPES.CUSTOM);
    setIsCustomAmountActive(true);
  };

  const handleCustomAmountChange = (e) => {
    const value = parseInt(e.currentTarget.value, 10);
    if (value > totalOwedAmount / 100)
      setCustomAmountError(
        <p>
          Max. amount can be repaid <Amount currency="INR" value={totalOwedAmount} />
        </p>,
      );
    else if (value < 10)
      setCustomAmountError(
        <p>
          Min. amount can be repaid <Amount currency="INR" value={1000} />
        </p>,
      );
    else {
      setCustomAmountError('');
    }
    setTempCustomAmount(e.currentTarget.value);
  };

  const handleCustomAmountCloseClick = () => {
    setIsCustomAmountActive(false);
    setTempCustomAmount(customAmount);
    setCustomAmountError('');
  };

  const handleCustomAmountDoneClick = () => {
    setIsCustomAmountActive(false);
    setCustomAmount(Math.round(100 * tempCustomAmount));
    setRepayAmount(Math.round(100 * tempCustomAmount));
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
                currency="INR"
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
              <Amount className="repay--amount" currency="INR" value={totalOwedAmount} />
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
                    <Amount className="repay--amount" currency="INR" value={customAmount} />
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
            <Button.Primary
              disabled={
                repayType === REPAY_AMOUNT_TYPES.CUSTOM &&
                (customAmountError || !tempCustomAmount || isCustomAmountActive)
              }
              className="mr-24"
              onClick={handleConfirmClick}
            >
              Confirm
            </Button.Primary>
            <Button.Transparent onClick={handleCancelClick}>Cancel</Button.Transparent>
          </div>
        </div>
      </div>
      <div className="flex wrapper-alignment">
        {!isCustomRepayType && (
          <div className="flex">
            <div className="principal">
              <div className="amount--title">Principal</div>
              <Amount className="amount" currency="INR" value={principalAmount} />
            </div>
            <div>
              <div className="amount--title">Interest</div>
              <Amount className="amount" currency="INR" value={interestAmount} />
            </div>
          </div>
        )}
        {balance === 0 && (
          <div className="hint flex">
            <i className="i i-info-outline mr-4 mt-2" />
            <p>Amount can be repaid using Netbanking/UPI</p>
          </div>
        )}
      </div>
    </div>
  );
};

export default RepayAmount;
