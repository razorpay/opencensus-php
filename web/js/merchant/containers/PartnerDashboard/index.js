import { Component } from 'react';
import { Route, Switch, NavLink } from 'react-router-dom';

import TestModeBanner from 'merchant/containers/TestModeBanner';

import SubMerchantList from './SubMerchant/List';
import Settings from './Settings';

export default () => (
  <tabbed-container>
    <header id="partner-header">
      <NavLink exact to="/submerchants">
        Affiliated Accounts
      </NavLink>
      <NavLink to="/submerchants/settings">Settings</NavLink>
    </header>
    <TestModeBanner />
    <content>
      <Switch>
        <Route path="/submerchants/settings" component={Settings} />
        <Route path="/submerchants" component={SubMerchantList} />
      </Switch>
    </content>
  </tabbed-container>
);
