import React, { useState } from 'react';
import moment from 'moment';
import PropTypes from 'prop-types';
import RazorpatCapital from '../../../../../../../icons/merchant/razorpay-capital.svg';
import Amount from 'common/ui/Amount';
import { calculateLoanBreakup, getTimeDifferenceIn } from '../util';
function SubHeading({ heading = '' }) {
  return <span className="text-xsm text-gray-o-50">{heading}</span>;
}

function getTimeDifference(endDate, currentDate) {
  const months = getTimeDifferenceIn(endDate, currentDate, 'months') + 1;
  const days = getTimeDifferenceIn(endDate, currentDate, 'days') + 1;
  const years = getTimeDifferenceIn(endDate, currentDate, 'years') + 1;

  if (months > 12) {
    return {
      type: 'Years',
      num: years,
    };
  } else if (days > 30) {
    return {
      type: 'Months',
      num: months,
    };
  } else {
    return {
      type: 'Days',
      num: days,
    };
  }
}
export default function LoanSummary({ installment = [], plan = {} }) {
  const { installments } = installment;
  const {
    totalPrincipalAmount,
    totalPrincipalAmountCollected,
    totalInterestAmount,
    totalInterestAmountCollected,
  } = calculateLoanBreakup(installments);
  const principalBalance = totalPrincipalAmount - totalPrincipalAmountCollected;
  const interestBalance = totalInterestAmount - totalInterestAmountCollected;
  const currentDate = new Date().getTime() / 1000;
  const remainingTenureMonths = getTimeDifference(
    plan.installments_config.end_date,
    Math.max(currentDate, plan.installments_config.start_date),
  );
  const firstEMIDate =
    installments.length > 0 ? moment.unix(installments[0].start_date).format('D MMM') : '';
  const firstEMIYear =
    installments.length > 0 ? moment.unix(installments[0].start_date).format('Y') : '';
  const loanTenure = getTimeDifference(
    plan.installments_config.end_date,
    plan.installments_config.start_date,
  );
  const EMI = installments.length > 0 ? installments[0].epi_amount : '';
  const [displaySummary, toggleDisplaySummary] = useState(false);

  return (
    <div>
      <div className="card loan-summary">
        <div className="heading" onClick={() => toggleDisplaySummary((prev) => !prev)}>
          <div className="highlight"></div>
          <span className="font-bold">Loan Summary</span>
          <i className={`i i-chevron-up ${displaySummary ? '' : 'fa-rotate-180'}`} />
        </div>
        <div className={`loan-summary-info ${displaySummary ? 'show-summary' : 'hide-summary'}`}>
          <span className="font-bold">Repayment</span>
          <div className="flex flex-row mt-12">
            <div className="flex flex-col first-group">
              <SubHeading heading="Principal Balance" />
              <Amount className="text-base mt-6" value={principalBalance} />
            </div>
            <div className="flex flex-col">
              <SubHeading heading="Interest Balance" />
              <Amount className="text-base mt-6" value={interestBalance} />
            </div>
          </div>
          <div className="flex flex-row mt-24">
            <div className="flex flex-col first-group">
              <SubHeading heading="Remaining Tenure" />
              <p>
                <span className="font-bold text-sm mt-6">{remainingTenureMonths.num}</span>{' '}
                {remainingTenureMonths.type}
              </p>
            </div>
            <div className="flex flex-col">
              <SubHeading heading="First EDI Date" />
              <p>
                <span className="mt-6 text-sm font-bold ">{firstEMIDate},</span> {firstEMIYear}
              </p>
            </div>
          </div>
          <span className="font-bold mt-24 Withdrawal-heading">Withdrawal</span>
          <div className="flex flex-row mt-12">
            <div className="flex flex-col first-group">
              <SubHeading heading="Principal Amount" />
              <Amount className="text-base mt-6" value={totalPrincipalAmount} />
            </div>
            <div className="flex flex-col">
              <SubHeading heading="Interest" />
              <Amount className="text-base mt-6" value={totalInterestAmount} />
            </div>
          </div>
          <div className="flex flex-row mt-24">
            <div className="flex flex-col first-group">
              <SubHeading heading="Loan Tenure" />
              <p>
                <span className="font-bold">{loanTenure.num}</span> {loanTenure.type}
              </p>
            </div>
            <div className="flex flex-col">
              <SubHeading heading="EMI" />
              <Amount className="text-base mt-6" value={EMI} />
            </div>
          </div>
        </div>
      </div>

      {!displaySummary && (
        <div className="card poweredby-card">
          <span className="text-xs">Powered by</span>{' '}
          <img src={RazorpatCapital} className="capital-logo" />
        </div>
      )}
    </div>
  );
}

LoanSummary.propTypes = {
  installment: PropTypes.array,
  plan: PropTypes.object,
};

LoanSummary.defaultProps = {
  installment: [],
  plan: {},
};
