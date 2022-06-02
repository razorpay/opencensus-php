import React, { Fragment } from 'react';
import { withRouter, Link } from 'react-router-dom';

import Spinner from 'common/ui/Spinner';
import Button from 'common/new-ui/Button';
import Amount from 'common/ui/Amount';
import { CASH_ADVANCE_SECTIONS, REPAYMENT_VIEWS } from '../constants';
import { trackRepayNow, trackViewRepayments } from '../TrackEvents/trackEvents';

const Loader = () => {
  return (
    <div className="page-spinner-container">
      <Spinner />
    </div>
  );
};

const Summary = ({
  setView,
  currentOutstandingTotalAmount,
  totalOwedAmount,
  loading,
  location: { pathname = '' },
}) => {
  const handleRepayNowClick = () => {
    trackRepayNow(pathname);
    setView(REPAYMENT_VIEWS.REPAY_AMOUNT);
  };

  return (
    <div className="repay-container summary">
      {loading ? (
        <Loader />
      ) : (
        <Fragment>
          <p className="title">Current Outstanding Amount</p>
          <div className="large-amount">
            <Amount value={currentOutstandingTotalAmount} />
          </div>
          <div className="scheduled-text">
            <p>{totalOwedAmount === 0 ? 'No pending amount needs to be repaid.' : 'As of today'}</p>
          </div>
          <div className="repay-container__ctas">
            {totalOwedAmount !== 0 ? (
              <Button.Primary disabled={loading} className="mr-16" onClick={handleRepayNowClick}>
                Repay Now
              </Button.Primary>
            ) : null}
            <Link
              to={`/capital/cash-advance/${CASH_ADVANCE_SECTIONS.REPAYMENTS_SCHEDULE}`}
              class="btn btn-outline"
              onClick={() => trackViewRepayments(pathname)}
            >
              View Past Repayments
            </Link>
          </div>
        </Fragment>
      )}
    </div>
  );
};

export default withRouter(Summary);
