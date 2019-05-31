import { Switch, NavLink, Redirect, Route } from 'react-router-dom';

import Transactional from './Transactional/List';
import Daily from './Daily/List';

export default function SubventionContainer() {
  return (
    <tabbed-container>
      <header>
        <NavLink exact to="/partners/subvention/daily">
          Daily Subvention
        </NavLink>
        <NavLink exact to="/partners/subvention/transactional">
          Subvention Per Transaction
        </NavLink>
      </header>
      <content>
        <Switch>
          <Redirect
            to="/partners/subvention/daily"
            from="partners/subvention"
            exact
          />

          <Route path="/partners/subvention/daily" component={Daily} />

          <Route
            path="/partners/subvention/transactional"
            component={Transactional}
          />
        </Switch>
      </content>
    </tabbed-container>
  );
}
