import React, { Component } from 'react';
import { Route, NavLink, withRouter } from 'react-router-dom';

import Activation from 'merchant/containers/Activation';
import AddFunds from 'merchant/containers/AddFunds';
import Credits from 'merchant/containers/Credits/List';

@withRouter
export default class MyAccount extends Component {
  render() {
    return (
      <tabbed-container>
        <header>
          <NavLink to="/app/activation">Activation</NavLink>
          <NavLink to="/app/credits">Credits</NavLink>
          <NavLink to="/app/addfunds">Add Funds</NavLink>
        </header>

        <Route path="/app/activation" component={Activation} />
        <Route path="/app/credits" component={Credits} />
        <Route path="/app/addfunds" component={AddFunds} />
      </tabbed-container>
    );
  }
}
