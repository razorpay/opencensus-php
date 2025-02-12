import React, { useEffect, useState, Fragment } from 'react';
import { REPAYMENT_VIEWS, COLLECTIONS_PRODUCT_TYPES } from '../constants';
import Repay from '../OverviewFooter/Repay';
import Result from '../OverviewFooter/Result';
import {
  getCurrentOutstandingBreakup,
  getNextRepayBreakup,
  getTotalAmountBreakup,
} from '../OverviewFooter/utils';
import Amount from 'common/ui/Amount';
import PlaceholderLoader from 'common/ui/PlaceholderLoader';
import { connect } from 'react-redux';
import { fetchBalances } from 'merchant/reducers/capital/repayments';
import { fetchCurrentBalance } from 'merchant/reducers/home';
import moment from 'moment';
import TotalOwedAmountBreakdown from './TotalOwedAmountBreakdown';

const RepaymentCard = ({
  account_balance,
  balances,
  fetchCurrentBalance,
  fetchBalances,
  merchantID,
  installments,
  currentOutstanding,
}) => {
  const { totalInterestAmount, totalPrincipalAmount } = getTotalAmountBreakup(balances);

  const [view, setView] = useState(REPAYMENT_VIEWS.SUMMARY);
  const [resultAmounts, setResultAmounts] = useState({
    settlementAmount: 0,
    bankAmount: 0,
    repayAmount: 0,
    principalAmount: 0,
    interestAmount: 0,
    remainingSettlementBalance: 0,
  });

  const { nextRepayInterestAmount, nextRepayPrincipalAmount } = getNextRepayBreakup(installments);

  const nextRepayableAmount = nextRepayInterestAmount + nextRepayPrincipalAmount;
  const totalOwedAmount = totalInterestAmount + totalPrincipalAmount;
  const balance = account_balance.data.balance || 0;

  const currentOutstandingBreakup = getCurrentOutstandingBreakup(currentOutstanding);

  useEffect(() => {
    if (view === REPAYMENT_VIEWS.SUMMARY) {
      fetchCurrentBalance();
      fetchBalances({
        product_type: COLLECTIONS_PRODUCT_TYPES.CASH_ADVANCE,
        credit_id: merchantID,
      });
    }
  }, [view]);

  const LeftView =
    view === REPAYMENT_VIEWS.SUMMARY ? (
      <Summary
        totalOwedAmount={totalOwedAmount}
        loading={balances.loading}
        setView={setView}
        installments={installments}
      />
    ) : view === REPAYMENT_VIEWS.REPAY_AMOUNT || view === REPAYMENT_VIEWS.REPAY_METHOD ? (
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
        currentOutstandingTotalAmount={currentOutstandingBreakup.total}
        currentOutstandingInterestAmount={currentOutstandingBreakup.interest}
        currentOutstandingPrincipalAmount={currentOutstandingBreakup.principal}
      />
    ) : view === REPAYMENT_VIEWS.RESULT_SUCCESS || view === REPAYMENT_VIEWS.RESULT_FAILURE ? (
      <Result
        setView={setView}
        view={view}
        nextRepayableAmount={nextRepayableAmount}
        balance={balance}
        resultAmounts={resultAmounts}
      />
    ) : null;

  return (
    <>
      {LeftView}
      <TotalOwedAmountBreakdown
        totalOwedAmount={totalOwedAmount}
        totalInterestAmount={totalInterestAmount}
        totalPrincipalAmount={totalPrincipalAmount}
      />
    </>
  );
};

export default connect(
  (state) => ({
    balances: state.repayments.balances,
    merchantID: state.session.user.current,
    account_balance: state.home.current_balance,
  }),
  {
    fetchBalances,
    fetchCurrentBalance,
  },
)(RepaymentCard);

const Summary = ({ totalOwedAmount, loading, setView, installments }) => {
  let lastDate = '';
  let last = installments.data || [];
  last = last[last.length - 1];
  if (last && moment().unix() > last.repayment_date) {
    last = null;
  }
  if (last) {
    lastDate = moment.unix(last.repayment_date).format('MMMM D');
  }

  return (
    <div className="repayment-summary">
      <div className="top-section flex">
        <div className="left">
          <p className="title">Total Owed Amount</p>
          <div className="large-amount">
            {loading ? <PlaceholderLoader /> : <Amount value={totalOwedAmount} />}
          </div>
        </div>
        {totalOwedAmount > 0 && (
          <div className="right">
            <button
              className="btn btn-primary"
              onClick={() => setView(REPAYMENT_VIEWS.REPAY_AMOUNT)}
            >
              Repay Now
            </button>
          </div>
        )}
      </div>
      <div className="footer">
        {loading ? (
          <div className="loader-wrapper">
            <PlaceholderLoader />
            <PlaceholderLoader />
          </div>
        ) : (
          <div className="block-note">
            {totalOwedAmount > 0 ? (
              <Fragment>
                This amount will be deducted on a daily basis in parts from your settlement balance
                {lastDate ? (
                  <span>
                    {' '}
                    by <strong>{lastDate}</strong>.
                  </span>
                ) : (
                  '.'
                )}
              </Fragment>
            ) : (
              'No pending amount needs to be repaid.'
            )}
          </div>
        )}
      </div>
    </div>
  );
};
