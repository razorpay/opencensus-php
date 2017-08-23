import React, { Component } from 'react';
import { Route, Switch, NavLink, withRouter } from 'react-router-dom';
import ShowWhen from 'merchant/components/ShowWhen';
import TestModeBanner from 'merchant/containers/TestModeBanner';

import Configuration from 'merchant/containers/Configuration';
import ApiKeys from 'merchant/containers/Keys/List';
import Webhooks from 'merchant/containers/Webhooks/List';
import Applications from 'merchant/containers/Applications/';
import ApplicationsNew from 'merchant/containers/Applications/new';

@withRouter
export default class Settings extends Component {
  render() {
    return (
      <tabbed-container>
        <header id="settings-header">
          <ShowWhen myRole="owner manager admin">
            <NavLink to="/config">Configuration</NavLink>
          </ShowWhen>

          <ShowWhen myRole="owner manager admin">
            <NavLink to="/webhooks">Webhooks</NavLink>
          </ShowWhen>

          <ShowWhen myRole="owner admin">
            <NavLink to="/keys">API Keys</NavLink>
          </ShowWhen>

          <ShowWhen featureEnabled="Oauth" myRole="owner">
            <NavLink to="/applications">Applications</NavLink>
          </ShowWhen>
        </header>
        <TestModeBanner />
        <content>
          <Route path="/config" component={Configuration} />
          <Route path="/webhooks" component={Webhooks} />
          <Route path="/keys" component={ApiKeys} />
          <Switch>
            <Route exact path="/applications" component={Applications} />
            <Route exact path="/applications/new" component={ApplicationsNew} />
            <Route path="/applications/:id" component={ApplicationsNew} />
          </Switch>
        </content>
      </tabbed-container>
    );
  }
}
