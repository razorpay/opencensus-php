import { Component } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router';
import Smooch from 'smooch';

import ModalDialog from 'rzp/ui/ModalDialog';
import Notifications from 'rzp/ui/Notifications';
import ReactIdle from 'rzp/ui/ReactIdle';
import LocalStorageService from 'rzp/utils/localStorage';
import debounce from 'rzp/utils/debounce';
import Sidebar from 'merchantLA/containers/Sidebar';
import HeaderNav from 'merchantLA/components/HeaderNav';
import Content from 'merchantLA/components/Content';
import Footer from 'merchant/components/Footer';
import MerchantTour from 'merchant/containers/MerchantTour';
import IdleWarningDialog from 'merchant/components/IdleWarningDialog';
import * as ModalActions from 'rzp/modules/modals';
import * as NotificationActions from 'rzp/modules/notifications';
import * as SessionActions from 'merchantLA/modules/session';
import { applyTheme } from 'rzp/themes';
import User, { setFeatures } from 'merchantLA/models/User';
import { resizeWindow } from 'merchantLA/modules/app';

@withRouter
@connect(
  state => ({
    ...state.session,
    windowWidth: state.app.windowWidth,
  }),
  {
    ...ModalActions,
    ...SessionActions,
    ...NotificationActions,
    resizeWindow,
  }
)
export default class App extends Component {
  constructor(props) {
    super(props);

    const { user } = props;

    const oldModeToken = 'rzp_mode',
      oldModeValue = LocalStorageService.getItem(oldModeToken);

    // localizing mode for each merchant so that different modes can be maintained
    // across logins/merchants
    if (oldModeValue) {
      Object.keys(window.rzp_user.merchants).forEach(merchantId => {
        LocalStorageService.setItem(
          `${oldModeToken}--${merchantId}`,
          oldModeValue
        );
      });

      LocalStorageService.removeItem(oldModeToken);
    }

    this.modeToken = `${oldModeToken}--${window.rzp_user.current}`;

    this.state = {
      isLoading: true,
    };

    this.handleResize = debounce(this.handleResize.bind(this), 200);
  }

  componentWillMount() {
    let currentMode = LocalStorageService.getItem(this.modeToken);

    Promise.all([
      this.fetchUser().then(({ data }) => {
        let user = data;
        let role = user.userRole;

        if (!currentMode) {
          currentMode = user.isActivated ? 'live' : 'test';
        } else if (!user.isActivated) {
          currentMode = 'test';
        }

        this.props.updateSession({ mode: currentMode });
        this.redirectToRoute(role);

        setTimeout(() => {
          this.initSmooch(user);
        });
        return data;
      }),
      this.fetchOrg().then(({ data }) => {
        let orgCode = (this.orgCode = data.custom_code);
        if (orgCode && orgCode !== 'rzp') {
          applyTheme(orgCode);
        }
      }),
    ]).then(response => {
      this.props.updateSession({ mode: currentMode });

      let $splash = document.getElementById('splash');
      if ($splash) {
        $splash.parentElement.removeChild($splash);
      }

      this.setState({ isLoading: false });
      this.setState({ isLoading: false });
    });
  }

  componentDidMount() {
    window.addEventListener('resize', this.handleResize);
  }

  componentWillReceiveProps({ user, history }) {
    if (user.isAuthenticated) {
      let role = user.userRole;
      this.redirectToRoute(role);
    }
  }

  componentWillUnmount() {
    window.removeEventListener('resize', this.handleResize);
  }

  fetchUser() {
    let user = new User(window.rzp_user);

    if (user) {
      this.props.updateSession({ user });

      // if the user is live but chose to browse in test mode,
      // it will be stored in rzp_mode
      let currentMode = LocalStorageService.getItem(this.modeToken);

      if (!currentMode) {
        currentMode = user.isActivated ? 'live' : 'test';
      } else if (!user.isActivated) {
        currentMode = 'test';
      }

      // making sure his current mode is remembered so that when he gets
      // activated, he wont be switched to live mode automatically
      // which may lead to mass confusion for merchants
      LocalStorageService.setItem(this.modeToken, currentMode);

      if (user && user.user) {
        if (window.setRavenContext) {
          window.setRavenContext({
            mode: currentMode,
          });
        }

        window.rzpAnalytics({
          name: 'set_dimensions',
          dimensions: {
            dimension1: currentMode, // Mode
            dimension2: user.name, // Merchant Name
            dimension3: user.id, // Merchant ID
            dimension4: user.user.email, // Logged User Email
            dimension5: user.role, // Logged User Role
          },
        });
      }

      return Promise.resolve({ data: user });
    } else {
      return this.props.fetchUser();
    }
  }

  fetchOrg() {
    let org = window.rzp_org;
    if (org) {
      delete window.rzp_org;
      this.props.updateSession({ org });
      return Promise.resolve({ data: org });
    } else {
      return this.props.fetchOrg();
    }
  }

  redirectToRoute(role) {
    let pathname = this.props.history.location.pathname;

    if (
      pathname === '/' ||
      pathname === '/dashboard' ||
      pathname === '/dashboard_v2'
    ) {
      switch (role) {
        case 'sellerapp':
          let url = '/paymentlinks';
          return this.props.history.replace(url);
        case 'support':
          return this.props.history.replace('/payments');

        case null:
          return this.props.history.replace('/profile');
      }
    }
  }

  initSmooch(data) {
    if (location.hostname === 'dashboard.razorpay.com') {
      let role = data.userRole;
      Smooch.init({ appId: '54d849a9c99af8250046dbf8' }).then(function() {
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
            dashboardLink:
              location.origin + '/admin#/app/merchants/' + data.id + '/detail',
          },
        });
      });
    }
  }

  switchMode = mode => {
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Header',
      eventAction: 'Switch - Mode',
      eventLabel: mode,
    });
    let user = this.props.user;
    if (mode === 'live' && !user.isActivated) {
      this.props.openModal({
        size: 'small',
        component: (
          <ActivationRequired
            user={this.props.user}
            onCloseClick={this.props.closeModal}
          />
        ),
      });
    } else {
      LocalStorageService.setItem(this.modeToken, mode);
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

  lock = () => {
    let email = this.props.user.user.email;

    if (window.Raven && window.Raven.captureMessage) {
      window.Raven.captureMessage('Dashboard Locked', {
        level: 'info',
      });
    }

    return this.props.logout().then(() => {
      // waiting for 100ms more hoping raven call
      // would be resolved by then
      window.setTimeout(() => {
        location.hash = `/access/lockme/${email}`;
        location.reload();
      }, 100);
    });
  };

  showIdleWarning = () => {
    this.props.closeModal();
    this.props.openModal({
      component: <IdleWarningDialog countdown={15} />,
    });
  };

  handleResize = () => {
    this.props.resizeWindow();
  };

  render() {
    let { user, org, mode, modeFormatted } = this.props;

    if (this.state.isLoading || !user.isAuthenticated) {
      return null;
    }

    return (
      <div class={`layout ${this.orgCode}`}>
        <HeaderNav
          user={user}
          mode={mode}
          modeFormatted={modeFormatted}
          onSwitchMode={this.switchMode}
          onSwitchMerchant={this.switchMerchant}
          showMobileNav={this.props.windowWidth < 950}
        />
        <Sidebar user={user} logoURL={org.main_logo_url} />
        <Content user={user} modeFormatted={modeFormatted} />
        <Footer />

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

        <MerchantTour user={user} />
      </div>
    );
  }
}
