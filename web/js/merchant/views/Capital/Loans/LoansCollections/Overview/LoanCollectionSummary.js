import React from 'react';
import Amount from 'common/ui/Amount';
import PropTypes from 'prop-types';
import { ProgressBar } from 'common/ui/ProgressBar';
// import { INSTALLMENT_STATUS } from '../constants';
import { calculateLoanBreakup } from '../util';

// const { PAID, OVERDUE } = INSTALLMENT_STATUS;
export default function LoanCollectionSummary({ installment, /* plan ,*/ showHeader = true }) {
  const {
    installments = [],
    amount_collected: { principal = 0, interest = 0 },
  } = installment;

  const { totalPrincipalAmount, totalInterestAmount } = calculateLoanBreakup(installments);
  const totalEmiPaid = Number(principal) + Number(interest);
  // const totalEmis = installments.length;
  const totalEmiAmount = totalPrincipalAmount + totalInterestAmount;
  // const paidEmis = installments.filter(({ status }) => status === PAID).length;
  // const unpaidEmis = installments.filter(({ status }) => status === OVERDUE).length;

  return (
    <div className="card loan-collection-summary">
      {showHeader && (
        <div className="heading">
          <span className="font-bold">Loan Collection Summary</span>
        </div>
      )}
      <div className="loan-collection-info">
        <span className="collection-heading font-bold">Loan Paid</span>
        <div className="amount-summary mt-4">
          <Amount value={totalEmiPaid} className="text-xl" />{' '}
          <span className="text-sm out-of"> out of </span>
          <Amount value={totalEmiAmount} className="text-sm" />
          <ProgressBar
            value={totalEmiPaid}
            max={totalEmiAmount === 0 ? 100 : totalEmiAmount}
            min={0}
            color="rgba(0, 134, 89, 0.76)"
          />
          {/* <div className="emi-paid text-xsm mt-6">
            <span>
              <span className="font-bold">
                {paidEmis} of {totalEmis}
              </span>{' '}
              EMI paid
            </span>
            {unpaidEmis ? (
              <span className="text-danger">
                <span className="font-bold">, {unpaidEmis}</span> EMI unpaid
              </span>
            ) : null}
          </div> */}
        </div>
      </div>
    </div>
  );
}
LoanCollectionSummary.propTypes = {
  installment: PropTypes.object,
};

LoanCollectionSummary.defaultProps = {
  installment: {},
};
