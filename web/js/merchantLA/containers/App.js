import { Component } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router';

import ModalDialog from 'common/ui/ModalDialog';
import Notifications from 'common/ui/Notifications';
import LocalStorageService from 'common/utils/localStorage';
import debounce from 'common/utils/debounce';
import Sidebar from 'merchantLA/containers/Sidebar';
import HeaderNav from 'merchantLA/components/HeaderNav';
import Content from 'merchantLA/components/Content';
import Footer from 'merchant/components/Footer';
import MerchantTour from 'merchant/containers/MerchantTour';
import PasswordReLogin from 'merchant_common/components/PasswordReLogin';
import * as ModalActions from 'merchant_common/reducers/modals';
import * as NotificationActions from 'merchant_common/reducers/notifications';
import * as SessionActions from 'merchantLA/reducers/session';
import { applyTheme } from 'merchant_common/helpers/themes';
import User, { setFeatures } from 'merchantLA/models/User';
import { resizeWindow } from 'merchantLA/reducers/app';

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
  pendingRequests = [];

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

  resumePendingRequests = () => {
    for (let i = 0; i < this.pendingRequests.length; i++) {
      this.pendingRequests[i]();
    }
  };

  registerPendingRequests(req) {
    this.pendingRequests.push(req);
  }

  componentWillMount() {
    // Event Based method to lock dashboard screen
    const self = this;
    window.addEventListener('NOT_AUTHENTICATED', function(e) {
      self.registerPendingRequests(e.detail.continueAjax);

      if (this.isDashboardLocked) {
        return;
      }

      self.lockDashboard(self.resumePendingRequests);
    });

    window.addEventListener('REQUEST_ERROR', function(e) {
      const errorCode = e.detail.response
        ? e.detail.response.status
        : 'UNKNOWN STATUS';

      window.ga &&
        window.ga(
          'send',
          'event',
          `LA Dashboard - ${errorCode} Error`,
          e.detail.url,
          e.detail.response
        );
    });

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

        window.trackHubs({
          name: 'identify',
          id: user.id,
          email: user.user.email,
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

  switchMode = mode => {
    window.rzpAnalytics({
      eventCategory: 'LA Dashboard - Header',
      eventAction: 'Switch - Mode',
      eventLabel: mode,
    });
    let user = this.props.user;
    if (mode === 'live' && !user.isActivated) {
      this.props.showNotification({
        type: 'error',
        message:
          'Your account has not been activated yet. Your parent merchant needs to add your bank details to enable this.',
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

  lockDashboard = cb => {
    let email = this.props.user.user.email;

    if (window.Raven && window.Raven.captureMessage) {
      window.Raven.captureMessage('Dashboard Locked', {
        level: 'info',
      });
    }

    this.resumeLockActionCB = cb;

    this.setState({ isDashboardLocked: true });
  };

  removeLockScreen = () => {
    this.resumeLockActionCB = undefined;
    this.setState({ isDashboardLocked: false });
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
        <Footer user={user} />

        {/* Creates Portal for the comp */}
        <ModalDialog />
        <Notifications />
        <MerchantTour user={user} />

        {this.state.isDashboardLocked && (
          <PasswordReLogin
            merchantId={user.current}
            userEmail={user.user.email}
            removeLockScreen={this.removeLockScreen}
            showNotification={this.props.showNotification}
            resumeLockActionCB={this.resumeLockActionCB}
          />
        )}
      </div>
    );
  }
}
