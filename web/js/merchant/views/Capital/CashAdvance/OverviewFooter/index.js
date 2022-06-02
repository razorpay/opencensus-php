import React, { useState, useEffect } from 'react';
import { connect } from 'react-redux';
import SummaryCarousel from '../SummaryCarousel';
import { REPAYMENT_VIEWS, COLLECTIONS_PRODUCT_TYPES } from '../constants';
import Summary from './Summary';
import Repay from './Repay';
import Result from './Result';
import { fetchInstallments, fetchCurrentOutstanding } from 'merchant/reducers/capital/withdrawals';
import { fetchBalances } from 'merchant/reducers/capital/repayments';
import { fetchCurrentBalance } from 'merchant/reducers/home';
import moment from 'moment';
import { getTotalAmountBreakup, getCurrentOutstandingBreakup } from './utils/index';

// SUMMARY --> REPAY - AMOUNT --> RESULT - SUCCESS
//                   - METHOD            - FAILURE

function OverviewFooter({
  balances,
  installments,
  fetchInstallments,
  fetchBalances,
  fetchCurrentBalance,
  fetchCurrentOutstanding,
  hideSummaryTab,
  merchantId,
  account_balance,
  current_outstanding,
}) {
  const [view, setView] = useState(REPAYMENT_VIEWS.SUMMARY);
  const [resultAmounts, setResultAmounts] = useState({
    settlementAmount: 0,
    bankAmount: 0,
    repayAmount: 0,
    principalAmount: 0,
    interestAmount: 0,
    remainingSettlementBalance: 0,
    userRepayMethod: '',
  });
  useEffect(() => {
    if (view === REPAYMENT_VIEWS.SUMMARY) {
      fetchInstallments({
        owner_id: merchantId,
        from: moment().startOf('day').unix(),
        to: moment().add(30, 'days').unix(),
      });
      fetchBalances({
        product_type: COLLECTIONS_PRODUCT_TYPES.CASH_ADVANCE,
        credit_id: merchantId,
      });
      fetchCurrentBalance();
      fetchCurrentOutstanding({
        owner_id: merchantId,
        from: moment().startOf('day').unix(),
        to: moment().add(30, 'days').unix(),
      });
    }
  }, [view]);

  const balance = account_balance.data.balance || 0;

  const { totalInterestAmount, totalPrincipalAmount } = getTotalAmountBreakup(balances);

  const totalOwedAmount = totalInterestAmount + totalPrincipalAmount;

  const currentOutstanding = getCurrentOutstandingBreakup(current_outstanding);

  return (
    <div className="overview-footer">
      {!hideSummaryTab && <div className="repay-summary-tab">Repayment Summary</div>}
      <div className="flex">
        {view === REPAYMENT_VIEWS.SUMMARY && (
          <Summary
            setView={setView}
            currentOutstandingTotalAmount={currentOutstanding.total}
            totalOwedAmount={totalOwedAmount}
            loading={balances.loading || installments.loading}
          />
        )}
        {(view === REPAYMENT_VIEWS.REPAY_METHOD || view === REPAYMENT_VIEWS.REPAY_AMOUNT) && (
          <Repay
            setView={setView}
            currentOutstandingTotalAmount={currentOutstanding.total}
            balance={balance}
            view={view}
            currentOutstandingInterestAmount={currentOutstanding.interest}
            currentOutstandingPrincipalAmount={currentOutstanding.principal}
            totalOwedAmount={totalOwedAmount}
            totalInterestAmount={totalInterestAmount}
            totalPrincipalAmount={totalPrincipalAmount}
            setResultAmounts={setResultAmounts}
          />
        )}
        {(view === REPAYMENT_VIEWS.RESULT_FAILURE || view === REPAYMENT_VIEWS.RESULT_SUCCESS) && (
          <Result
            setView={setView}
            view={view}
            currentOutstandingTotalAmount={currentOutstanding.total}
            balance={balance}
            resultAmounts={resultAmounts}
          />
        )}
        <SummaryCarousel />
      </div>
    </div>
  );
}

export default connect(
  (state) => {
    return {
      balances: state.repayments.balances,
      installments: state.withdrawals.installments,
      merchantId: state.session.user.current,
      account_balance: state.home.current_balance,
      current_outstanding: state.withdrawals.current_outstanding,
    };
  },
  { fetchInstallments, fetchBalances, fetchCurrentBalance, fetchCurrentOutstanding },
)(OverviewFooter);
