import { Component } from 'react';
import { Switch, Route, NavLink, Redirect } from 'react-router-dom';

import ShowWhen, { ShowWhenRoute } from 'merchant/components/ShowWhen';

import Transactional from './Transactional/List';
import Daily from './Daily/List';

export default class EarningsContainer extends Component {
  render() {
    return (
      <tabbed-container>
        <header>
          <ShowWhen
            additionalCondition={user => user.isDailyCommissionsEnabled}
          >
            <NavLink exact to="/partners/earnings/daily">
              Daily Earnings
            </NavLink>
          </ShowWhen>
          <ShowWhen
            additionalCondition={user => user.isTransactionalCommissionsEnabled}
          >
            <NavLink exact to="/partners/earnings/transactional">
              Transactional Details
            </NavLink>
          </ShowWhen>
        </header>
        <content>
          <Switch>
            <Redirect
              to="/partners/earnings/daily"
              from="/partners/earnings"
              exact
            />
            <ShowWhenRoute
              path="/partners/earnings/transactional"
              component={Transactional}
              additionalCondition={user => !user.isPartner('reseller')}
            />
            <ShowWhenRoute path="/partners/earnings/daily" component={Daily} />
          </Switch>
        </content>
      </tabbed-container>
    );
  }
}
