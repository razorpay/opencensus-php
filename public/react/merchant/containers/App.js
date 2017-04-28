import { Component } from 'react';
import { connect } from 'react-redux';
import { HashRouter as Router } from 'react-router-dom';
import { fetchUser, fetchOrg } from 'merchant/modules/session';

import Sidebar from 'merchant/components/Sidebar';
import HeaderNav from 'merchant/components/HeaderNav';
import Content from 'merchant/components/Content';

@connect(state => state.session, {
  fetchUser,
  fetchOrg,
})
export default class App extends Component {
  componentWillMount() {
    return Promise.all([this.props.fetchUser(), this.props.fetchOrg()]);
  }

  switchMode = () => {};

  switchMerchant = () => {};

  logout = () => {};

  render() {
    let { user, modeFormatted } = this.props;

    if (!user) {
      return null;
    }

    return (
      <Router>
        <div class="layout">
          <HeaderNav
            user={user}
            modeFormatted={modeFormatted}
            onSwitchMode={this.switchMode}
            onSwitchMerchant={this.switchMerchant}
            onLogout={this.logout}
          />
          <Sidebar user={user} />
          <Content />
        </div>
      </Router>
    );
  }
}
