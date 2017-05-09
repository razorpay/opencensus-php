import React, { Component } from 'react';
import { NavLink, withRouter } from 'react-router-dom';

import Activation from 'merchant/containers/Activation';
import AddFunds from 'merchant/containers/AddFunds';
import Credits from 'merchant/containers/Credits/List';

const components = {
  activation: <Activation />,
  addfunds: <AddFunds />,
  credits: <Credits />,
};

@withRouter
export default class MyAccount extends Component {
  render() {
    let urlFragments = this.props.location.pathname.match(/\/app\/(\w+)/);

    let component = components[urlFragments[1]];

    return (
      <tabbed-container>
        <header>
          <NavLink to="/app/profile">Profile</NavLink>
          <NavLink to="/app/activation">Activation</NavLink>
          <NavLink to="/app/credits">Credits</NavLink>
          <NavLink to="/app/addfunds">Add Funds</NavLink>
        </header>
        {component}
      </tabbed-container>
    );
  }
}
