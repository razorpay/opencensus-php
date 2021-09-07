import React, { useState, useEffect } from 'react';
import moment from 'moment';
import { withRouter } from 'react-router-dom';
import RepaymentFilters from './RepaymentFilters';
import RepaymentList from './RepaymentList';
import fileDownload from 'common/utils/file-download';
import { arrayObjToCsv, getURLQueryParams } from 'common/utils/rzp-utils';
import { DEFAULT_COUNT, getBreakupByBalanceType, STATUS_LABELS } from '../../constants';
import { getCollectionMethod, getRepaymentsWithOutstandingBalance } from '../util';
import OverviewStatus from '../Overview/OverviewStatus';
import LoanCollectionSummary from '../Overview/LoanCollectionSummary';

const exportAsCSV = (repayments) => {
  const reportData = repayments.map(
    ({ id, amount, status, created_at, breakups, ...restProperties }) => {
      const principalRepaid = getBreakupByBalanceType(breakups, 'BALANCE_TYPE_PRINCIPAL');
      const interestRepaid = getBreakupByBalanceType(breakups, 'BALANCE_TYPE_INTEREST');
      const createdAt = created_at ? moment.unix(created_at).format('D MMM') : '--';
      const collectionMethod = getCollectionMethod(restProperties);

      return {
        id,
        createdAt,
        collectionMethod,
        amount: `₹ ${(amount / 100).toFixed(2)}`,
        principal_repaid: `₹ ${(principalRepaid / 100).toFixed(2)}`,
        interest_repaid: `₹ ${(interestRepaid / 100).toFixed(2)}`,
        status: STATUS_LABELS[status],
      };
    },
  );

  const csvData = arrayObjToCsv(reportData);
  fileDownload(csvData, 'repayments_export.csv');
};

function RepaymentHistory({
  plan,
  installments,
  repayments,
  upcomingPayments,
  loanAmount = 0,
  location: { search = {} },
  onRefresh,
}) {
  const [filteredRepayments, setFilteredRepayments] = useState(repayments);

  const onSubmit = ({ reference_id, status, count }) => {
    let newRepayments = getRepaymentsWithOutstandingBalance(repayments, loanAmount);
    if (reference_id)
      newRepayments = newRepayments.filter((r) =>
        r.id.toLowerCase().includes(reference_id.toLowerCase()),
      );
    if (status) newRepayments = newRepayments.filter((r) => r.status === status);
    if (count) newRepayments = newRepayments.slice(0, count);

    setFilteredRepayments(newRepayments);
  };

  useEffect(() => {
    const filters = getURLQueryParams(search);
    onSubmit(filters);
  }, []);

  return (
    <div className="repayments-history">
      <div className="payment-box-wrapper flex justify-between">
        <OverviewStatus
          plan={plan}
          installment={installments}
          upcomingPayments={upcomingPayments}
          lastRepayment={repayments[0]}
          onRefresh={onRefresh}
          showHeader={false}
          showFooter={false}
        />
        <LoanCollectionSummary plan={plan} installment={installments} showHeader={false} />
      </div>
      <div className="content-wrapper cash-advance-repayments">
        <div className="filters-wrapper">
          <RepaymentFilters
            form="loansRepaymentListFilter"
            count={DEFAULT_COUNT}
            onSubmit={onSubmit}
          />
          <button
            disabled={!repayments.length}
            className="btn btn-outline export-button"
            onClick={() => exportAsCSV(repayments)}
          >
            <i className="i i-download" /> Export
          </button>
        </div>
        <RepaymentList repayments={filteredRepayments} loanAmount={loanAmount} />
      </div>
    </div>
  );
}

export default withRouter(RepaymentHistory);
