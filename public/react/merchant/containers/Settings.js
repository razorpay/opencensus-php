import React, { Component } from 'react';
import { Route, NavLink, withRouter } from 'react-router-dom';
import ShowWhen from 'merchant/components/ShowWhen';

import Configuration from 'merchant/containers/Configuration';
import ApiKeys from 'merchant/containers/Keys/List';
import Webhooks from 'merchant/containers/Webhooks/List';

@withRouter
export default class Settings extends Component {
  render() {
    return (
      <tabbed-container>
        <header>
          <ShowWhen myRole="owner manager admin">
            <NavLink to="/app/config">Configuration</NavLink>
            <NavLink to="/app/webhooks">Webhooks</NavLink>
          </ShowWhen>

          <ShowWhen myRole="owner admin">
            <NavLink to="/app/keys">API Keys</NavLink>
          </ShowWhen>
        </header>

        <Route path="/app/config" component={Configuration} />
        <Route path="/app/webhooks" component={Webhooks} />
        <Route path="/app/keys" component={ApiKeys} />
      </tabbed-container>
    );
  }
}
