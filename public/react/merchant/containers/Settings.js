import React, { Component } from 'react';
import { Route, NavLink, withRouter } from 'react-router-dom';

import Configuration from 'merchant/containers/Configuration';
import ApiKeys from 'merchant/containers/Keys/List';
import Webhooks from 'merchant/containers/Webhooks/List';

@withRouter
export default class Settings extends Component {
  render() {
    return (
      <tabbed-container>
        <header>
          <NavLink to="/app/config">Configuration</NavLink>
          <NavLink to="/app/webhooks">Webhooks</NavLink>
          <NavLink to="/app/keys">API Keys</NavLink>
        </header>

        <Route path="/app/config" component={Configuration} />
        <Route path="/app/keys" component={ApiKeys} />
        <Route path="/app/webhooks" component={Webhooks} />
      </tabbed-container>
    );
  }
}
