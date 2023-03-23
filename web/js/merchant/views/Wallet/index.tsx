import React from 'react';
import { connect } from 'react-redux';
import { NavLink, Redirect, Route, Switch } from 'react-router-dom';

import Accounts from 'merchant/views/Wallet/Accounts';
import { SessionContext, WalletSession } from 'merchant/views/Wallet/context';
import BatchActions from 'merchant/views/Wallet/BatchActions';
import PopoverComponent, { PopoverBody, PopoverTitle } from 'common/ui/Popover';
import BatchOptions from 'merchant/views/Wallet/BatchActions/BatchOptions';
import ShowWhen from 'merchant/components/ShowWhen';

const WalletContainer = (session: WalletSession): JSX.Element => (
  <SessionContext.Provider value={session}>
    <div className="tabbed-container">
      <header>
        <ShowWhen additionalCondition={(user) => user.isIssuingBulkUploadEnabled}>
          <NavLink to="/wallet/batch-actions">Batch Actions</NavLink>
        </ShowWhen>
        <ShowWhen additionalCondition={(user) => user.isIssuingDashboardEnabled}>
          <NavLink to="/wallet/accounts">Accounts</NavLink>
        </ShowWhen>
        <ShowWhen additionalCondition={(user) => user.isIssuingDashboardEnabled}>
          <NavLink to="/wallet/transactions" exact>
            Transactions
          </NavLink>
        </ShowWhen>
        <ShowWhen additionalCondition={(user) => user.isIssuingDashboardEnabled}>
          <NavLink to="/wallet/funds" exact>
            Funds
          </NavLink>
        </ShowWhen>
        <ShowWhen additionalCondition={(user) => user.isIssuingBulkUploadEnabled}>
          <div className="pull-right MultiBatch--action">
            <div className="btn btn-primary">Upload New Batch</div>
            <PopoverComponent align="bottom" class="MultiBatch--popover">
              <PopoverTitle>
                <h4>
                  <strong>Upload New Batch</strong>
                </h4>
              </PopoverTitle>
              <PopoverBody>
                <BatchOptions />
              </PopoverBody>
            </PopoverComponent>
          </div>
        </ShowWhen>
      </header>
      <div className="content">
        <Switch>
          <Route path="/wallet/accounts" component={Accounts} />
          <Route path="/wallet/batch-actions" component={BatchActions} />
          <Redirect exact from="/wallet/" to="/wallet/batch-actions" />
        </Switch>
      </div>
    </div>
  </SessionContext.Provider>
);

export default connect((state) => ({ mode: state.session.mode }))(WalletContainer);
