import React from 'react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { connect } from 'react-redux';
import { NavLink, Route, Routes, Navigate } from 'react-router-dom';

import PopoverComponent, { PopoverBody, PopoverTitle } from 'common/ui/Popover';
import Accounts from 'merchant/views/Wallet/Accounts';
import BatchActions from 'merchant/views/Wallet/BatchActions';
import BatchOptions from 'merchant/views/Wallet/BatchActions/BatchOptions';
import Funds from 'merchant/views/Wallet/Funds';
import Loads from 'merchant/views/Wallet/Loads';
import Payments from 'merchant/views/Wallet/Payments';
import { Reports } from 'merchant/views/Wallet/Reports';
import Transactions from 'merchant/views/Wallet/Transactions';
import { SessionContext, WalletSession } from 'merchant/views/Wallet/context';
import ShowWhen, { RouteGuard } from 'merchant/components/ShowWhen';
import { walletPaths } from './constants';
import { useSplitzService } from 'common/splitz';
import { isExperimentEnabled } from 'common/splitz/utils';
import Campaigns from 'merchant/views/Wallet/Campaigns';
import { ToastContainer } from '@razorpay/blade/components';

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      retry: false,
      staleTime: 1000 * 60 * 60, // Consider data stale if older than an hour
    },
  },
});

const WalletContainer = (session: WalletSession): JSX.Element => {
  const splitz = useSplitzService();

  return (
    <QueryClientProvider client={queryClient}>
      <SessionContext.Provider value={session}>
        <div className="tabbed-container">
          <header>
            <ShowWhen
              additionalCondition={(user) =>
                user.isIssuingBulkUploadEnabled &&
                isExperimentEnabled(splitz?.abExperiments?.wallet_campaigns)
              }
            >
              <NavLink to={walletPaths.campaigns}>Campaigns</NavLink>
            </ShowWhen>
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
                user.isIssuingDashboardEnabled &&
                isExperimentEnabled(splitz?.abExperiments?.wallet_reports_experiment)
              }
            >
              <NavLink to={walletPaths.reports}>Reports</NavLink>
            </ShowWhen>
            <ShowWhen
              additionalCondition={(user) =>
                user.isIssuingBulkUploadEnabled &&
                session.location.pathname === walletPaths.batchActions
              }
            >
              <div className="pull-right MultiBatch--action">
                <div className="btn btn-primary">Upload New Batch</div>
                <PopoverComponent align="bottom" className="MultiBatch--popover">
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
                path={`${walletPaths.campaigns.replace('/wallet/', '')}/*`}
                element={
                  <RouteGuard>
                    <Campaigns />
                  </RouteGuard>
                }
              />
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
              <Route
                path={`${walletPaths.reports.replace('/wallet/', '')}/*`}
                element={
                  <RouteGuard>
                    <Reports />
                  </RouteGuard>
                }
              />
              <Route index element={<Navigate to={walletPaths.funds} replace />} />
            </Routes>
          </div>
          <ToastContainer />
        </div>
      </SessionContext.Provider>
    </QueryClientProvider>
  );
};

export default connect((state) => ({
  mode: state.session?.mode,
  merchant_id: state.session?.user?.current,
}))(WalletContainer);
