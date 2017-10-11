import React, { Component } from 'react';
import {
  NavLink,
  Route,
  matchPath,
  withRouter,
  Switch,
} from 'react-router-dom';
import ModalContainer, { openSlider, closeSlider } from 'common/modal';

import MerchantList from 'admin/merchants/list';
import PlanList from 'admin/plans/list';
import EntityList from 'admin/entities/list';

import MerchantEntity from 'admin/merchants/entity';

@withRouter
export default class App extends Component {
  componentWillMount() {
    this.setBaseLocation(this.props.location);
  }

  componentWillReceiveProps(props) {
    this.setBaseLocation(props.location);
  }

  render() {
    return (
      <div id="app-container">
        <main>
          {(this.location && (
            <Switch location={this.location}>
              <Route path="/merchants" component={MerchantList} />
              <Route path="/plans" component={PlanList} />
              <Route path="/entities" component={EntityList} />
            </Switch>
          )) ||
            null}
        </main>
        <header />
        <aside>
          <a href="/admin">
            <img src="https://cdn.razorpay.com/logo_invert.svg" width="146" />
          </a>
          <label>Management</label>
          <NavLink to="/merchants">Merchants</NavLink>
          <NavLink to="/stats">Merchant Stats</NavLink>
          <NavLink to="/plans">Pricing Plans</NavLink>
          <NavLink to="/entities">Entities</NavLink>
          <NavLink to="/actions">Actions</NavLink>
          <NavLink to="/email-logs">Email Logs</NavLink>
        </aside>
        <ModalContainer />
      </div>
    );
  }

  setBaseLocation(location) {
    for (let route in entityRoutes) {
      var match = matchPath(location.pathname, route);
      if (match) {
        var EntityView = entityRoutes[route];
        return openSlider({
          component: <EntityView id={match.params.id} />,
          closeUrl: location.pathname,
        });
      }
    }
    closeSlider();
    this.location = location;
  }
}

const entityRoutes = {
  '/merchants/:id': MerchantEntity,
};
