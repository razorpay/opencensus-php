import React from 'react';
import { getGroupedRepayments } from '../util';
import LoanCollectionSummary from './LoanCollectionSummary';
import LoanSummary from './LoanSummary';
import OverviewStatus from './OverviewStatus/index';
import RecentRepayments from './RecentRepayments';

export default function Overview({
  plan,
  installments,
  recentRepayments,
  upcomingPayments,
  onRefresh,
}) {
  const lastFiveRecentRepayments = getGroupedRepayments(recentRepayments, 5);
  return (
    <div className="overview-wrapper">
      <div className="overview-left">
        <OverviewStatus
          plan={plan}
          installment={installments}
          upcomingPayments={upcomingPayments}
          lastRepayment={recentRepayments[0]} // that is latest repayment, api gives in desc order
          onRefresh={onRefresh}
        />
        <RecentRepayments repayments={lastFiveRecentRepayments} />
      </div>
      <div className="overview-right">
        <LoanCollectionSummary plan={plan} installment={installments} />
        <LoanSummary plan={plan} installment={installments} />
      </div>
    </div>
  );
}
