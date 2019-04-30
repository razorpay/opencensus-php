import { Route, Switch, NavLink } from 'react-router-dom';

import Applications from 'merchant/containers/Applications';
import WriteApplicationEntity from 'merchant/containers/Applications/new';

export default function PartnerApplications() {
  return (
    <tabbed-container>
      <header>
        <NavLink exact to="/partners/applications">
          Applications
        </NavLink>
      </header>
      <content>
        <Switch>
          <Route
            path="/partners/applications/new"
            component={WriteApplicationEntity}
          />

          <Route
            path="/partners/applications/:id"
            component={WriteApplicationEntity}
          />
          <Route path="/partners/applications" component={Applications} />
        </Switch>
      </content>
    </tabbed-container>
  );
}
