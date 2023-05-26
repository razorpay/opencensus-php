import React from 'react';
import { connect } from 'react-redux';
import { NavLink, Route, Switch, Redirect } from 'react-router-dom';

import Funds from 'merchant/views/Wallet/Funds';
import Accounts from 'merchant/views/Wallet/Accounts';
import Transactions from 'merchant/views/Wallet/Transactions';
import Payments from 'merchant/views/Wallet/Payments';
import Loads from 'merchant/views/Wallet/Loads';

import { SessionContext, WalletSession } from 'merchant/views/Wallet/context';
import BatchActions from 'merchant/views/Wallet/BatchActions';
import PopoverComponent, { PopoverBody, PopoverTitle } from 'common/ui/Popover';
import BatchOptions from 'merchant/views/Wallet/BatchActions/BatchOptions';
import ShowWhen from 'merchant/components/ShowWhen';
import { walletPaths } from './constants';
import { QueryCache, ReactQueryCacheProvider } from 'react-query';

const queryCache = new QueryCache({
  defaultConfig: {
    queries: {
      retry: false,
      staleTime: 1000 * 60 * 60, // Consider data stale if older than an hour
    },
  },
});

const WalletContainer = (session: WalletSession): JSX.Element => (
  <ReactQueryCacheProvider queryCache={queryCache}>
    <SessionContext.Provider value={session}>
      <div className="tabbed-container">
        <header>
          <ShowWhen additionalCondition={(user) => user.isIssuingBulkUploadEnabled}>
            <NavLink to={walletPaths.batchActions}>Batch Actions</NavLink>
          </ShowWhen>
          <ShowWhen additionalCondition={(user) => user.isIssuingDashboardEnabled}>
            <NavLink to={walletPaths.accounts}>Accounts</NavLink>
          </ShowWhen>
          <ShowWhen additionalCondition={(user) => user.isIssuingDashboardEnabled}>
            <NavLink to={walletPaths.transactions} exact>
              Transactions
            </NavLink>
          </ShowWhen>
          <ShowWhen additionalCondition={(user) => user.isIssuingDashboardEnabled}>
            <NavLink to={walletPaths.funds}>Funds</NavLink>
          </ShowWhen>
          <ShowWhen additionalCondition={(user) => user.isIssuingDashboardEnabled}>
            <NavLink to={walletPaths.payments}>Payments</NavLink>
          </ShowWhen>
          <ShowWhen additionalCondition={(user) => user.isIssuingDashboardEnabled}>
            <NavLink to={walletPaths.loads}>Loads</NavLink>
          </ShowWhen>
          <ShowWhen
            additionalCondition={(user) =>
              user.isIssuingBulkUploadEnabled &&
              session.location.pathname === walletPaths.batchActions
            }
          >
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
            <Route path={walletPaths.accountDetail} component={Accounts} />
            <Route path={walletPaths.accounts} component={Accounts} />
            <Route path={walletPaths.batchActions} component={BatchActions} />
            <Route path={walletPaths.transactions} component={Transactions} />
            <Route path={walletPaths.funds} component={Funds} />
            <Route path={walletPaths.payments} component={Payments} />
            <Route path={walletPaths.loads} component={Loads} />

            <Redirect exact from={walletPaths.wallet} to={walletPaths.batchActions} />
          </Switch>
        </div>
      </div>
    </SessionContext.Provider>
  </ReactQueryCacheProvider>
);

export default connect((state) => ({
  mode: state.session?.mode,
  merchant_id: state.session?.user?.current,
}))(WalletContainer);
