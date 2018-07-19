import React, { Component } from 'react';
import { Route, NavLink } from 'react-router-dom';
import ShowWhen from 'merchantLA/components/ShowWhen';

import Profile from 'merchantLA/containers/MyAccount/Profile';
import TeamManagement from 'merchantLA/containers/MyAccount/Team';

export default class MyAccount extends Component {
  render() {
    return (
      <tabbed-container>
        <header id="myaccount-header">
          <NavLink to="/profile">Profile</NavLink>
          <ShowWhen myRole="owner">
            <NavLink to="/team">Manage Team</NavLink>
          </ShowWhen>
        </header>
        <content>
          <Route path="/profile" component={Profile} />
          <Route path="/team" component={TeamManagement} />
        </content>
      </tabbed-container>
    );
  }
}
