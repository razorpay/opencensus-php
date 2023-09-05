import React from 'react';
import { connect } from 'react-redux';

import { useSplitzService } from 'common/splitz';
import { Store } from 'common/typings';
import TransactionsV1 from 'merchant/views/Transactions/v1';
import TransactionsV2 from 'merchant/views/Transactions/v2';
import { isTransactionsV2Enabled } from 'merchant/views/Transactions/v2/common/utils';

interface TransactionsProps {
  user: Store['session']['user'];
}

const Transactions = ({ user }: TransactionsProps): JSX.Element => {
  const splitz = useSplitzService();
  return isTransactionsV2Enabled(splitz, user) ? <TransactionsV2 /> : <TransactionsV1 />;
};

const mapStateToProps = (state: Store) => ({
  user: state.session.user,
});

export default connect(mapStateToProps)(Transactions);
