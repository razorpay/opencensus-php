import { Component } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router';

import ModalDialog from 'common/ui/ModalDialog';
import Notifications from 'common/ui/Notifications';
import { getItem, setItem, removeItem } from 'common/utils/localStorage';
import debounce from 'common/utils/debounce';
import Sidebar from 'merchantLA/containers/Sidebar';
import HeaderNav from 'merchantLA/components/HeaderNav';
import Content from 'merchantLA/components/Content';
import Footer from 'merchant/components/Footer';
import MerchantTour from 'merchantLA/containers/MerchantTour';
import * as ModalActions from 'merchant_common/reducers/modals';
import * as NotificationActions from 'merchant_common/reducers/notifications';
import * as SessionActions from 'merchantLA/reducers/session';
import { applyTheme } from 'merchant_common/helpers/themes';
import User from 'merchantLA/models/User';
import { resizeWindow } from 'merchantLA/reducers/app';
import rolesList from 'merchantLA/helpers/permissions/roles-list';
import LogoutDialog from '../../merchant/components/LogoutDialog';

@withRouter
@connect(
  (state) => ({
    ...state.session,
    windowWidth: state.app.windowWidth,
  }),
  {
    ...ModalActions,
    ...SessionActions,
    ...NotificationActions,
    resizeWindow,
  },
)
export default class App extends Component {
  pendingRequests = [];

  constructor(props) {
    super(props);

    const oldModeToken = 'rzp_mode';
    const oldModeValue = getItem(oldModeToken);

    // localizing mode for each merchant so that different modes can be maintained
    // across logins/merchants
    if (oldModeValue) {
      Object.keys(window.rzp_user.merchants).forEach((merchantId) => {
        setItem(`${oldModeToken}--${merchantId}`, oldModeValue);
      });

      removeItem(oldModeToken);
    }

    this.modeToken = `${oldModeToken}--${window.rzp_user.current}`;
    this.logoutPopupShown = false;
    this.state = {
      isLoading: true,
    };

    this.handleResize = debounce(this.handleResize.bind(this), 200);
  }

  componentWillMount() {
    // Event Based method to lock dashboard screen
    const user = window.rzp_user;
    const self = this;
    window.addEventListener('NOT_AUTHENTICATED', () => {
      if (self.logoutPopupShown) {
        return;
      }
      self.props.closeModal();
      self.props.openModal({
        size: 'large',
        component: <LogoutDialog user={user} />,
      });
      self.logoutPopupShown = true;
    });

    window.addEventListener('REQUEST_ERROR', (e) => {
      const errorCode = e.detail.response ? e.detail.response.status : 'UNKNOWN STATUS';
      if (window.ga) {
        window.ga(
          'send',
          'event',
          `LA Dashboard - ${errorCode} Error`,
          e.detail.url,
          e.detail.response,
        );
      }
    });

    let currentMode = getItem(this.modeToken);

    Promise.all([
      this.fetchUser().then(({ data }) => {
        const currentUser = data;
        const role = currentUser.userRole;

        if (!currentMode) {
          currentMode = currentUser.isActivated ? 'live' : 'test';
        } else if (!currentUser.isActivated) {
          currentMode = 'test';
        }

        this.props.updateSession({ mode: currentMode });
        this.redirectToRoute(role);

        return data;
      }),
      this.fetchOrg().then(({ data }) => {
        this.orgCode = data.custom_code;
        const orgCode = this.orgCode;
        if (orgCode && orgCode !== 'rzp') {
          applyTheme(orgCode);
        }
      }),
    ]).then(() => {
      this.props.updateSession({ mode: currentMode });

      const $splash = document.getElementById('splash');
      if ($splash) {
        $splash.parentElement.removeChild($splash);
      }

      this.setState({ isLoading: false });
    });
  }

  componentDidMount() {
    window.addEventListener('resize', this.handleResize);
  }

  componentWillReceiveProps({ user }) {
    if (user.isAuthenticated) {
      const role = user.userRole;
      this.redirectToRoute(role);
    }
  }

  componentWillUnmount() {
    window.removeEventListener('resize', this.handleResize);
  }

  fetchUser() {
    const user = new User(window.rzp_user);

    if (user) {
      this.props.updateSession({ user });

      // if the user is live but chose to browse in test mode,
      // it will be stored in rzp_mode
      let currentMode = getItem(this.modeToken);

      if (!currentMode) {
        currentMode = user.isActivated ? 'live' : 'test';
      } else if (!user.isActivated) {
        currentMode = 'test';
      }

      // making sure his current mode is remembered so that when he gets
      // activated, he wont be switched to live mode automatically
      // which may lead to mass confusion for merchants
      setItem(this.modeToken, currentMode);

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
    const org = window.rzp_org;
    if (org) {
      delete window.rzp_org;
      this.props.updateSession({ org });
      return Promise.resolve({ data: org });
    } else {
      return this.props.fetchOrg();
    }
  }

  redirectToRoute(role) {
    /* 
      Added a default fallback return null but i think don't even need
      any retun in the first place any stakeholder do check this logic once
     */
    const pathname = this.props.history.location.pathname;

    if (pathname === '/' || pathname === '/dashboard' || pathname === '/dashboard_v2') {
      switch (role) {
        case [rolesList.SELLERAPP]:
          return this.props.history.replace('/paymentlinks');
        case [rolesList.SUPPORT]:
          return this.props.history.replace('/payments');
        case null:
          return this.props.history.replace('/profile');
        default:
          return null;
      }
    }
    return null;
  }

  switchMode = (mode, callback) => {
    window.rzpAnalytics({
      eventCategory: 'LA Dashboard - Header',
      eventAction: 'Switch - Mode',
      eventLabel: mode,
    });
    const user = this.props.user;
    if (mode === 'live' && !user.isActivated) {
      this.props.showNotification({
        type: 'error',
        message:
          'Your account has not been activated yet. Your parent merchant needs to add your bank details to enable this.',
      });
    } else {
      if (callback) callback();
      setItem(this.modeToken, mode);
      location.reload();
    }
  };

  switchMerchant = (merchant) => {
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

  handleResize = () => {
    this.props.resizeWindow();
  };

  render() {
    const { user, org, mode, modeFormatted } = this.props;

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
      </div>
    );
  }
}
