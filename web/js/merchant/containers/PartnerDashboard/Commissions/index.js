import { Component } from 'react';
import { Switch, Route, NavLink } from 'react-router-dom';

import Transactional from './Transactional/List';
import Daily from './Daily/List';

export default class EarningsContainer extends Component {
  render() {
    return (
      <tabbed-container>
        <header>
          <NavLink exact to="/partners/earnings/daily">
            Daily Earnings
          </NavLink>
          <NavLink exact to="/partners/earnings/transactional">
            Transactional Details
          </NavLink>
        </header>
        <content>
          <Switch>
            <Route
              path="/partners/earnings/transactional"
              component={Transactional}
            />
            <Route path="/partners/earnings/daily" component={Daily} />
          </Switch>
        </content>
      </tabbed-container>
    );
  }
}
