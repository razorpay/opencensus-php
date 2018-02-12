import React, { Component } from 'react';
import { Route, NavLink, withRouter } from 'react-router-dom';
import ShowWhen from 'merchant/components/ShowWhen';

import Profile from 'merchant/containers/Profile';
import Activation from 'merchant/containers/Activation';
import AddFunds from 'merchant/containers/AddFunds';
import Credits from 'merchant/containers/Credits/List';
import Referrals from 'merchant/containers/Referrals/List';
import TeamManagement from 'merchant/containers/Team';

export default class MyAccount extends Component {
  render() {
    return (
      <tabbed-container>
        <header id="myaccount-header">
          <NavLink to="/profile">Profile</NavLink>
          <ShowWhen myRole="owner manager admin">
            <NavLink to="/activation">Activation</NavLink>
          </ShowWhen>

          <ShowWhen notMyRole="sellerapp support">
            <NavLink to="/credits">Credits</NavLink>
          </ShowWhen>

          <ShowWhen notMyRole="sellerapp">
            <NavLink to="/addfunds">Add Funds</NavLink>
          </ShowWhen>

          <ShowWhen notMyRole="sellerapp support" featureEnabled="Referral">
            <NavLink to="/referrals">Referrals</NavLink>
          </ShowWhen>

          <ShowWhen myRole="owner">
            <NavLink to="/team">Manage Team</NavLink>
          </ShowWhen>
        </header>
        <content>
          <Route path="/profile" component={Profile} />
          <Route path="/activation" component={Activation} />
          <Route path="/credits" component={Credits} />
          <Route path="/addfunds" component={AddFunds} />
          <Route path="/referrals" component={Referrals} />
          <Route path="/team" component={TeamManagement} />
        </content>
      </tabbed-container>
    );
  }
}
