import React, { Component } from 'react';
import { Route, NavLink, withRouter } from 'react-router-dom';
import ShowWhen from 'merchant/components/ShowWhen';

import Profile from 'merchant/containers/Profile';
import Activation from 'merchant/containers/Activation';
import AddFunds from 'merchant/containers/AddFunds';
import Credits from 'merchant/containers/Credits/List';
import Referrals from 'merchant/containers/Referrals/List';

export default class MyAccount extends Component {
  render() {
    return (
      <tabbed-container>
        <header id="myaccount-header">
          <NavLink to="/app/profile">Profile</NavLink>
          <ShowWhen myRole="owner manager admin">
            <NavLink to="/app/activation">Activation</NavLink>
          </ShowWhen>

          <ShowWhen notMyRole="sellerapp support">
            <NavLink to="/app/credits">Credits</NavLink>
          </ShowWhen>

          <ShowWhen notMyRole="sellerapp">
            <NavLink to="/app/addfunds">Add Funds</NavLink>
          </ShowWhen>

          <ShowWhen notMyRole="sellerapp support" featureEnabled="Referral">
            <NavLink to="/app/referrals">Referrals</NavLink>
          </ShowWhen>
        </header>

        <Route path="/app/profile" component={Profile} />
        <Route path="/app/activation" component={Activation} />
        <Route path="/app/credits" component={Credits} />
        <Route path="/app/addfunds" component={AddFunds} />
        <Route path="/app/referrals" component={Referrals} />
      </tabbed-container>
    );
  }
}
