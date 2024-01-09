import { Route, Routes, NavLink } from 'react-router-dom';

import Applications from 'merchant/views/Settings/Applications';
import WriteApplicationEntity from 'merchant/views/Settings/Applications/new';
import ErrorBoundary, { Teams } from 'common/new-ui/ErrorBoundary';
import lazy from 'merchant/routes/LazyLoader';
import { RouteGuard } from 'merchant/components/ShowWhen';

// lazy loaded components
const AppConfiguration = lazy(() =>
  import('merchant/views/PartnerDashboard/Settings/configuration').then((module) => ({
    default: module.AppConfiguration,
  })),
);

export default function PartnerApplications(props) {
  const { location } = props;
  const showHeader =
    location.pathname === '/partners/applications' ||
    location.pathname === '/partners/applications/new';
  return (
    <tabbed-container>
      {showHeader ? (
        <header>
          <NavLink end to="/partners/applications">
            Applications
          </NavLink>
        </header>
      ) : null}
      <content>
        <ErrorBoundary team={Teams?.PARTNERSHIP} resetOnProps>
          <Routes>
            <Route
              path="new/*"
              element={
                <RouteGuard>
                  <WriteApplicationEntity />
                </RouteGuard>
              }
            />
            <Route
              path=":id/*"
              element={
                <RouteGuard>
                  <WriteApplicationEntity />
                </RouteGuard>
              }
            />
            <Route
              path="configuration/:id/*"
              element={
                <RouteGuard>
                  <AppConfiguration />
                </RouteGuard>
              }
            />
            <Route
              path="*"
              element={
                <RouteGuard>
                  <Applications />
                </RouteGuard>
              }
            />
          </Routes>
        </ErrorBoundary>
      </content>
    </tabbed-container>
  );
}
