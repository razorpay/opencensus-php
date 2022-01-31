import React from 'react';
import { getGroupedRepayments } from '../util';
import LoanCollectionSummary from './LoanCollectionSummary';
import LoanSummary from './LoanSummary';
import OverviewStatus from './OverviewStatus/index';
import RecentRepayments from './RecentRepayments';

export default function Overview({ plan, installments, repayments, upcomingPayments, onRefresh }) {
  const lastFiveRepayments = getGroupedRepayments(repayments, 5);
  return (
    <div className="overview-wrapper">
      <div className="overview-left">
        <OverviewStatus
          plan={plan}
          installment={installments}
          upcomingPayments={upcomingPayments}
          lastRepayment={repayments[repayments.length - 1]} // that is latest repayment, api gives in asc order
          onRefresh={onRefresh}
        />
        <RecentRepayments repayments={lastFiveRepayments} />
      </div>
      <div className="overview-right">
        <LoanCollectionSummary plan={plan} installment={installments} />
        <LoanSummary plan={plan} installment={installments} />
      </div>
    </div>
  );
}
