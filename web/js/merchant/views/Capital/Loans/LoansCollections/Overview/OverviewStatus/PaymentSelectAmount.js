import React, { useMemo, useState, useRef } from 'react';

import { rupeesToPaise, paiseToRupees } from 'common/utils/rzp-utils';
import Amount from 'common/ui/Amount';
import LoanStatusFooter from './LoanStatusFooter';
import Button from 'common/new-ui/Button';
import Input from 'common/new-ui/Input';

import { useLoanData } from './PaymentContext';
import { OVERVIEW_STATUS_VIEWS } from '../../constants';

const AMOUNT_TYPE = {
  OUTSTANDING: 'outstanding', // outstanding due amount
  CUSTOM: 'custom',
};

export const isCustomAmount = (outstanding, amountToPay) =>
  amountToPay > 0 && amountToPay < outstanding; // used to restore state when navigating to Payment Method

export default function PaymentSelectAmount({
  setView,
  settlementBalance: {
    data: { balance: primaryBalance = 0 },
  },
}) {
  const { state: loanData, dispatch } = useLoanData();
  const amountToBePaid = +loanData.amountToBePaid; // in paise
  const totalOutstandingDueToBePaid = loanData.totalDueInfo.amount;

  const [selectedAmountType, setSelectedAmountType] = useState(() =>
    isCustomAmount(totalOutstandingDueToBePaid, amountToBePaid)
      ? AMOUNT_TYPE.CUSTOM
      : AMOUNT_TYPE.OUTSTANDING,
  );
  const [customAmount, setCustomAmount] = useState(
    `${paiseToRupees(amountToBePaid || totalOutstandingDueToBePaid)}`,
  ); // in Rupees
  const showCustomAmountEditTextUI = useRef(selectedAmountType === AMOUNT_TYPE.CUSTOM); // used to decide when to show Enter Amout or ${customAmount} Edit UI

  const [showCustomAmountInput, setShowCustomAmountInput] = useState(false); // custom amount input field

  const customAmountInPaise = rupeesToPaise(customAmount);

  const onNext = () => {
    const amount =
      selectedAmountType === AMOUNT_TYPE.OUTSTANDING
        ? totalOutstandingDueToBePaid
        : customAmountInPaise;
    dispatch({ type: 'SET_PAYMENT_AMOUNT', payload: amount });
    setView(OVERVIEW_STATUS_VIEWS.PAYMENT_METHOD);
  };

  const onAmountTypeSelect = (e) => {
    const value = e.target.value;
    // hide custom amount field incase visible when toggling back to outstanding
    if (value === AMOUNT_TYPE.OUTSTANDING) {
      setShowCustomAmountInput(false);
    }

    setSelectedAmountType(value);
  };

  const toggleShowCustomAmountField = () => {
    showCustomAmountEditTextUI.current = true;
    setShowCustomAmountInput((prev) => !prev);
  };

  const onCancel = () => {
    setView(OVERVIEW_STATUS_VIEWS.PAYMENT_AMOUNT);
  };

  const onInputCustomAmount = (e) => {
    setCustomAmount(e.currentTarget.value);
  };

  const customAmountErrorMsg = useMemo(() => {
    if (selectedAmountType !== AMOUNT_TYPE.CUSTOM) return null;
    const value = customAmountInPaise;
    const minAmountInPaise = rupeesToPaise(1);
    if (value > totalOutstandingDueToBePaid)
      return (
        <p>
          Max. amount can be repaid <Amount value={totalOutstandingDueToBePaid} />
        </p>
      );
    if (isNaN(value) || value < minAmountInPaise)
      return (
        <p>
          Min. amount can be repaid <Amount value={minAmountInPaise} />
        </p>
      );
    return null;
  }, [totalOutstandingDueToBePaid, selectedAmountType, customAmountInPaise]);

  const customAmountInputUI = (
    <Input
      autoRender
      addonBefore="₹"
      type="number"
      className="custom-amount__input"
      addonAfter={
        !customAmountErrorMsg && (
          <div className="Button--transparent" onClick={toggleShowCustomAmountField}>
            Done
          </div>
        )
      }
      propagatedError={customAmountErrorMsg}
      name="amount"
      value={customAmount}
      onChange={onInputCustomAmount}
    />
  );

  const amountTypeSelectioRadioUI = (
    <Input.Radio
      name="payment_amount_type"
      autoRender
      className={
        selectedAmountType === AMOUNT_TYPE.OUTSTANDING
          ? 'payment-option-one-selected'
          : 'payment-option-two-selected' // workaround for custom styling since component doesnt have support for highlighting select item
      }
      options={[
        {
          label: (
            <div className="flex payment-option">
              <span className="text-sm">Total Due Amount</span>
              <Amount value={totalOutstandingDueToBePaid} />
            </div>
          ),
          value: AMOUNT_TYPE.OUTSTANDING,
        },
        {
          label: (
            <div className="flex payment-option custom-amount-label">
              <span className="text-sm">Custom</span>
              {!showCustomAmountInput ? (
                showCustomAmountEditTextUI.current ? (
                  <div className="flex" onClick={toggleShowCustomAmountField}>
                    <Amount value={customAmountInPaise} />{' '}
                    <div className="Button--transparent edit-btn">Edit amount</div>
                  </div>
                ) : (
                  <div className="Button--transparent" onClick={toggleShowCustomAmountField}>
                    Enter Amount
                  </div>
                )
              ) : (
                customAmountInputUI
              )}
            </div>
          ),
          value: AMOUNT_TYPE.CUSTOM,
        },
      ]}
      defaultValue={selectedAmountType}
      onChange={onAmountTypeSelect}
    />
  );

  const disableNextButton =
    !!customAmountErrorMsg ||
    (selectedAmountType === AMOUNT_TYPE.CUSTOM && !showCustomAmountEditTextUI.current); // disabling when Enter amount UI is visible bcuz user not interacted with custom amount input field yet but selected custom amount

  return (
    <div className="loan-payment-stepper loan-payment-stepper--select-amount">
      <nav className="loan-payment-stepper__nav">
        <span>
          Select Amount <span className="paginate ml-4">(1/2)</span>
        </span>
      </nav>
      <div className="flex loan-payment-stepper__body">
        <div className="flex left">
          <span className="text-navy-blue-o-70 font-bold">I want to repay</span>
          <div className="flex mt-8 footer text-xsm">{amountTypeSelectioRadioUI}</div>
        </div>
        <div className="flex right">
          <Button.Primary className="btn" onClick={onNext} disabled={disableNextButton}>
            Next
          </Button.Primary>
          <Button.Transparent className="cancel-button" onClick={onCancel}>
            Cancel
          </Button.Transparent>
        </div>
      </div>
      <LoanStatusFooter icon="i i-info-alt text-teal">
        <p className="text-sm">
          Available Settlement Balance <Amount value={primaryBalance} className="text-navy-blue" />
        </p>
      </LoanStatusFooter>
    </div>
  );
}
