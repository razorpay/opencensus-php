import { Component } from 'react';
import { connect } from 'react-redux';
import { Route, Switch, NavLink } from 'react-router-dom';

import TestModeBanner from 'merchant/containers/TestModeBanner';

import SubMerchantList from './SubMerchant/List';
import Settings from './Settings';

@connect(state => ({
  partnerType: state.session.user.partner_type,
}))
export default class PartnerDashboard extends Component {
  render() {
    const isSettingsAccessible =
      ['full_managed', 'aggregator'].indexOf(this.props.partnerType) > -1;
    return (
      <tabbed-container>
        <header id="partner-header">
          <NavLink exact to="/submerchants">
            Affiliated Accounts
          </NavLink>
          {isSettingsAccessible && (
            <NavLink to="/submerchants/settings">Settings</NavLink>
          )}
        </header>
        <content>
          <Switch>
            {isSettingsAccessible && (
              <Route path="/submerchants/settings" component={Settings} />
            )}
            <Route path="/submerchants" component={SubMerchantList} />
          </Switch>
        </content>
      </tabbed-container>
    );
  }
}
