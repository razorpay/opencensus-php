import React from 'react';
import RepaySuccess from './RepaySuccess';
import RepayFailure from './RepayFailure';
import { REPAYMENT_VIEWS } from '../constants';

const Result = ({ setView, view, resultAmounts }) => {
  const handleCrossClick = () => {
    setView(REPAYMENT_VIEWS.SUMMARY);
  };
  return (
    <div className="repay-container repay-result">
      {view === REPAYMENT_VIEWS.RESULT_SUCCESS ? (
        <RepaySuccess setView={setView} resultAmounts={resultAmounts} />
      ) : (
        <RepayFailure setView={setView} resultAmounts={resultAmounts} />
      )}
      <div className="btn-cross" onClick={handleCrossClick}>
        <i className="i i-close" />
      </div>
    </div>
  );
};

export default Result;
