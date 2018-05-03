import { Component } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router';
import Smooch from 'smooch';

import ModalDialog from 'rzp/ui/ModalDialog';
import Notifications from 'rzp/ui/Notifications';
import ReactIdle from 'rzp/ui/ReactIdle';
import LocalStorageService from 'rzp/utils/localStorage';
import Sidebar from 'merchant/containers/Sidebar';
import HeaderNav from 'merchant/components/HeaderNav';
import Content from 'merchant/components/Content';
import Footer from 'merchant/components/Footer';
import MerchantTour from 'merchant/containers/MerchantTour';
import ActivationRequired from 'merchant/components/ActivationRequired';
import IdleWarningDialog from 'merchant/components/IdleWarningDialog';
import * as ModalActions from 'rzp/modules/modals';
import * as NotificationActions from 'rzp/modules/notifications';
import * as SessionActions from 'merchant/modules/session';
import * as ConfigActions from 'merchant/modules/config';
import { applyTheme } from 'rzp/themes';
import User, { setFeatures } from 'merchant/models/User';
import { fetchFeaturesAjax } from 'merchant/modules/config';
import AddGST from 'merchant/containers/Profile/AddGST';
import { fetchGST } from 'merchant/modules/profile';
import { fetchConfig } from 'merchant/modules/config';

@withRouter
@connect(state => ({ ...state.session, config: state.config }), {
  ...ModalActions,
  ...SessionActions,
  ...ConfigActions,
  ...NotificationActions,
  fetchGST,
})
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
      showMobileNav: false,
    };
  }

  componentWillMount() {
    let currentMode = LocalStorageService.getItem(this.modeToken);

    this.props.fetchGST();
    this.props.fetchConfig();

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
      // Fetch features before displaying other views
      fetchFeaturesAjax(response[0].current)
        .catch(_ => _)
        .then(data => {
          let user = new User(response[0]);
          user.features = setFeatures(data.success ? data.data.features : []);

          this.props.updateSession({ user, mode: currentMode });

          let $splash = document.getElementById('splash');
          if ($splash) {
            $splash.parentElement.removeChild($splash);
          }

          this.setState({ isLoading: false });
        });
    });
  }

  componentWillReceiveProps({ user, history }) {
    if (user.isAuthenticated) {
      let role = user.userRole;
      this.redirectToRoute(role);
    }
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

  toggleMobileNav = () => {
    this.setState({
      showMobileNav: !this.state.showMobileNav,
    });
  };

  showGSTModal = () => {
    this.props.openModal({
      size: 'small',
      component: <AddGST openedFromTopbar={true} />,
    });
  };

  render() {
    let { user, config, org, mode, modeFormatted } = this.props;

    if (this.state.isLoading || !user.isAuthenticated) {
      return null;
    }

    return (
      <div class={`layout ${this.orgCode}`}>
        <HeaderNav
          user={user}
          mode={mode}
          modeFormatted={modeFormatted}
          showGSTModal={this.showGSTModal}
          onSwitchMode={this.switchMode}
          onSwitchMerchant={this.switchMerchant}
          toggleMobileNav={this.toggleMobileNav}
          showMobileNav={this.state.showMobileNav}
        />
        <Sidebar
          user={user}
          logoURL={org.main_logo_url}
          config={config.config}
        />
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
