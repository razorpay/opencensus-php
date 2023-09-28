import React, { useEffect } from 'react';

import { withRouter } from 'common/deprecated/withRouter';
import Button from 'common/new-ui/Button';
import Amount from 'common/ui/Amount';
import {
  trackRepaymentClose,
  trackRepaymentFailure,
  trackRepaymentRetry,
} from 'merchant/views/Capital/CashAdvance/TrackEvents/trackEvents';
import { REPAYMENT_VIEWS } from 'merchant/views/Capital/CashAdvance/constants';

const RepayFailure = ({ setView, resultAmounts, location: { pathname } }) => {
  const handleCloseClick = () => {
    trackRepaymentClose(pathname, 'button', resultAmounts);
    setView(REPAYMENT_VIEWS.REPAY_AMOUNT);
  };
  const handleRetryRepaymentClick = () => {
    trackRepaymentRetry(pathname, resultAmounts);
    setView(REPAYMENT_VIEWS.REPAY_AMOUNT);
  };
  useEffect(() => {
    if (resultAmounts.repayAmount > 0) {
      trackRepaymentFailure(pathname, resultAmounts);
    }
  }, [resultAmounts]);
  return (
    <div className="failure">
      <div style={{ alignItems: 'center' }} className="flex">
        <i className="i i-info-circle text-danger mt-2 mr-10" />
        <h1 className="title">Repayment Failure!</h1>
      </div>
      <div className="description">
        <p>
          Oops, Your repayment has been failed due to some internal error.
          <br />
          Incase if any money has been debited from your account or settlement balance, it will be
          added back within 1-2 days.
        </p>
      </div>
      <div className="details flex">
        <div className="repayment-amount">
          <div className="details--heading">Repayment Amount</div>
          <div className="details--amount">
            <Amount currency="INR" value={resultAmounts.repayAmount} />
          </div>
        </div>
        <div className="repayment-via">
          <div className="details--heading">Attachment Via</div>
          <div className="flex repayment-via--details">
            {resultAmounts.settlementAmount !== 0 && (
              <div
                className={`settlement-balance${
                  resultAmounts.bankAmount !== 0 ? ' border-right' : ''
                }`}
              >
                Settlement Balance
              </div>
            )}
            {resultAmounts.bankAmount !== 0 && <div>Netbanking / UPI</div>}
          </div>
        </div>
        <div>
          <button className="btn btn-primary mr-24" onClick={handleRetryRepaymentClick}>
            Retry Repayment
          </button>
          <Button.Transparent onClick={handleCloseClick}>Close</Button.Transparent>
        </div>
      </div>
    </div>
  );
};

export default withRouter(RepayFailure);
