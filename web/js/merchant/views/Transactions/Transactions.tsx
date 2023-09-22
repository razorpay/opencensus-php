import React, { Suspense } from 'react';
import { Box, Spinner } from '@razorpay/blade/components';
import { connect } from 'react-redux';

import { useSplitzService } from 'common/splitz';
import { Store } from 'common/typings';
import lazy from 'merchant/routes/LazyLoader';
import { isTransactionsV2Enabled } from 'merchant/views/Transactions/v2/common/utils';

const TransactionsV1 = lazy(
  () => import(/* webpackChunkName: "TransactionsV1" */ 'merchant/views/Transactions/v1'),
);

const TransactionsV2 = lazy(
  () => import(/* webpackChunkName: "TransactionsV2" */ 'merchant/views/Transactions/v2'),
);

interface TransactionsProps {
  user: Store['session']['user'];
}

const Transactions = ({ user }: TransactionsProps): JSX.Element => {
  const splitz = useSplitzService();
  return (
    <Suspense
      fallback={
        <Box display="flex" alignItems="center" justifyContent="center" height="100vh">
          <Spinner accessibilityLabel="Loading transactions" size="xlarge" />
        </Box>
      }
    >
      {isTransactionsV2Enabled(splitz, user) ? <TransactionsV2 /> : <TransactionsV1 />}
    </Suspense>
  );
};

const mapStateToProps = (state: Store) => ({
  user: state.session.user,
});

export default connect(mapStateToProps)(Transactions);
