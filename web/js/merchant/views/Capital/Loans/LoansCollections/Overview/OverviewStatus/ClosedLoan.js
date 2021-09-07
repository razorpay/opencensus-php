import React from 'react';
import LoanStatusFooter from './LoanStatusFooter';
import LoanStatusHeader from './LoanStatusHeader';
import Amount from 'common/ui/Amount';
import moment from 'moment';
import { DATE_FORMATS } from '../../constants';

export default function ClosedLoan({ installment, plan }) {
  const closedDate = moment(plan.installments_config.end_date * 1000).format(
    DATE_FORMATS.LOAN_DISBURSAL,
  );
  const { principal, interest } = installment.amount_collected;
  return (
    <div className="closed-loan">
      <LoanStatusHeader plan={plan} />
      <div className="flex closed-loan-body">
        <div className="flex">
          <div className="flex breakdown-section">
            <span className="text-navy-blue-o-70 text-sm font-bold">Principal amount</span>
            <Amount value={Number(principal)} className="text-3xl text-navy-blue" />
          </div>
          <div className="flex breakdown-section">
            <span className="text-navy-blue-o-70 text-sm font-bold">Interest Repaid</span>
            <Amount value={Number(interest)} className="text-3xl text-navy-blue" />
          </div>
        </div>
        <div className="flex mt-8 footer text-xsm">
          <span>
            Closed on <span className="ml-8 font-bold">{closedDate}</span>
          </span>
        </div>
      </div>
      <LoanStatusFooter icon="i i-success-icon text-success">
        <p className="text-sm">
          You’ve succesfully repaid and closed this loan <span className="ml-8">🎉</span>
        </p>
      </LoanStatusFooter>
    </div>
  );
}
