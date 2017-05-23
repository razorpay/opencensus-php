import { Component } from 'react';
import { connect } from 'react-redux';
import { HashRouter as Router } from 'react-router-dom';

import ModalDialog from 'rzp/ui/ModalDialog';
import Slider from 'rzp/ui/Slider';
import Notifications from 'rzp/ui/Notifications';
import Sidebar from 'merchant/components/Sidebar';
import HeaderNav from 'merchant/components/HeaderNav';
import Content from 'merchant/components/Content';
import ActivationRequired from 'merchant/components/ActivationRequired';
import * as ModalActions from 'rzp/modules/modals';
import * as NotificationActions from 'rzp/modules/notifications';
import * as SessionActions from 'merchant/modules/session';

// Will be moved to separate file
class Layout extends Component {
  render() {
    return (
      <div class="layout">
        {this.props.children}
      </div>
    );
  }
}

@connect(state => state.session, {
  ...ModalActions,
  ...SessionActions,
  ...NotificationActions,
})
export default class App extends Component {
  state = {
    isLoading: true,
  };

  componentWillMount() {
    let currentMode = localStorage.getItem('rzp_mode');

    if (currentMode) {
      this.props.updateSession({ mode: currentMode });
    }
    Promise.all([
      this.props.fetchUser().then(response => {
        if (!currentMode) {
          currentMode = parseInt(response.data.activated) === 1
            ? 'live'
            : 'test';
          this.props.updateSession({ mode: currentMode });
        }
      }),
      this.props.fetchOrg(),
    ]).then(() => {
      this.setState({ isLoading: false });
    });
  }

  switchMode = mode => {
    let user = this.props.user;
    if (mode === 'live' && parseInt(user.activated) !== 1) {
      this.props.openModal({
        size: 'small',
        component: <ActivationRequired onCloseClick={this.props.closeModal} />,
      });
    } else {
      localStorage.setItem('rzp_mode', mode);
      location.reload();
    }
  };

  switchMerchant = merchant => {
    this.props
      .switchMerchant(merchant.id)
      .then(() => {
        location.reload();
      })
      .catch(({ errors }) => {
        this.props.showNotification({
          type: 'error',
          message: errors,
        });
      });
  };

  logout = () => {};

  render() {
    let { user, mode, modeFormatted } = this.props;

    if (this.state.isLoading) {
      return null;
    }

    return (
      <Router basename="/app">
        <Layout>
          <HeaderNav
            user={user}
            mode={mode}
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
