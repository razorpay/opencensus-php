import React, { useEffect } from 'react';
import { Route, Routes } from 'react-router-dom';
import { withRouter } from 'common/deprecated/withRouter';
import EntitiesOverview from './EntitiesOverview';
import Landing from './Landing';
import { track } from './common/tracking';

const TransactionsContainer = (): JSX.Element => {
  useEffect(() => {
    track({
      objectName: 'Transactions Page',
      actionName: 'Rendered',
    });
    setTimeout(() =>
      window.scrollTo({
        top: 0,
        left: 0,
        behavior: 'smooth',
      }),
    );
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  return (
    <div>
      <Routes>
        <Route path="/payments" element={Landing} />
        <Route path="/orders" element={Landing} />
        <Route path="/failed-payments" element={EntitiesOverview} />
        <Route path="/disputes" element={EntitiesOverview} />
        <Route path="/refunds" element={EntitiesOverview} />
        <Route path="/success-rate" element={EntitiesOverview} />
        <Route path="/refunds/batchuploads" element={EntitiesOverview} />
        <Route path="/refunds/batchupload" element={EntitiesOverview} />
      </Routes>
    </div>
  );
};

export default withRouter(TransactionsContainer);
