import React, { Component } from 'react';
import { NavLink, Route } from 'react-router-dom';
import ModalContainer from 'store/modal';

import MerchantList from 'admin/merchants';

export default class App extends Component {
  state = {
    user: window.rzp_user,
    org: window.rzp_org,
  };

  render() {
    return (
      <div id="app-container">
        <main>
          <Route path="/merchants" component={MerchantList} />
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
}
