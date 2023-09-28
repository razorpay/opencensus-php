import { useEffect } from 'react';
import store from 'merchant/store';
import { Route, Routes } from 'react-router-dom';
import SubMerchantList from './SubMerchant/List';
import Settings from './Settings';
import Earnings from './Earnings';
import Subvention from './Subvention';
import Applications from './Applications';
import PartnerReports from 'merchant_common/views/Reports/views/PartnerReports';
import Home from './Home';
import ErrorBoundary, { Teams } from 'common/new-ui/ErrorBoundary';
import usePartnerPageNPS from 'merchant/views/PartnerDashboard/SubMerchant/utils/usePartnerPageNPS';
import useTrackPartnerExperiments from 'merchant/views/PartnerDashboard/SubMerchant/utils/useTrackPartnerExperiments';
import Configuration, { AppConfiguration } from './Settings/configuration';
import { RouteGuard } from 'merchant/components/ShowWhen';

export default function PartnerDashboard() {
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

        <Route path="applications/*">
          <Route
            path="applications/configuration/:id/*"
            element={
              <RouteGuard additionalCondition={(user) => user.isPartner('pure_platform')}>
                <AppConfiguration />
              </RouteGuard>
            }
          />

          <Route
            path="*"
            element={
              <RouteGuard additionalCondition={(user) => user.isPartner('pure_platform')}>
                <Applications />
              </RouteGuard>
            }
          />
        </Route>

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
          path="submerchants/*"
          element={
            <RouteGuard>
              <SubMerchantList />
            </RouteGuard>
          }
        />
      </Routes>
    </ErrorBoundary>
  );
}
