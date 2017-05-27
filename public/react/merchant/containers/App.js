import { Component } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router';

import ModalDialog from 'rzp/ui/ModalDialog';
import Slider from 'rzp/ui/Slider';
import Notifications from 'rzp/ui/Notifications';
import ReactIdle from 'rzp/ui/ReactIdle';
import Sidebar from 'merchant/components/Sidebar';
import HeaderNav from 'merchant/components/HeaderNav';
import Content from 'merchant/components/Content';
import ActivationRequired from 'merchant/components/ActivationRequired';
import IdleWarningDialog from 'merchant/components/IdleWarningDialog';
import * as ModalActions from 'rzp/modules/modals';
import * as NotificationActions from 'rzp/modules/notifications';
import * as SessionActions from 'merchant/modules/session';
import { applyTheme } from 'rzp/themes';

@withRouter
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
      this.props.fetchUser().then(({ data }) => {
        let role = data.merchants[data.current].pivot.role;

        if (!currentMode) {
          currentMode = parseInt(data.activated) === 1 ? 'live' : 'test';
          this.props.updateSession({ mode: currentMode });
        }

        if (role === 'sellerapp') {
          this.props.history.replace('/invoices');
        }
        this.initSmooch(data);
      }),
      this.props.fetchOrg().then(({ data }) => {
        let orgCode = (this.orgCode = data.custom_code);
        if (orgCode && orgCode !== 'rzp') {
          applyTheme(orgCode);
        }
      }),
    ]).then(() => {
      this.setState({ isLoading: false });
    });
  }

  initSmooch(data) {
    debugger;
    let role = data.merchants[data.current].pivot.role;
    if (window.smoochScript) {
      smoochScript.then(function() {
        var sk_user = function() {
          if (window.skIntro) {
            window.skIntro.html('');
          }
          $('#sk-footer input').off('focus', window.skFocusListener);
          window.smoochUserLoaded = true;
          Smooch.updateUser({
            givenName: data.name,
            email: data.email,
            properties: {
              id: data.id,
              activated: data.activated,
              locked: data.locked,
              submitted: data.submitted,
              role: role,
              userEmail: data.user.email,
              dashboardLink: location.origin +
                '/admin#/app/merchants/' +
                data.id +
                '/detail',
            },
          });
        };

        if (Smooch._rzpReady) {
          sk_user();
        } else {
          Smooch.on('ready', function() {
            sk_user();
          });
        }
      });
    }
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

  logout = () => {
    return this.props.logout().then(() => {
      location.reload();
    });
  };

  lock = () => {
    let email = this.props.user.contact_email;
    return this.props.logout().then(() => {
      location.hash = `/access/lockme/${email}`;
      location.reload();
    });
  };

  showIdleWarning = () => {
    this.props.closeModal();
    this.props.openModal({
      component: <IdleWarningDialog countdown={15} />,
    });
  };

  render() {
    let { user, mode, modeFormatted } = this.props;

    if (this.state.isLoading || !user) {
      return null;
    }

    return (
      <div id="react-root" class={`react-root layout ${this.orgCode}`}>
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
        <Notifications />
        <ReactIdle
          idleDuration={15 * 60}
          warningDuration={15}
          onIdleStart={this.showIdleWarning}
          onIdleEnd={this.props.closeModal}
          onIdleTimeout={this.lock}
        />
      </div>
    );
  }
}
