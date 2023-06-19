import { Route, Switch, NavLink } from 'react-router-dom';

import Applications from 'merchant/views/Settings/Applications';
import WriteApplicationEntity from 'merchant/views/Settings/Applications/new';
import ErrorBoundary, { Teams } from 'common/new-ui/ErrorBoundary';
import lazy from 'merchant/routes/LazyLoader';

// lazy loaded components
const AppConfiguration = lazy(() =>
  import('merchant/views/PartnerDashboard/Settings/configuration'),
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
          <NavLink exact to="/partners/applications">
            Applications
          </NavLink>
        </header>
      ) : null}
      <content>
        <ErrorBoundary team={Teams?.PARTNERSHIP} resetOnProps>
          <Switch>
            <Route path="/partners/applications/new" component={WriteApplicationEntity} />
            <Route path="/partners/applications/:id" component={WriteApplicationEntity} />
            <Route path="/partners/applications/configuration/:id" component={AppConfiguration} />
            <Route path="/partners/applications" component={Applications} />
          </Switch>
        </ErrorBoundary>
      </content>
    </tabbed-container>
  );
}
