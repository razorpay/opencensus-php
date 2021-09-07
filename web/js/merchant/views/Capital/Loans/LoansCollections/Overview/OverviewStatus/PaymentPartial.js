import React from 'react';
import Button from 'common/new-ui/Button';
import Amount from 'common/ui/Amount';
import { COLLECTIONS_PAYMENT_TYPE } from '../../constants';
import LoanStatusFooter from './LoanStatusFooter';
import { useLoanData } from './PaymentContext';
import moment from 'moment';
import { withRouter } from 'react-router-dom';
import { LOANS_BASE_URL, LOANS_SECTIONS } from '../../../constants';

function PaymentPartial({ onRefresh, history }) {
  const {
    state: { successRepayments = [], failedRepayments = [] },
  } = useLoanData();

  const onDone = () => {
    onRefresh();
  };

  const onViewRepaymentHistory = () => {
    history.push(`${LOANS_BASE_URL}${LOANS_SECTIONS.REPAYMENTS_HISTORY}`);
    onRefresh();
  };

  const allRepayments = [...successRepayments, ...failedRepayments];
  return (
    <div className="payment-success">
      <div className="payment-success-body">
        <div className="top">
          <div className="flex left">
            <div className="flex items-end">
              <i className="i i-triangle-alert text-xl text-warm" />
              <span className="text-xl leading-none text-navy-blue font-bold ml-8">
                Repayment Partially Successful!
              </span>
            </div>
            <span className="text-sm text-navy-blue-o-80 mt-8">
              There was a problem with one of your repayments.
            </span>
          </div>
          <div className="right">
            <Button.Primary onClick={onDone}>Done</Button.Primary>
          </div>
        </div>
        <div className="bottom flex">
          <div className="detail-segment flex">
            <span className="text-xs text-grey-o-60">Amount Collected</span>
            {allRepayments.map(({ amount }, idx) => (
              <Amount key={idx} value={amount} className="text-navy-blue text-sm font-bold" />
            ))}
          </div>
          <div className="detail-segment flex">
            <span className="text-xs text-grey-o-60">Repayment Date</span>
            {allRepayments.map((_, idx) => (
              <span key={idx} className="text-navy-blue text-sm font-bold">
                {moment().format('MMM DD, YYYY')}
              </span>
            ))}
          </div>
          <div className="detail-segment flex">
            <span className="text-xs text-grey-o-60">Repayment Via</span>
            {allRepayments.map(({ type }, idx) => (
              <span key={idx} className="text-navy-blue text-sm font-bold">
                {COLLECTIONS_PAYMENT_TYPE[type]}
              </span>
            ))}
          </div>
          <div className="detail-segment flex">
            <span className="text-xs text-grey-o-60">Reference Id</span>
            {allRepayments.map(({ id }, idx) => (
              <span key={idx} className="text-navy-blue text-sm font-bold">
                {id}
              </span>
            ))}
          </div>
          <div className="detail-segment flex">
            <span className="text-xs text-grey-o-60">Status</span>
            {allRepayments.map(({ success }, idx) => (
              <span
                key={idx}
                className={`text-${success ? 'success' : 'danger'} text-sm font-bold`}
              >
                {success ? 'Successful' : 'Failed'}
              </span>
            ))}
          </div>
        </div>
      </div>
      <LoanStatusFooter>
        <Button.Transparent onClick={onViewRepaymentHistory}>
          View Repayment History
        </Button.Transparent>
      </LoanStatusFooter>
    </div>
  );
}

export default withRouter(PaymentPartial);
