import { Component } from 'react';
import { connect } from 'react-redux';
import { Router } from 'react-router-dom';
import { fetchUser, fetchOrg } from 'merchant/modules/session';

import Sidebar from 'merchant/components/Sidebar';
import HeaderNav from 'merchant/components/HeaderNav';
import Content from 'merchant/components/Content';
import ModalDialog from 'rzp/ui/ModalDialog';
import Slider from 'rzp/ui/Slider';
import Notifications from 'rzp/ui/Notifications';
import createHashHistory from 'history/createHashHistory';

const history = createHashHistory();
const push = history.push;
let shouldNotify = true;

history.push = function(path, options = {}, state) {
  if (options.notify === false) {
    shouldNotify = false;
  } else {
    shouldNotify = true;
  }
  push(path, state);
};

// Will be moved to separate file
class Layout extends Component {
  shouldComponentUpdate() {
    if (!shouldNotify) {
      shouldNotify = true;
      return false;
    }
    return true;
  }

  render() {
    return (
      <div class="layout">
        {this.props.children}
      </div>
    );
  }
}

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
      <Router history={history}>
        <Layout>
          <HeaderNav
            user={user}
            modeFormatted={modeFormatted}
            onSwitchMode={this.switchMode}
            onSwitchMerchant={this.switchMerchant}
            onLogout={this.logout}
          />
          <Sidebar user={user} />
          <Content />

          {/* Creates Portal for the comp */}
          <ModalDialog />
          <Slider />
          <Notifications />
        </Layout>
      </Router>
    );
  }
}
