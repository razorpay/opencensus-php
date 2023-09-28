import React from 'react';
import { connect } from 'react-redux';
import { NavLink, Route, Routes, Navigate } from 'react-router-dom';

import Funds from 'merchant/views/Wallet/Funds';
import Accounts from 'merchant/views/Wallet/Accounts';
import Transactions from 'merchant/views/Wallet/Transactions';
import Payments from 'merchant/views/Wallet/Payments';
import Loads from 'merchant/views/Wallet/Loads';

import { SessionContext, WalletSession } from 'merchant/views/Wallet/context';
import BatchActions from 'merchant/views/Wallet/BatchActions';
import PopoverComponent, { PopoverBody, PopoverTitle } from 'common/ui/Popover';
import BatchOptions from 'merchant/views/Wallet/BatchActions/BatchOptions';
import ShowWhen, { RouteGuard } from 'merchant/components/ShowWhen';
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
            <NavLink to={walletPaths.transactions} end>
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
          <Routes>
            <Route
              path={`${walletPaths.accountDetail.replace('/wallet/', '')}/*`}
              element={
                <RouteGuard>
                  <Accounts />
                </RouteGuard>
              }
            />
            <Route
              path={`${walletPaths.accounts.replace('/wallet/', '')}/*`}
              element={
                <RouteGuard>
                  <Accounts />
                </RouteGuard>
              }
            />
            <Route
              path={`${walletPaths.batchActions.replace('/wallet/', '')}/*`}
              element={
                <RouteGuard>
                  <BatchActions />
                </RouteGuard>
              }
            />
            <Route
              path={`${walletPaths.transactions.replace('/wallet/', '')}/*`}
              element={
                <RouteGuard>
                  <Transactions />
                </RouteGuard>
              }
            />
            <Route
              path={`${walletPaths.funds.replace('/wallet/', '')}/*`}
              element={
                <RouteGuard>
                  <Funds />
                </RouteGuard>
              }
            />
            <Route
              path={`${walletPaths.payments.replace('/wallet/', '')}/*`}
              element={
                <RouteGuard>
                  <Payments />
                </RouteGuard>
              }
            />
            <Route
              path={`${walletPaths.loads.replace('/wallet/', '')}/*`}
              element={
                <RouteGuard>
                  <Loads />
                </RouteGuard>
              }
            />

            <Route index element={<Navigate to={walletPaths.funds} replace />} />
          </Routes>
        </div>
      </div>
    </SessionContext.Provider>
  </ReactQueryCacheProvider>
);

export default connect((state) => ({
  mode: state.session?.mode,
  merchant_id: state.session?.user?.current,
}))(WalletContainer);
