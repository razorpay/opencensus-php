import React from 'react';

import { withRouter } from 'common/deprecated/withRouter';
import { trackRepaymentClose } from 'merchant/views/Capital/CashAdvance/TrackEvents/trackEvents';
import { REPAYMENT_VIEWS } from 'merchant/views/Capital/CashAdvance/constants';

import RepayFailure from './RepayFailure';
import RepaySuccess from './RepaySuccess';

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
