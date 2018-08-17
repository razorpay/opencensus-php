import React, { Component } from 'react';
import { Route, NavLink, withRouter } from 'react-router-dom';
import { connect } from 'react-redux';
import ShowWhen from 'merchant/components/ShowWhen';

import Profile from 'merchant/containers/Profile';
import AddFunds from 'merchant/containers/AddFunds';
import Credits from 'merchant/containers/Credits/List';
import Referrals from 'merchant/containers/Referrals/List';
import TeamManagement from 'merchant/containers/Team';

import { trackLinkClick } from './ga';

@connect(state => ({
  ...state.session,
}))
export default class MyAccount extends Component {
  render() {
    const { partner_type } = this.props.user;
    const isPartner = !!partner_type;
    const isNonPurePlatformPartner =
      isPartner && partner_type !== 'pure_platform';
    return (
      <tabbed-container>
        <header id="myaccount-header">
          <NavLink to="/profile">Profile</NavLink>

          <ShowWhen notMyRole="sellerapp agent support">
            <NavLink to="/credits">Credits</NavLink>
          </ShowWhen>

          <ShowWhen notMyRole="sellerapp agent">
            <NavLink to="/addfunds">Add Funds</NavLink>
          </ShowWhen>
          {!(isPartner && isNonPurePlatformPartner) && (
            <ShowWhen notMyRole="sellerapp agent" featureEnabled="Referral">
              <NavLink to="/referrals">Referrals</NavLink>
            </ShowWhen>
          )}

          <ShowWhen myRole="owner">
            <NavLink to="/team">Manage Team</NavLink>
          </ShowWhen>
        </header>
        <content>
          <Route path="/profile" component={Profile} />
          <Route path="/credits" component={Credits} />
          <Route path="/addfunds" component={AddFunds} />
          <Route path="/referrals" component={Referrals} />
          <Route path="/team" component={TeamManagement} />
        </content>
      </tabbed-container>
    );
  }
}
