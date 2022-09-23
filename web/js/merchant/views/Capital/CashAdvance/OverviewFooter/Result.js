import React from 'react';
import { withRouter } from 'react-router-dom';
import RepaySuccess from './RepaySuccess';
import RepayFailure from './RepayFailure';
import { REPAYMENT_VIEWS } from '../constants';
import { trackRepaymentClose } from '../TrackEvents/trackEvents';

const Result = ({ setView, view, resultAmounts, location: { pathname } }) => {
  const handleCrossClick = () => {
    if (view !== REPAYMENT_VIEWS.RESULT_SUCCESS)
      trackRepaymentClose(pathname, 'icon', resultAmounts);
    setView(REPAYMENT_VIEWS.REPAY_AMOUNT);
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

export default withRouter(Result);
