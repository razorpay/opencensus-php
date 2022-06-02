import React, { useState, useEffect } from 'react';
import RepayAmount from './RepayAmount';
import RepayMethod from './RepayMethod';
import { REPAYMENT_VIEWS, REPAY_AMOUNT_TYPES } from '../constants';

const Repay = ({
  view,
  setView,
  currentOutstandingTotalAmount,
  balance,
  currentOutstandingInterestAmount,
  currentOutstandingPrincipalAmount,
  totalOwedAmount,
  totalInterestAmount,
  totalPrincipalAmount,
  setResultAmounts,
}) => {
  const [repayType, setRepayType] = useState(REPAY_AMOUNT_TYPES.CURRENT_OUTSTANDING);
  const [customAmount, setCustomAmount] = useState(null);
  const [repayAmount, setRepayAmount] = useState(currentOutstandingTotalAmount);

  const isBalanceZero = balance === 0;

  const [settlementBalance, setSettlementBalance] = useState({
    active: !isBalanceZero,
    amount: balance,
    error: '',
    isCustomAmountActive: false,
    customAmount: balance,
  });

  const isSettlementBalanceLessThanRepayAmount = settlementBalance.customAmount < repayAmount;
  const isCustomAmountLessThanOrEqualToBalance = settlementBalance.customAmount <= balance;

  const [bankBalance, setBankBalance] = useState({
    active: isSettlementBalanceLessThanRepayAmount,
    amount: 0,
  });

  function getInputType() {
    if (
      isBalanceZero ||
      (!isSettlementBalanceLessThanRepayAmount && isCustomAmountLessThanOrEqualToBalance)
    )
      return 'radio';
    return 'checkbox';
  }

  const repayInputType = getInputType();

  useEffect(() => {
    setSettlementBalance({
      ...settlementBalance,
      active: !isBalanceZero,
    });
    setBankBalance({
      ...bankBalance,
      active: isSettlementBalanceLessThanRepayAmount,
    });
  }, [view]);

  useEffect(() => {
    const amount = repayAmount > balance ? balance : repayAmount;
    setSettlementBalance({
      ...settlementBalance,
      amount,
      customAmount: amount,
      active: !isBalanceZero,
    });
  }, [repayAmount, balance]);

  useEffect(() => {
    setBankBalance({
      ...bankBalance,
      amount: settlementBalance.active
        ? repayAmount - Math.min(settlementBalance.customAmount, settlementBalance.amount)
        : repayAmount,
    });
  }, [
    settlementBalance.amount,
    settlementBalance.customAmount,
    settlementBalance.active,
    repayAmount,
    balance,
  ]);

  if (view === REPAYMENT_VIEWS.REPAY_METHOD) {
    return (
      <RepayMethod
        setView={setView}
        repayAmount={repayAmount}
        balance={balance}
        repayInputType={repayInputType}
        isSettlementBalanceLessThanRepayAmount={isSettlementBalanceLessThanRepayAmount}
        setSettlementBalance={setSettlementBalance}
        settlementBalance={settlementBalance}
        setBankBalance={setBankBalance}
        bankBalance={bankBalance}
        setResultAmounts={setResultAmounts}
        totalInterestAmount={totalInterestAmount}
        totalPrincipalAmount={totalPrincipalAmount}
        currentOutstandingInterestAmount={currentOutstandingInterestAmount}
        currentOutstandingPrincipalAmount={currentOutstandingPrincipalAmount}
        repayType={repayType}
      />
    );
  } else if (view === REPAYMENT_VIEWS.REPAY_AMOUNT) {
    return (
      <RepayAmount
        setView={setView}
        currentOutstandingTotalAmount={currentOutstandingTotalAmount}
        totalOwedAmount={totalOwedAmount}
        customAmount={customAmount}
        balance={balance}
        currentOutstandingInterestAmount={currentOutstandingInterestAmount}
        currentOutstandingPrincipalAmount={currentOutstandingPrincipalAmount}
        setRepayType={setRepayType}
        repayType={repayType}
        setCustomAmount={setCustomAmount}
        setRepayAmount={setRepayAmount}
        totalInterestAmount={totalInterestAmount}
        totalPrincipalAmount={totalPrincipalAmount}
      />
    );
  } else return null;
};

export default Repay;
