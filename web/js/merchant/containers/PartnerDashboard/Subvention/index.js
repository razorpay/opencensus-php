import { Switch, NavLink, Redirect, Route } from 'react-router-dom';

import Transactional from './Transactional/List';
import Daily from './Daily/List';

export default function SubventionContainer() {
  return (
    <tabbed-container>
      <header>
        <NavLink exact to="/partners/subventions/daily">
          Daily Subvention
        </NavLink>
        <NavLink exact to="/partners/subventions/transactional">
          Subvention Per Transaction
        </NavLink>
      </header>
      <content>
        <Switch>
          <Redirect
            to="/partners/subventions/daily"
            from="partners/subventions"
            exact
          />

          <Route path="/partners/subventions/daily" component={Daily} />

          <Route
            path="/partners/subventions/transactional"
            component={Transactional}
          />
        </Switch>
      </content>
    </tabbed-container>
  );
}
