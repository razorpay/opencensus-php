import React, { useState, useEffect } from 'react';
import { useLoanData } from './PaymentContext';
import Amount from 'common/ui/Amount';
import Button, { AsyncBtn } from 'common/new-ui/Button';
import { OVERVIEW_STATUS_VIEWS } from '../../constants';
import { handleRepayment } from './paymentUtil';
import Input from 'common/new-ui/Input';
import LoanStatusFooter from './LoanStatusFooter';
import { NOOP } from 'merchant/views/Capital/Loans/constants';

function PaymentMethod({
  setView,
  settlementBalance: {
    data: { balance: primaryBalance = 0 },
  },
  allowCustomAmountRepayment,
}) {
  const { state: loanData, dispatch } = useLoanData();
  const amountToBePaid = Number(loanData.amountToBePaid);
  const usableAmount = Math.min(primaryBalance, amountToBePaid);
  const [viaBalance, setViaBalance] = useState(usableAmount || 0);
  const [viaNetbanking, setViaNetbanking] = useState(amountToBePaid - viaBalance);

  const payload = {
    viaBalance,
    viaNetbanking,
  };

  useEffect(() => {
    if (!primaryBalance) {
      const inputRadio = document.querySelector(
        '.loan-payment-stepper--select-method input[name="payment-method"][value="0"]',
      );
      inputRadio && (inputRadio.disabled = true);
    }
  }, [primaryBalance]);

  const onRepayNow = () => {
    return handleRepayment(payload, loanData, dispatch)
      .catch(NOOP)
      .finally(() => {
        setView(OVERVIEW_STATUS_VIEWS.PAYMENT_RESULT);
      });
  };

  const onCancel = () => {
    setView(OVERVIEW_STATUS_VIEWS.PAYMENT_AMOUNT);
  };

  const onSingleSelect = (e) => {
    if (e.target.value === '0') {
      setViaBalance(usableAmount);
      setViaNetbanking(0);
    } else {
      setViaBalance(0);
      setViaNetbanking(amountToBePaid);
    }
  };

  const onMultiSelect = (type, e) => {
    switch (type) {
      default:
      case 'settlement_balance':
        setViaBalance(e.target.checked ? usableAmount : 0);
        if (viaNetbanking) {
          setViaNetbanking(e.target.checked ? amountToBePaid - usableAmount : amountToBePaid);
        }
        break;
      case 'netbanking':
        setViaNetbanking(e.target.checked ? amountToBePaid - viaBalance : 0);
        break;
    }
  };

  const renderRadio = () => {
    return (
      <Input.Radio
        name="payment-method"
        className={
          viaBalance ? 'payment-option-one-selected' : 'payment-option-two-selected' // workaround for custom styling since component doesnt have support for highlighting select item
        }
        options={[
          {
            label: (
              <div className="flex payment-option">
                <span className="text-sm">Settlement Balance</span>
                <span className="text-xs">
                  {primaryBalance ? (
                    <>
                      Use <Amount value={usableAmount} /> from balance
                    </>
                  ) : (
                    <>
                      Not Available, As balance is <Amount value={usableAmount} />{' '}
                    </>
                  )}
                </span>
              </div>
            ),
            value: 0,
            disabled: !primaryBalance, // not support in the component, workaround in useEffect.
          },
          {
            label: (
              <div className="flex payment-option">
                <span className="text-sm">Netbanking/UPI</span>
                <span className="text-xs">
                  {viaNetbanking ? (
                    <>
                      Pay <Amount value={viaNetbanking} className="font-bold text-navy-blue" />
                    </>
                  ) : (
                    'Using Razorpay Checkout'
                  )}
                </span>
              </div>
            ),
            value: 1,
          },
        ]}
        defaultValue={viaBalance ? 0 : 1}
        onChange={onSingleSelect}
      />
    );
  };

  const renderCheckbox = () => {
    return (
      <React.Fragment key={`${viaBalance}_${viaNetbanking}`}>
        <Input.Check
          defaultValue={viaBalance}
          name="settlement_balance"
          className={viaBalance && 'checkbox--selected'}
          fieldLabel={
            <div className="flex payment-option">
              <span className="text-sm">Settlement Balance</span>
              <span className="text-xs">
                Use <Amount value={usableAmount} /> from balance
              </span>
            </div>
          }
          onChange={onMultiSelect.bind(null, 'settlement_balance')}
          disabled={!primaryBalance}
        />
        <Input.Check
          defaultValue={viaNetbanking}
          name="netbanking"
          className={viaNetbanking && 'checkbox--selected'}
          fieldLabel={
            <div className="flex payment-option">
              <span className="text-sm">Netbanking/UPI</span>
              <span className="text-xs">
                {viaNetbanking ? (
                  <>
                    Remaining <Amount value={viaNetbanking} className="font-bold text-navy-blue" />
                  </>
                ) : (
                  'Using Razorpay Checkout'
                )}
              </span>
            </div>
          }
          onChange={onMultiSelect.bind(null, 'netbanking')}
        />
      </React.Fragment>
    );
  };

  const onBack = () => {
    setView(OVERVIEW_STATUS_VIEWS.PAYMENT_SELECT_AMOUNT);
  };

  return (
    <div
      className={`loan-payment-stepper loan-payment-stepper--select-method ${
        !allowCustomAmountRepayment ? 'disabled-custom-amount-feat' : ''
      }`}
    >
      {allowCustomAmountRepayment && (
        <nav className="loan-payment-stepper__nav">
          <i className="i i-arrow-back cursor-pointer mr-16" onClick={onBack} />
          <span>
            Payment Method <span className="paginate ml-4">(2/2)</span>
          </span>
        </nav>
      )}
      <div className="flex loan-payment-stepper__body">
        <div className="flex left">
          <span className="text-navy-blue-o-70 font-bold">I want to pay using</span>
          <div className="flex mt-8 footer text-xsm">
            {!primaryBalance || primaryBalance >= amountToBePaid ? renderRadio() : renderCheckbox()}
          </div>
        </div>
        <div className="flex right">
          <AsyncBtn.Primary
            onClick={onRepayNow}
            disabled={viaNetbanking + viaBalance < amountToBePaid}
          >
            Repay
          </AsyncBtn.Primary>
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

export default PaymentMethod;
