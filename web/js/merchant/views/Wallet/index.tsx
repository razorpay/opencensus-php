import React from 'react';
import { connect } from 'react-redux';
import { NavLink, Redirect, Route, Switch } from 'react-router-dom';

import Accounts from 'merchant/views/Wallet/Accounts';
import { SessionContext, WalletSession } from 'merchant/views/Wallet/context';

const WalletContainer = (session: WalletSession): JSX.Element => (
  <SessionContext.Provider value={session}>
    <div className="tabbed-container">
      <header>
        <NavLink to="/wallet/accounts">Accounts</NavLink>
        <NavLink to="/wallet/transactions" exact>
          Transactions
        </NavLink>
        <NavLink to="/wallet/funds" exact>
          Funds
        </NavLink>
      </header>
      <div className="content">
        <div className="content-wrapper">
          <Switch>
            <Route path="/wallet/accounts" exact component={Accounts} />
            <Redirect to="/wallet/accounts" />
          </Switch>
        </div>
      </div>
    </div>
  </SessionContext.Provider>
);

export default connect((state) => ({ mode: state.session.mode }))(WalletContainer);
