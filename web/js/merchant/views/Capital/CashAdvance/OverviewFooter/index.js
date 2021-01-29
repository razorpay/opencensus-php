import React, { useState, useEffect } from 'react';
import { connect } from 'react-redux';
import SummaryCarousel from '../SummaryCarousel';
import { REPAYMENT_VIEWS, COLLECTIONS_PRODUCT_TYPES } from '../constants';
import Summary from './Summary';
import Repay from './Repay';
import Result from './Result';
import { fetchInstallments } from 'merchant/reducers/capital/withdrawals';
import { fetchBalances } from 'merchant/reducers/capital/repayments';
import { fetchCurrentBalance } from 'merchant/reducers/home';
import moment from 'moment';
import { getTotalAmountBreakup, getNextRepayBreakup } from './utils/index';

// SUMMARY --> REPAY - AMOUNT --> RESULT - SUCCESS
//                   - METHOD            - FAILURE

function OverviewFooter({
  balances,
  installments,
  fetchInstallments,
  fetchBalances,
  fetchCurrentBalance,
  hideSummaryTab,
  merchantID,
  account_balance,
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
        owner_id: merchantID,
        from: moment().startOf('day').unix(),
        to: moment().add(30, 'days').unix(),
      });
      fetchBalances({
        product_type: COLLECTIONS_PRODUCT_TYPES.CASH_ADVANCE,
        credit_id: merchantID,
      });
      fetchCurrentBalance();
    }
  }, [view]);

  const balance = account_balance.data.balance || 0;

  const { totalInterestAmount, totalPrincipalAmount } = getTotalAmountBreakup(balances);

  const {
    nextRepayInterestAmount,
    nextRepayPrincipalAmount,
    nextRepaymentDate,
  } = getNextRepayBreakup(installments);

  const nextRepayableAmount = nextRepayInterestAmount + nextRepayPrincipalAmount;

  const totalOwedAmount = totalInterestAmount + totalPrincipalAmount;

  return (
    <div className="overview-footer">
      {!hideSummaryTab && <div className="repay-summary-tab">Repayment Summary</div>}
      <div className="flex">
        {view === REPAYMENT_VIEWS.SUMMARY && (
          <Summary
            setView={setView}
            nextRepayableAmount={nextRepayableAmount}
            totalOwedAmount={totalOwedAmount}
            nextRepaymentDate={nextRepaymentDate}
            loading={balances.loading || installments.loading}
          />
        )}
        {(view === REPAYMENT_VIEWS.REPAY_METHOD || view === REPAYMENT_VIEWS.REPAY_AMOUNT) && (
          <Repay
            setView={setView}
            nextRepayableAmount={nextRepayableAmount}
            balance={balance}
            view={view}
            nextRepayInterestAmount={nextRepayInterestAmount}
            nextRepayPrincipalAmount={nextRepayPrincipalAmount}
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
            nextRepayableAmount={nextRepayableAmount}
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
      merchantID: state.session.user.current,
      account_balance: state.home.current_balance,
    };
  },
  { fetchInstallments, fetchBalances, fetchCurrentBalance },
)(OverviewFooter);
