import { Component } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router';

import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import ModalDialog from 'common/ui/ModalDialog';
import Notifications from 'common/ui/Notifications';
import LocalStorageService from 'common/utils/localStorage';
import debounce from 'common/utils/debounce';
import Sidebar from 'merchant/containers/Sidebar';
import HeaderNav from 'merchant/components/HeaderNav';
import Content from 'merchant/components/Content';
import Footer from 'merchant/components/Footer';
import MerchantTour from 'merchant/containers/MerchantTour';
import ActivationRequired from 'merchant/components/ActivationRequired';
import PasswordReLogin from 'merchant_common/components/PasswordReLogin';
import * as ModalActions from 'merchant_common/reducers/modals';
import * as NotificationActions from 'merchant_common/reducers/notifications';
import * as SessionActions from 'merchant/reducers/session';
import * as ConfigActions from 'merchant/reducers/config';
import { applyTheme } from 'merchant_common/helpers/themes';
import User, { setFeatures } from 'merchant/models/User';
import { fetchFeaturesAjax } from 'merchant/reducers/config';
import AddGST from 'merchant/views/Account/Profile/components/AddGST';
import { fetchGST } from 'merchant/reducers/profile';
import { fetchConfig } from 'merchant/reducers/config';
import { fireAnalyticsEvents } from 'common/utils/googleAnalytics';
import {
  resizeWindow,
  updateMerchantLiveTransactionFlag,
} from 'merchant/reducers/app';
import { matchFullPageView } from 'merchant/routes';
import { classList } from 'common/utils/rzp-utils';
import { setTrackData } from 'common/utils/googleAnalytics';
import { merchantFetch } from 'merchant/utils/ajax';
import rolesList from 'merchant/helpers/permissions/roles-list';

import initChat from 'merchant/chat';
import RTracking from 'react-tracking';

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
@RTracking(
  ({ user, mode }) => {
    const u = {
      email: user.user.email,
      id: user.user.id,
      mid: user.current,
      role: user.role,
      business_type: user.business_type,
      activated: user.activated,
    };
    let utm = null;
    let gclid = null; //Google click id, analytics will try to capture and save to cookie if present.
    if (typeof window.analytics !== 'undefined') {
      utm = analytics.utils.getLandingParams();
      gclid = analytics.utils.getCookie('gclid');
    }
    return window.rzpQ.component('Home', {
      user: u,
      utm_params: utm,
      gclid: gclid,
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
      window.rzp_user &&
        Object.keys(window.rzp_user.merchants).forEach(merchantId => {
          LocalStorageService.setItem(
            `${oldModeToken}--${merchantId}`,
            oldModeValue
          );
        });

      LocalStorageService.removeItem(oldModeToken);
    }

    this.modeToken = null;

    if (window.rzp_user) {
      this.modeToken = `${oldModeToken}--${window.rzp_user.current}`;
    }

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
  setLiveTransactionDone = ({ live_transaction_done, id }) => {
    if (live_transaction_done === undefined) return;

    switch (live_transaction_done) {
      case 1:
        updateMerchantLiveTransactionFlag(id)
          .then(resp => {
            if (resp.success) {
              setTrackData({
                eventCategory: 'Dashboard - Instant Activations Live',
                eventAction: 'Login',
                eventLabel: 'MTU-Funnel',
              })();
              fireAnalyticsEvents({
                fbData: 'live_mtu_funnel',
                liData: 1668428,
              });
            }
          })
          .catch(err => {});
        break;
      case 2:
        setTrackData({
          eventCategory: 'Dashboard - Instant Activations Live',
          eventAction: 'Login',
          eventLabel: 'MTU-Audience',
        })();
        fireAnalyticsEvents({
          fbData: 'live_mtu_audience',
          liData: 1668436,
          quoraData: 'live_mtu_audience',
          redditData: 'Purchase',
        });
        break;
    }
  };

  fetchUser() {
    let user = window.rzp_user ? new User(window.rzp_user) : null;

    if (user) {
      this.props.updateSession({ user });
      this.setLiveTransactionDone(user);

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
        case [rolesList.SELLERAPP]:
        case [rolesList.AGENT]:
          let url = '/paymentlinks';
          return this.props.history.replace(url);

        case [rolesList.SUPPORT]:
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
