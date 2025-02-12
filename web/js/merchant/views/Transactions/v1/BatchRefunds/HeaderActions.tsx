import React from 'react';
import { connect } from 'react-redux';
import { Store } from 'common/typings';
// eslint-disable-next-line no-restricted-imports
import HeaderAction from 'common/ui/HeaderAction';
import { useMobile } from 'common/hooks/useMobile';

interface TransactionsProps {
  children: JSX.Element;
}

const HeaderActions = ({ children }: TransactionsProps): JSX.Element => {
  const isMobile = useMobile();

  return !isMobile ? children : <HeaderAction responsive>{children}</HeaderAction>;
};

const mapStateToProps = (state: Store) => ({
  user: state.session.user,
});

export default connect(mapStateToProps)(HeaderActions);
