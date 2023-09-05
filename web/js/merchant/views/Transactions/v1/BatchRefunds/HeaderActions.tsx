import React from 'react';
import { connect } from 'react-redux';
import { Store } from 'common/typings';
// eslint-disable-next-line no-restricted-imports
import HeaderAction from 'common/ui/HeaderAction';
import { useMobile } from 'common/hooks/useMobile';
import { useSplitzService } from 'common/splitz';
import { isTransactionsV2Enabled } from 'merchant/views/Transactions/v2/common/utils';

interface TransactionsProps {
  user: Store['session']['user'];
  children: JSX.Element;
}

const HeaderActions = ({ user, children }: TransactionsProps): JSX.Element => {
  const isMobile = useMobile();
  const splitz = useSplitzService();

  return isTransactionsV2Enabled(splitz, user) && !isMobile ? (
    children
  ) : (
    <HeaderAction responsive>{children}</HeaderAction>
  );
};

const mapStateToProps = (state: Store) => ({
  user: state.session.user,
});

export default connect(mapStateToProps)(HeaderActions);
