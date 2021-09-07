import React from 'react';
import Button from 'common/new-ui/Button';
import Amount from 'common/ui/Amount';
import { COLLECTIONS_PAYMENT_TYPE } from '../../constants';
import LoanStatusFooter from './LoanStatusFooter';
import { useLoanData } from './PaymentContext';
import moment from 'moment';
import { withRouter } from 'react-router-dom';
import { LOANS_BASE_URL, LOANS_SECTIONS } from '../../../constants';

function PaymentSuccess({ onRefresh, history }) {
  const {
    state: { successRepayments = [] },
  } = useLoanData();

  const onDone = () => {
    onRefresh();
  };

  const onViewRepaymentHistory = () => {
    history.push(`${LOANS_BASE_URL}${LOANS_SECTIONS.REPAYMENTS_HISTORY}`);
    onRefresh();
  };

  const totalAmountCollected = successRepayments.reduce((acc, { amount }) => acc + amount, 0);

  return (
    <div className="payment-success">
      <div className="payment-success-body">
        <div className="top">
          <div className="flex left">
            <div className="flex items-end">
              <i className="i i-success-icon text-xl text-success" />
              <span className="text-xl leading-none text-navy-blue font-bold ml-8">
                Repayment Successful!
              </span>
            </div>
            <span className="text-sm text-navy-blue-o-80 mt-8">
              Woohoo! We’ve recieved your payment 🎉{' '}
            </span>
          </div>
          <div className="right">
            <Button.Primary onClick={onDone}>Done</Button.Primary>
          </div>
        </div>
        <div className="bottom flex">
          <div className="detail-segment flex">
            <span className="text-xs text-grey-o-60">Amount Collected</span>
            <Amount value={totalAmountCollected} className="text-navy-blue text-sm font-bold" />
          </div>
          <div className="detail-segment flex">
            <span className="text-xs text-grey-o-60">Repayment Date</span>
            <span className="text-navy-blue text-sm font-bold">
              {moment().format('MMM DD, YYYY')}
            </span>
          </div>
          <div className="detail-segment flex">
            <span className="text-xs text-grey-o-60">Repayment Via</span>
            {successRepayments.map(({ type }, idx) => (
              <span key={idx} className="text-navy-blue text-sm font-bold">
                {COLLECTIONS_PAYMENT_TYPE[type]}
              </span>
            ))}
          </div>
          <div className="detail-segment flex">
            <span className="text-xs text-grey-o-60">Reference Id</span>
            {successRepayments.map(({ id }, idx) => (
              <span key={idx} className="text-navy-blue text-sm font-bold">
                {id}
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

export default withRouter(PaymentSuccess);
