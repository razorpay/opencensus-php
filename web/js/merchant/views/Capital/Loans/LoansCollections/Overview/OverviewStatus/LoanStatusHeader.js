import React from 'react';
import { DATE_FORMATS, PLAN_STATUS } from '../../constants';
import moment from 'moment';

export default function LoanStatusHeader({ plan }) {
  const isActive = plan.status === PLAN_STATUS.CREATED;
  const loanStatus = isActive ? 'Active' : 'Closed';
  const disbursalDate = moment(plan.installments_config.start_date * 1000)
    .subtract(1, 'days')
    .format(DATE_FORMATS.LOAN_DISBURSAL);
  const closedDate = moment(plan.installments_config.end_date * 1000).format(
    DATE_FORMATS.LOAN_DISBURSAL,
  );

  return (
    <header className="flex mt-4">
      <span className={`status-pill ${isActive ? '' : 'fill'} text-xs`}>{loanStatus} Loan</span>
      <span className="mr-4 ml-4 text-grey-o-60 text-xsm">•</span>
      <span className="text-grey-o-60 text-xsm ml-4">
        {isActive ? 'Availed' : 'Closed'} on {isActive ? disbursalDate : closedDate}
      </span>
      <div className="separator" />
    </header>
  );
}
