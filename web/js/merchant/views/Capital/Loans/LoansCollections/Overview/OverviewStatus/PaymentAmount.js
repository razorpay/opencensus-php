import React from 'react';
import LoanStatusHeader from './LoanStatusHeader';
import LoanStatusFooter from './LoanStatusFooter';
import Amount from 'common/ui/Amount';
import Button from 'common/new-ui/Button';
import Popover, { PopoverBody } from 'common/ui/Popover';
import { DATE_FORMATS, INSTALLMENT_STATUS, OVERVIEW_STATUS_VIEWS } from '../../constants';
import moment from 'moment';
import { findFrom, isPastDate, isRepaymentSuccess } from '../../util';
import { useLoanData, ACTIONS } from './PaymentContext';

const { PENDING, PARTIALLY_PAID, CREATED } = INSTALLMENT_STATUS;

function getLoanFooterText(
  repayment,
  upcomingPayment,
  amountExpectedTillNow = 0,
  collectedAmount = 0,
) {
  const isPaymentDue = upcomingPayment && isPastDate(upcomingPayment.date * 1000);
  const pendingPayment = isPaymentDue && collectedAmount < amountExpectedTillNow;
  const pendingAmount = amountExpectedTillNow - collectedAmount;
  if (pendingPayment) {
    return {
      icon: 'i i-triangle-alert',
      color: 'danger',
      content: (
        <p className="text-sm">
          You have a pending payment of <Amount value={pendingAmount} />. Please keep sufficient
          funds in your settlement balance.
        </p>
      ),
    };
  }
  if (repayment && isRepaymentSuccess(repayment)) {
    return {
      icon: 'i i-success-icon',
      color: 'success',
      content: (
        <p className="text-sm">
          Your last repayment of <Amount value={repayment.amount} /> was successfully received on{' '}
          <strong>{moment(repayment.created_at * 1000).format(DATE_FORMATS.LOAN_DISBURSAL)}</strong>{' '}
          🎉
        </p>
      ),
    };
  }
  return {
    icon: 'i i-info-alt',
    color: 'teal',
    content: (
      <p className="text-sm">Your loan repayment will be deducted from your settlement balance.</p>
    ),
  };
}

export default function PaymentAmount({
  setView,
  lastRepayment,
  upcomingPayments: { amount_expected_till_now: amountExpectedTillNow = 0, schedule = [] },
  installment: { amount_collected, installments = [] },
  plan,
  allowCustomAmountRepayment,
}) {
  const {
    state: { showHeader, showFooter },
    dispatch,
  } = useLoanData();
  const firstScheduledPayment = schedule.find(({ amount }) => Boolean(Number(amount)));
  const currentInstallment =
    findFrom(installments, [PARTIALLY_PAID, PENDING]) || findFrom(installments, [CREATED]);

  const collectedFromInstallment =
    Number(currentInstallment.principal_collected_amount) +
    Number(currentInstallment.interest_collected_amount);
  const { amount, date } = firstScheduledPayment || {
    amount: Number(currentInstallment.epi_amount) - collectedFromInstallment,
    date: currentInstallment.end_date,
  };
  const formattedDate = moment(date * 1000).format(DATE_FORMATS.LOAN_DISBURSAL);

  const onRepayNow = () => {
    dispatch({ type: ACTIONS.SET_TOTAL_DUE_INFO, payload: { amount, date } }); // init due amount amount
    if (allowCustomAmountRepayment) {
      dispatch({ type: 'SET_PAYMENT_AMOUNT', payload: 0 }); // Reset amount to be paid
      setView(OVERVIEW_STATUS_VIEWS.PAYMENT_SELECT_AMOUNT);
      return;
    }
    dispatch({ type: 'SET_PAYMENT_AMOUNT', payload: amount });
    setView(OVERVIEW_STATUS_VIEWS.PAYMENT_METHOD);
  };

  const collectedAmount = Number(amount_collected.principal) + Number(amount_collected.interest);

  const { content, icon, color } = getLoanFooterText(
    lastRepayment,
    firstScheduledPayment,
    amountExpectedTillNow,
    collectedAmount,
  );

  return (
    <div className="payment-amount">
      <div className="h-full flex flex-col">
        {showHeader && <LoanStatusHeader plan={plan} />}
        <div className="flex payment-amount-body">
          <div className="flex left">
            <span className="text-navy-blue-o-70 font-bold">
              {firstScheduledPayment ? 'Next Autopay Amount' : 'Total Installment Amount'}
            </span>
            <Amount
              value={Number(amount)}
              className="text-navy-blue text-3xl font-bold"
              style={{ alignSelf: 'flex-start' }}
            />
            <div className="flex mt-8 footer text-xsm">
              <p className="text-section">
                <i className="i i-date-range text-grey-o-60 mr-8" />
                <span className="leading-none">
                  Due on <span className="ml-6 font-bold">{formattedDate}</span>
                </span>
              </p>
              <p className="text-section ml-50">
                <i className="i i-rupee-alt text-grey-o-60 mr-8" />
                <span className="leading-none">
                  Collection Method{' '}
                  <span className="ml-8 font-bold">
                    {firstScheduledPayment ? 'Settlement Balance' : 'Manual'}
                  </span>
                </span>
              </p>
            </div>
          </div>
          <div className="flex right">
            <div>
              <Button
                disabled={currentInstallment.status === CREATED}
                onClick={onRepayNow}
                className="btn Button--primary text-size-15"
              >
                Repay Now
              </Button>
              {currentInstallment.status === CREATED ? (
                <Popover align="top" theme="dark">
                  <PopoverBody>
                    You have successfully paid your EDIs till date. You can pay your next EDI on{' '}
                    {moment(currentInstallment.start_date * 1000).format(
                      DATE_FORMATS.LOAN_DISBURSAL,
                    )}
                  </PopoverBody>
                </Popover>
              ) : null}
            </div>
          </div>
        </div>
      </div>
      {showFooter && <LoanStatusFooter icon={`${icon} text-${color}`}>{content}</LoanStatusFooter>}
    </div>
  );
}
