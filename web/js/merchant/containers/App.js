import { Component } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router';

import ErrorBoundary from 'common/ErrorBoundary';
import ModalDialog from 'rzp/ui/ModalDialog';
import Notifications from 'rzp/ui/Notifications';
import LocalStorageService from 'rzp/utils/localStorage';
import debounce from 'rzp/utils/debounce';
import Sidebar from 'merchant/containers/Sidebar';
import HeaderNav from 'merchant/components/HeaderNav';
import Content from 'merchant/components/Content';
import Footer from 'merchant/components/Footer';
import MerchantTour from 'merchant/containers/MerchantTour';
import ActivationRequired from 'merchant/components/ActivationRequired';
import PasswordReLogin from 'merchant_common/components/PasswordReLogin';
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
import { resizeWindow } from 'merchant/modules/app';
import { matchFullPageView } from 'merchant/routes';
import { classList } from 'common/util';
import { setTrackData } from 'rzp/utils/googleAnalytics';
import { merchantFetch } from 'merchant/utils/ajax';

import initChat from 'merchant/chat';
import track from 'react-tracking';

@withRouter
@connect(
  state => ({
    ...state.session,
    config: state.config,
    windowWidth: state.app.windowWidth,
    merchant_gst: state.profile.merchant_gst,
  }),
  {
    ...ModalActions,
    ...SessionActions,
    ...ConfigActions,
    ...NotificationActions,
    fetchGST,
    resizeWindow,
  }
)
@track(
  ({ user, mode }) => {
    const u = { email: user.user.email, id: user.user.id, mid: user.current };
    let utm = null;
    if (undefined !== analytics) {
      utm = analytics.utils.getLandingParams();
    }
    return window.rzpQ.component('Home', {
      user: u,
      utm_params: utm,
      mode: mode,
    });
  },
  {
    dispatch: data => {
      window.rzpQ.push(data);
    },
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
          `Dashboard - ${errorCode} Error`,
          e.detail.url,
          e.detail.response
        );
    });

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
          initChat(user);
        });
        return data;
      }),
      this.fetchOrg().then(({ data }) => {
        let orgCode = (this.orgCode = data.custom_code);
        if (orgCode && orgCode !== 'rzp') {
          applyTheme(orgCode);
        }
      }),
      this.fetchSupportedCurrencies().then(({ data }) => {
        window.currencyList = data;
      }),
    ])
      .then(response => {
        if (response[0].showInstantActivation) {
          setTrackData({
            eventCategory: 'Dashboard - Instant Activations',
            eventAction: 'Show - Instant Activations Flow',
          })();

          if (typeof window.hj === 'function') {
            window.hj('trigger', 'instant_activation');
            window.hj('tagRecording', ['instant_activation']);
          }
        }

        // Fetch features before displaying other views
        fetchFeaturesAjax(response[0].current)
          .catch(_ => _)
          .then(data => {
            let user = new User(response[0]);
            user.features = setFeatures(data.success ? data.data.features : []);

            this.props.updateSession({ user, mode: currentMode });
            this.renderFPView = this.getFPView(this.props.location);

            removeSplashLoader();
            this.setState({ isLoading: false });
          });
      })
      .catch(() => {
        removeSplashLoader();
        this.setState({ isLoading: false });
      });
  }

  componentDidMount() {
    window.addEventListener('resize', this.handleResize);
  }

  componentWillReceiveProps({ user, history, location }) {
    if (user.isAuthenticated) {
      let role = user.userRole;
      this.redirectToRoute(role);

      this.renderFPView = this.getFPView(location);
    }
  }

  componentWillUnmount() {
    window.removeEventListener('resize', this.handleResize);
  }

  fetchSupportedCurrencies() {
    return merchantFetch('currency/all/proxy');
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
        case 'agent':
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

      window.trackHubs({
        name: 'update_property',
        data: {
          is_live: true,
        },
      });
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

  showGSTModal = () => {
    this.props.openModal({
      size: 'small',
      component: <AddGST />,
    });
  };

  getFPView = location => {
    const matchView = matchFullPageView(location.pathname);
    let FPView = null;

    if (matchView && matchView.match) {
      const FPComponent = matchView.component;

      FPView = (
        <ErrorBoundary resetOnProps location={location}>
          <FPComponent {...matchView.match.params} />
        </ErrorBoundary>
      );
    }

    return FPView;
  };

  render() {
    let { user, config, org, mode, modeFormatted, merchant_gst } = this.props;

    const hasGSTIN =
      this.props.merchant_gst.p_gstin || this.props.merchant_gst.gstin;

    if (this.state.isLoading || !user.isAuthenticated) {
      return null;
    }

    return (
      <div
        class={classList(
          'layout',
          this.orgCode,
          this.renderFPView && 'layout--fp'
        )}
      >
        {this.renderFPView ? (
          this.renderFPView
        ) : (
          <React.Fragment>
            <HeaderNav
              user={user}
              mode={mode}
              modeFormatted={modeFormatted}
              showGSTModal={hasGSTIN ? undefined : this.showGSTModal}
              onSwitchMode={this.switchMode}
              onSwitchMerchant={this.switchMerchant}
              showMobileNav={this.props.windowWidth < 950}
            />
            <Sidebar
              user={user}
              logoURL={org.main_logo_url}
              config={config.config}
              org_custom_code={org.custom_code}
            />
            <Content user={user} modeFormatted={modeFormatted} />
            <Footer showMobileNav={this.props.windowWidth < 950} user={user} />
          </React.Fragment>
        )}

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

function removeSplashLoader() {
  let $splash = document.getElementById('splash');
  if ($splash) {
    $splash.parentElement.removeChild($splash);
  }
}
