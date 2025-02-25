import React, { Suspense, useEffect } from 'react';
import { Box, Spinner } from '@razorpay/blade/components';
import { Route, Routes } from 'react-router-dom';

import ErrorBoundary, { Teams } from 'common/new-ui/ErrorBoundary';
import { RouteGuard } from 'merchant/components/ShowWhen';
import lazy from 'merchant/routes/LazyLoader';
import store from 'merchant/store';
import usePartnerPageNPS from 'merchant/views/PartnerDashboard/SubMerchant/utils/usePartnerPageNPS';
import useTrackPartnerExperiments from 'merchant/views/PartnerDashboard/SubMerchant/utils/useTrackPartnerExperiments';
import usePartnerDashboardExperiments from 'merchant/views/PartnerDashboard/hooks/usePartnerDashboardExperiments';
import PartnerReports from 'merchant_common/views/Reports/views/PartnerReports';

import Applications from './Applications';
import ClientAccounts from './ClientAccounts';
import Earnings from './Earnings';
import Home from './Home';
import AccountsAndSettings from './PartnerAccountsSettings';
import Settings from './Settings';
import Configuration from './Settings/configuration';
import SubMerchantList from './SubMerchant/List';
import Subvention from './Subvention';

const PartnerPlaybook = lazy(() =>
  import(
    /* webpackChunkName: "PartnerPlaybook" */ 'merchant/views/PartnerDashboard/PartnerPlaybook'
  ),
);

const PartnerManageTeam = lazy(() =>
  import(
    /* webpackChunkName: "PartnerManageTeam" */ 'merchant/views/PartnerDashboard/PartnerManageTeam'
  ),
);

const ManageTeamPosEkyc = lazy(() =>
  import(
    /* webpackChunkName: "PartnerManageTeam" */ 'merchant/views/PartnerDashboard/ManageTeamPosEkyc'
  ),
);

export default function PartnerDashboard() {
  const { isPartnerPlaybookEnabled, isAccountsListRevampEnabled, isPosEkycEnabled } =
    usePartnerDashboardExperiments();

  const user = store.getState().session.user;
  const isPartnershipFUX = user?.isPartnershipFUX || false;

  usePartnerPageNPS('mdYeQMQH');
  useTrackPartnerExperiments(user);
  useEffect(() => {
    if (isPartnershipFUX) {
      document.body.style.backgroundColor = '#eaedff';
    }
    return () => {
      if (isPartnershipFUX) {
        document.body.style.backgroundColor = null;
      }
    };
  }, [isPartnershipFUX]);
  return (
    <ErrorBoundary team={Teams?.PARTNERSHIP} resetOnProps>
      <Routes>
        <Route
          index
          element={
            <RouteGuard
              defaultPath="/partners/submerchants"
              additionalCondition={(user) => user.isPartner() && user.isPartnershipFUX}
            >
              <Home />
            </RouteGuard>
          }
        />

        <Route
          path="settings/*"
          element={
            <RouteGuard
              additionalCondition={(user) => user.isPartner('aggregator', 'fully_managed')}
            >
              <Settings />
            </RouteGuard>
          }
        />

        <Route
          path="config/*"
          element={
            <RouteGuard
              additionalCondition={(user) =>
                user.isPartner('aggregator', 'fully_managed') && user.isPartnershipForPhantomEnabled
              }
            >
              <Configuration />
            </RouteGuard>
          }
        />

        <Route
          path="applications/*"
          element={
            <RouteGuard additionalCondition={(user) => user.isPartner('pure_platform')}>
              <Applications />
            </RouteGuard>
          }
        />

        <Route
          path="earnings/*"
          element={
            <RouteGuard
              additionalCondition={(user) =>
                user.isAllowedView('earnings') && user.isHavingPartnerConfigs
              }
            >
              <Earnings />
            </RouteGuard>
          }
        />

        <Route
          path="subventions/*"
          element={
            <RouteGuard
              additionalCondition={(user) =>
                user.isAllowedView('earnings') && user.isHavingSubventionConfigs
              }
            >
              <Subvention />
            </RouteGuard>
          }
        />
        <Route
          path="reports/*"
          element={
            <RouteGuard
              additionalCondition={(user) =>
                !user.isPartner('reseller') || user.isHavingPartnerConfigs
              }
            >
              <PartnerReports />
            </RouteGuard>
          }
        />
        <Route
          path="playbook/*"
          element={
            <Suspense
              fallback={
                <Box minHeight="800px" display="flex" justifyContent="center" alignItems="center">
                  <Spinner accessibilityLabel="spinner" size="xlarge" />
                </Box>
              }
            >
              <RouteGuard additionalCondition={() => isPartnerPlaybookEnabled}>
                <PartnerPlaybook />
              </RouteGuard>
            </Suspense>
          }
        />
        <Route
          path="manage-team/*"
          element={
            <Suspense
              fallback={
                <Box minHeight="800px" display="flex" justifyContent="center" alignItems="center">
                  <Spinner accessibilityLabel="spinner" size="xlarge" />
                </Box>
              }
            >
              <RouteGuard additionalCondition={(user) => user.isAllowedTeamManagement}>
                <PartnerManageTeam />
              </RouteGuard>
            </Suspense>
          }
        />
        <Route
          path="accounts-settings/*"
          element={
            <Suspense
              fallback={
                <Box minHeight="800px" display="flex" justifyContent="center" alignItems="center">
                  <Spinner accessibilityLabel="spinner" size="xlarge" />
                </Box>
              }
            >
              <RouteGuard>
                <AccountsAndSettings />
              </RouteGuard>
            </Suspense>
          }
        />

        <Route
          path="submerchants/*"
          element={
            <RouteGuard>
              {isAccountsListRevampEnabled ? <ClientAccounts /> : <SubMerchantList />}
            </RouteGuard>
          }
        />

        <Route
          path="pos-ekyc-team"
          element={
            <RouteGuard additionalCondition={() => isPosEkycEnabled}>
              <ManageTeamPosEkyc />
            </RouteGuard>
          }
        />
      </Routes>
    </ErrorBoundary>
  );
}
