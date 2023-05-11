import { Route, Switch, NavLink } from 'react-router-dom';

import Applications from 'merchant/views/Settings/Applications';
import WriteApplicationEntity from 'merchant/views/Settings/Applications/new';
import ErrorBoundary, { Teams } from 'common/new-ui/ErrorBoundary';

export default function PartnerApplications() {
  return (
    <tabbed-container>
      <header>
        <NavLink exact to="/partners/applications">
          Applications
        </NavLink>
      </header>
      <content>
        <ErrorBoundary team={Teams?.PARTNERSHIP} resetOnProps>
          <Switch>
            <Route path="/partners/applications/new" component={WriteApplicationEntity} />
            <Route path="/partners/applications/:id" component={WriteApplicationEntity} />
            <Route path="/partners/applications" component={Applications} />
          </Switch>
        </ErrorBoundary>
      </content>
    </tabbed-container>
  );
}
