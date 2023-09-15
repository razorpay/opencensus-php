import React, { useEffect } from 'react';
import { Route, Switch, withRouter } from 'react-router-dom';

import EntitiesOverview from './EntitiesOverview';
import Landing from './Landing';
import { TransactionsEntityRoute } from './common/constants';
import { track } from './common/tracking';

const {
  PAYMENTS,
  ORDERS,
  FAILED_PAYMENTS,
  DISPUTES,
  SUCCESS_RATE,
  REFUNDS,
  BATCH_REFUNDS,
  BATCH_REFUNDS_UPLOAD,
} = TransactionsEntityRoute;

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
      <Switch>
        <Route path={[PAYMENTS, ORDERS]} component={Landing} />
        <Route
          path={[
            FAILED_PAYMENTS,
            DISPUTES,
            SUCCESS_RATE,
            REFUNDS,
            BATCH_REFUNDS,
            BATCH_REFUNDS_UPLOAD,
          ]}
          component={EntitiesOverview}
        />
      </Switch>
    </div>
  );
};

export default withRouter(TransactionsContainer);
