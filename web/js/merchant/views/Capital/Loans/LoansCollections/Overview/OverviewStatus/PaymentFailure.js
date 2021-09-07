import React from 'react';
import Button from 'common/new-ui/Button';
import { OVERVIEW_STATUS_VIEWS } from '../../constants';
import LoanStatusFooter from './LoanStatusFooter';
import PaymentPartial from './PaymentPartial';
import { useLoanData } from './PaymentContext';

export default function PaymentFailure({ setView, onRefresh }) {
  const {
    state: { successRepayments = [] },
  } = useLoanData();

  const onRetry = () => {
    setView(OVERVIEW_STATUS_VIEWS.PAYMENT_METHOD);
  };
  const onClose = () => {
    setView(OVERVIEW_STATUS_VIEWS.PAYMENT_AMOUNT);
  };

  if (successRepayments.length) return <PaymentPartial onRefresh={onRefresh} />;

  return (
    <div className="payment-failure">
      <div className="payment-failure-body">
        <div className="top">
          <div className="flex items-end">
            <i className="i i-info-alt text-xl text-danger" />
            <span className="text-xl leading-none text-navy-blue font-bold ml-8">
              Repayment Failure!
            </span>
          </div>
          <div className="text-content">
            <span className="text-sm text-navy-blue-o-80">
              Oops! Your repayment has been failed due to some internal error.
            </span>
            <span className="text-sm text-navy-blue-o-80">
              Incase if any money has been debited , it will be added back to your account within 1
              working day.
            </span>
          </div>
          <div className="actions">
            <Button.Primary onClick={onRetry}>Retry Repayment</Button.Primary>
            <Button.Transparent onClick={onClose}>Close</Button.Transparent>
          </div>
        </div>
      </div>
      <LoanStatusFooter icon="i i-info-alt text-teal">
        <p className="text-sm">
          Autopay from your settlement balance is the easiest way to make sure you never miss a
          payment!
        </p>
      </LoanStatusFooter>
    </div>
  );
}
