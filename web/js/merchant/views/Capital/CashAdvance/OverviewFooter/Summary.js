import React, { Fragment } from 'react';
import { withRouter, Link } from 'react-router-dom';

import Spinner from 'common/ui/Spinner';
import Button from 'common/new-ui/Button';
import Amount from 'common/ui/Amount';
import { CASH_ADVANCE_SECTIONS, REPAYMENT_VIEWS } from '../constants';
import moment from 'moment';

const Loader = () => {
  return (
    <div className="page-spinner-container">
      <Spinner />
    </div>
  );
};

const Summary = ({ setView, nextRepayableAmount, totalOwedAmount, loading, nextRepaymentDate }) => {
  const handleRepayNowClick = () => {
    setView(REPAYMENT_VIEWS.REPAY_METHOD);
  };

  const repaymentDate = nextRepaymentDate ? (
    <p>Scheduled for {moment.unix(nextRepaymentDate).format('MMMM D, YYYY [at] h a')}.</p>
  ) : (
    ''
  );

  return (
    <div className="repay-container summary">
      {loading ? (
        <Loader />
      ) : (
        <Fragment>
          <p className="title">Next Automatic Repayment</p>
          <div className="large-amount">
            <Amount value={nextRepayableAmount * 100} />
          </div>
          <div className="scheduled-text">
            <p>{totalOwedAmount === 0 ? 'No pending amount needs to be repaid.' : repaymentDate}</p>
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
            >
              View Repayments
            </Link>
          </div>
        </Fragment>
      )}
    </div>
  );
};

export default withRouter(Summary);
