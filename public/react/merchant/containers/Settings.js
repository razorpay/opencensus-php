import React, { Component } from 'react';
import { NavLink, withRouter } from 'react-router-dom';

import Configuration from 'merchant/containers/Configuration';
import Keys from 'merchant/containers/Keys/List';
import Webhooks from 'merchant/containers/Webhooks/List';

const components = {
  config: <Configuration />,
  keys: <Keys />,
  webhooks: <Webhooks />,
};

@withRouter
export default class Settings extends Component {
  render() {
    let urlFragments = this.props.location.pathname.match(/\/app\/(\w+)/);

    let component = components[urlFragments[1]];

    return (
      <tabbed-container>
        <header>
          <NavLink to="/app/config">Configuration</NavLink>
          <NavLink to="/app/webhooks">Webhooks</NavLink>
          <NavLink to="/app/keys">API Keys</NavLink>
        </header>
        {component}
      </tabbed-container>
    );
  }
}
