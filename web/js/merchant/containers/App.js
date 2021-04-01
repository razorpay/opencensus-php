import { Component, Suspense } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router';
import moment from 'moment';
import { makePopup } from '@typeform/embed';

import Loader from 'common/ui/Loader';

import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import ModalDialog from 'common/ui/ModalDialog';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import Notifications from 'common/ui/Notifications';
import LocalStorageService from 'common/utils/localStorage';
import debounce from 'common/utils/debounce';
import Sidebar from 'merchant/components/Sidebar';
import HeaderNav from 'merchant/components/HeaderNav';
import Content from 'merchant/routes/Content';
import Footer from 'merchant/components/Footer';
import ActivationRequiredModal from 'merchant/components/ActivationRequiredModal';
import PasswordReLogin from 'merchant_common/components/PasswordReLogin';
import * as ModalActions from 'merchant_common/reducers/modals';
import * as NotificationActions from 'merchant_common/reducers/notifications';
import { updateTwoFactorVerified } from 'merchant_common/reducers/twoFactor';
import * as SessionActions from 'merchant/reducers/session';
import * as ConfigActions from 'merchant/reducers/config';
import { applyTheme } from 'merchant_common/helpers/themes';
import TwoFactorVerificationProvider from 'common/ui/TwoFactorVerification/TwoFactorVerificationProvider';
import User, { setFeatures } from 'merchant/models/User';
import { fetchFeaturesAjax } from 'merchant/reducers/config';
import AddGST from 'merchant/views/Account/Profile/components/AddGST';
import { fetchGST } from 'merchant/reducers/profile';
import { fetchConfig, fetchRefundPricing } from 'merchant/reducers/config';
import { fireAnalyticsEvents, setTrackData } from 'common/utils/googleAnalytics';
import { resizeWindow, updateMerchantLiveTransactionFlag } from 'merchant/reducers/app';
import { matchFullPageView } from 'merchant/routes';
import { classList, isPresent } from 'common/utils/rzp-utils';
import { isMobileDevice } from 'merchant/components/Home/data';

import ajax, { merchantFetch } from 'merchant/utils/ajax';
import rolesList from 'merchant/helpers/permissions/roles-list';

import initChat from 'merchant/components/Support/chat';
import RTracking from 'react-tracking';
import qs from 'query-string';
import Wrapper from 'v2/components/Bootstrap/Wrapper';
import { fetchActiveTickets } from 'merchant/reducers/config.js';

@withRouter
@connect(
  (state) => ({
    ...state.session,
    baseLocation: state.app.baseLocation,
    config: state.config,
    windowWidth: state.app.windowWidth,
    merchant_gst: state.profile.merchant_gst,
  }),
  {
    ...ModalActions,
    ...SessionActions,
    ...ConfigActions,
    ...NotificationActions,
    updateTwoFactorVerified,
    fetchGST,
    fetchActiveTickets: fetchActiveTickets,
    resizeWindow,
  },
)
@RTracking(
  ({ user, mode }) => {
    let utm = null;
    let gclid = null; //Google click id, analytics will try to capture and save to cookie if present.
    let browser_details = {};
    const query = qs.parse(window.location.search);
    let source = 'pg';
    let u = {};
    if (user && user.user) {
      u = {
        email_id: user.user.email,
        user_id: user.user.id,
        mid: user.current,
        user_role: user.role,
        business_type: user.business_type,
        activation_status: user.activated,
        is_reg_auto_kyc_enabled: user.isRegAutoKYCEnabled,
        is_esign_aadhar_enabled: user.isEsignAadharEnabled,
      };
    }
    if (query.merchant) {
      source = query.merchant;
    }
    if (typeof window.razorpayAnalytics !== 'undefined') {
      utm = razorpayAnalytics.utils.getLandingParams();
      gclid = razorpayAnalytics.utils.getCookie('gclid');
      if (typeof razorpayAnalytics.utils.getBrowserDetails !== 'undefined') {
        browser_details = razorpayAnalytics.utils.getBrowserDetails();
      }
    }
    return window.rzpQ.component('Home', {
      ...u,
      utm_params: utm,
      gclid,
      mode: 'live',
      rzp_mode: mode,
      source,
      reffering_url: document.referrer,
      url: document.location.href,
      ...browser_details,
    });
  },
  {
    dispatch: (data) => {
      window.rzpQ.push(data);
    },
  },
)
export default class App extends Component {
  pendingRequests = [];

  constructor(props) {
    super(props);
    const { user } = props;

    const oldModeToken = 'rzp_mode';
    const oldModeValue = LocalStorageService.getItem(oldModeToken);

    // localizing mode for each merchant so that different modes can be maintained
    // across logins/merchants
    if (oldModeValue) {
      window.rzp_user &&
        Object.keys(window.rzp_user.merchants).forEach((merchantId) => {
          LocalStorageService.setItem(`${oldModeToken}--${merchantId}`, oldModeValue);
        });

      LocalStorageService.removeItem(oldModeToken);
    }

    this.modeToken = null;

    if (window.rzp_user) {
      this.modeToken = `${oldModeToken}--${window.rzp_user.current}`;
    }

    this.state = {
      isLoading: true,
      goLiveNPSSurveyPopup: false,
      nonGoLiveNPSSurveyPopup: false,
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
    const user = window.rzp_user;
    if (user && window.analytics) {
      const mode = localStorage.getItem(`rzp_mode--${user.id}`);
      const kycStatus = user.activated ? 'activated' : 'not activated';
      const activatedAt = user.activated_at;

      const segmentIdentiyCall = (dataFromAPI) =>
        analytics.identify({
          id: user.user.id,
          userId: user.user.id,
          emailId: user.email,
          activatedAt,
          mode,
          userRole: user.role,
          kycStatus,
          merchantId: user.current,
          businessCategory: user.businessCategory,
          ...dataFromAPI,
        });

      let dataFromAPI = {};
      merchantFetch('merchant/data_for_segment')
        .then((res) => {
          if (res.data) {
            dataFromAPI = res.data;
          }
          segmentIdentiyCall(dataFromAPI);
        })
        .catch(() => segmentIdentiyCall(dataFromAPI));
    }
    const self = this;
    window.addEventListener('NOT_AUTHENTICATED', function (e) {
      self.registerPendingRequests(e.detail.continueAjax);

      if (this.isDashboardLocked) {
        return;
      }

      self.lockDashboard(self.resumePendingRequests);
    });

    window.addEventListener('REQUEST_ERROR', function (e) {
      const errorCode = e.detail.response ? e.detail.response.status : 'UNKNOWN STATUS';

      window.ga &&
        window.ga(
          'send',
          'event',
          `Dashboard - ${errorCode} Error`,
          e.detail.url,
          e.detail.response,
        );
    });

    let currentMode = LocalStorageService.getItem(this.modeToken);
    this.props.fetchGST();
    this.props.fetchConfig();
    this.props.fetchRefundPricing();

    Promise.all([
      this.fetchUser().then(({ data }) => {
        const user = data;
        const role = user.userRole;
        if (user.isFdTicketsEnabled && (role === 'owner' || role === 'admin')) {
          this.props.fetchActiveTickets().then(() => {
            window.rzpAnalytics({
              eventCategory: 'Ticket Dashboard',
              eventAction: 'support form tickets fetched',
              eventLabel: `Tickets | Status: Success`,
            });
          });
        }
        if (!currentMode) {
          currentMode = user.isActivated ? 'live' : 'test';
        } else if (!user.isActivated) {
          currentMode = 'test';
        }

        this.props.updateSession({ mode: currentMode });
        this.props.updateTwoFactorVerified({
          twoFactorVerified: user.isTwoFactorVerified,
        });
        this.redirectToRoute(role);
        this.setLiveTransactionDone(user);

        setTimeout(() => {
          initChat(user);
        });
        return data;
      }),
      this.fetchOrg().then(({ data }) => {
        const orgCode = (this.orgCode = data.custom_code);
        if (orgCode && orgCode !== 'rzp') {
          applyTheme(data);
        }
      }),
      this.fetchSupportedCurrencies().then(({ data }) => {
        window.currencyList = data;
      }),
    ])
      .then((response) => {
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
          .catch((_) => _)
          .then((data) => {
            const user = new User(response[0]);
            user.features = setFeatures(data.success ? data.data.features : []);

            this.props.updateSession({ user, mode: currentMode });
            this.renderFullPageView = this.getFPView(this.props.location);

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
    const user = window.rzp_user;
    if (user) {
      const GoLiveNPSEnableTypeForm = makePopup(
        `https://razorpay.typeform.com/to/eJ8e5AQb?mid=${user.current}&source=dashboard&email=${user.email}`, // go live survey
        {
          mode: 'popup',
          hideHeaders: true,
          hideFooters: true,
          onSubmit: this.closeGoLiveSurvey,
        },
      );
      this.state.GoLiveNPSEnableTypeForm = GoLiveNPSEnableTypeForm; // saving reference typeform

      const NonGoLiveNPSEnableTypeForm = makePopup(
        `https://razorpay.typeform.com/to/cgDsNYSY?mid=${user.current}&source=dashboard&email=${user.email}`, // non go live survey
        {
          mode: 'popup',
          hideHeaders: true,
          hideFooters: true,
          onSubmit: this.closeNonGoLiveSurvey,
        },
      );
      this.state.NonGoLiveNPSEnableTypeForm = NonGoLiveNPSEnableTypeForm; // saving reference typeform
    }
  }

  componentWillReceiveProps({ user, history, location, baseLocation, org }) {
    if (user.isAuthenticated) {
      const role = user.userRole;
      this.redirectToRoute(role);

      this.renderFullPageView = this.getFPView(baseLocation || location);
    }
    if (org && user) {
      const goLiveSurveyShowed = !!LocalStorageService.getItem(
        'razorpay_go_live_nps_survey_showed',
      );
      if (
        org &&
        org.custom_code &&
        org.custom_code.toLowerCase() === 'rzp' && // only for razorpay org
        user.showNPSSurvey() && // experiment check
        !goLiveSurveyShowed &&
        !isMobileDevice() &&
        this.state.GoLiveNPSEnableTypeForm
      ) {
        const takeGoLiveNPSSurvey = this.dateIsInRange(user.created_at, [
          ['2021-02-28', '2021-04-01'],
        ]);
        this.setState({ goLiveNPSSurveyPopup: takeGoLiveNPSSurvey });
      }

      const nonGoLiveSurveyShowed = !!LocalStorageService.getItem(
        'razorpay_non_go_live_nps_survey_showed',
      );
      if (
        org &&
        org.custom_code &&
        org.custom_code.toLowerCase() === 'rzp' && // only for razorpay org
        user.showNPSSurvey() && // experiment check
        !nonGoLiveSurveyShowed &&
        !isMobileDevice() &&
        this.state.NonGoLiveNPSEnableTypeForm
      ) {
        const takeNonGoLiveNPSSurvey = this.dateIsInRange(user.created_at, [
          ['2020-02-29', '2020-04-01'],
          ['2020-08-31', '2020-10-01'],
          ['2020-11-30', '2021-01-01'],
        ]);
        this.setState({ nonGoLiveNPSSurveyPopup: takeNonGoLiveNPSSurvey });
      }
    }
  }

  componentWillUnmount() {
    window.removeEventListener('resize', this.handleResize);
  }

  /**
   * this function check wether the date is inside any of the date ranges or not
   * @param {String} inputDate - this date should be in unix timestamp format
   * @param {Object} dateRanges - this array contain arrays of date ranges date format should be YYYY-MM-DD
   * @returns {boolean}
   */
  dateIsInRange = (input, ranges) => {
    if (ranges) {
      const result = ranges.reduce((res, range) => {
        return res || moment(moment(input, 'X').format('YYYY-MM-DD')).isBetween(range[0], range[1]);
      }, false);
      return result;
    }
    return false;
  };

  closeGoLiveSurvey = () => {
    this.setState({ goLiveNPSSurveyPopup: false });
    this.state.GoLiveNPSEnableTypeForm.close();
  };

  closeNonGoLiveSurvey = () => {
    this.setState({ nonGoLiveNPSSurveyPopup: false });
    this.state.NonGoLiveNPSEnableTypeForm.close();
  };

  fetchSupportedCurrencies() {
    return merchantFetch('currency/all/proxy');
  }

  fireMTUFunnelEvents = (user) => {
    const isUnregisteredBusiness = user.isUnregisteredBusiness;
    const eventLabel = `MTU-Funnel${user.isUnregisteredBusiness ? '-Unreg' : ''}`;

    setTrackData({
      eventCategory: 'Dashboard - Instant Activations Live',
      eventAction: 'Login',
      eventLabel,
    })();

    let fbEvents = ['live_mtu_funnel', 'live_mtu_audience'];
    const bizTypeTerm = isUnregisteredBusiness ? 'unreg' : 'reg';
    fbEvents = [...fbEvents, `live_mtu_funnel_${bizTypeTerm}`, `live_mtu_audience_${bizTypeTerm}`];

    if (isUnregisteredBusiness) {
      fbEvents = [...fbEvents, 'combo1', 'combo2', 'combo4', 'combo5', 'combo7'];
    } else {
      fbEvents = [...fbEvents, 'combo1', 'combo2', 'combo3', 'combo4', 'combo6'];
    }

    fbEvents.forEach((evt) => {
      fireAnalyticsEvents({
        fbData: evt,
      });
    });
    fireAnalyticsEvents({
      liData: 1668428,
    });
  };

  fireMTUAudienceEvents = (user) => {
    const isUnregisteredBusiness = user.isUnregisteredBusiness;
    const eventLabel = `MTU-Audience${user.isUnregisteredBusiness ? '-Unreg' : ''}`;

    setTrackData({
      eventCategory: 'Dashboard - Instant Activations Live',
      eventAction: 'Login',
      eventLabel,
    })();

    let fbEvents = ['live_mtu_audience'];
    const bizTypeTerm = isUnregisteredBusiness ? 'unreg' : 'reg';
    fbEvents = [...fbEvents, `live_mtu_audience`, `live_mtu_audience_${bizTypeTerm}`];

    fbEvents.forEach((evt) => {
      fireAnalyticsEvents({
        fbData: evt,
      });
    });
    fireAnalyticsEvents({
      liData: 1668436,
      quoraData: 'Purchase',
      redditData: 'Purchase',
    });
  };

  setLiveTransactionDone = (user) => {
    let { live_transaction_done, id } = user;

    if (!isPresent(live_transaction_done)) {
      return;
    }

    live_transaction_done = parseInt(live_transaction_done);
    switch (live_transaction_done) {
      case 1:
        updateMerchantLiveTransactionFlag(id)
          .then((resp) => {
            if (resp.success) {
              this.fireMTUFunnelEvents(user);
            }
          })
          .catch((err) => {});
        break;
      case 2:
        this.fireMTUAudienceEvents(user);
        break;
    }
  };

  fetchUser() {
    const user = window.rzp_user ? new User(window.rzp_user) : null;

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
    const pathname = this.props.history.location.pathname;

    if (pathname === '/' || pathname === '/dashboard' || pathname === '/dashboard_v2') {
      switch (role) {
        case [rolesList.SELLERAPP]:
        case [rolesList.AGENT]:
          const url = '/paymentlinks';
          return this.props.history.replace(url);

        case [rolesList.SUPPORT]:
          return this.props.history.replace('/payments');

        case null:
          return this.props.history.replace('/profile');
      }
    }
  }

  switchMode = (mode) => {
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Header',
      eventAction: 'Switch - Mode',
      eventLabel: mode,
    });
    analyticsTrack({
      objectName: 'mode',
      actionName: 'selected',
      screen: 'home page',
      properties: {
        current: mode,
        new: 'test',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    const user = this.props.user;
    if (mode === 'live' && !user.isActivated) {
      this.props.openModal({
        size: 'small',
        component: (
          <ActivationRequiredModal user={this.props.user} onCloseClick={this.props.closeModal} />
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

  switchMerchant = (merchant) => {
    analyticsTrack({
      objectName: 'switch merchant',
      actionName: 'selected',
      screen: 'home page',
      properties: {
        new_mid: merchant.id,
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    this.props
      .switchMerchant(merchant.id)
      .then(() => {
        analyticsTrack({
          objectName: 'switch merchant',
          actionName: 'result',
          screen: 'home page',
          properties: {
            new_mid: merchant.id,
            ...getCommonAnalyticsProperties(window.rzp_user),
          },
        });
        location.reload();
      })
      .catch(({ errors }) => {
        this.props.showNotification({
          type: 'error',
          message: errors,
        });
      });
  };

  lockDashboard = (cb) => {
    const email = this.props.user.user.email;

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

  getFPView = (location) => {
    const matchView = matchFullPageView(location.pathname);
    let FPView = null;

    if (matchView && matchView.match) {
      const FPComponent = matchView.component;

      FPView = (
        <ErrorBoundary resetOnProps location={location}>
          <Suspense fallback={<Loader />}>
            <FPComponent {...matchView.match.params} />
          </Suspense>
        </ErrorBoundary>
      );
    }

    return FPView;
  };

  getSurveyForm = () => {
    const { goLiveNPSSurveyPopup, nonGoLiveNPSSurveyPopup } = this.state;
    return (
      <>
        {goLiveNPSSurveyPopup &&
          !LocalStorageService.getItem('razorpay_go_live_nps_survey_showed') && (
            <>
              {LocalStorageService.setItem('razorpay_go_live_nps_survey_showed', 1)}
              {this.state.GoLiveNPSEnableTypeForm.open()}
            </>
          )}
        {nonGoLiveNPSSurveyPopup &&
          !LocalStorageService.getItem('razorpay_non_go_live_nps_survey_showed') && (
            <>
              {LocalStorageService.setItem('razorpay_non_go_live_nps_survey_showed', 1)}
              {this.state.NonGoLiveNPSEnableTypeForm.open()}
            </>
          )}
      </>
    );
  };

  render() {
    const { user, config, org, mode, modeFormatted, merchant_gst } = this.props;

    const hasGSTIN = this.props.merchant_gst.p_gstin || this.props.merchant_gst.gstin;

    if (this.state.isLoading || !user.isAuthenticated) {
      return null;
    }

    return (
      <Wrapper
        context={{
          user,
          org,
          mode,
        }}
      >
        <div className={classList('layout', this.orgCode, this.renderFullPageView && 'layout--fp')}>
          <TwoFactorVerificationProvider merchantFetch={merchantFetch} ajax={ajax}>
            {!this.renderFullPageView && (
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
              </React.Fragment>
            )}

            {this.getSurveyForm()}

            <Content
              user={user}
              modeFormatted={modeFormatted}
              fullPageView={this.renderFullPageView}
            />

            {!this.renderFullPageView && (
              <Footer showMobileNav={this.props.windowWidth < 950} user={user} />
            )}

            {/* Creates Portal for the comp */}
            <ModalDialog />
            <Notifications />
          </TwoFactorVerificationProvider>

          {this.state.isDashboardLocked && (
            <PasswordReLogin
              merchantId={user.current}
              userEmail={user.user.email}
              removeLockScreen={this.removeLockScreen}
              showNotification={this.props.showNotification}
              resumeLockActionCB={this.resumeLockActionCB}
              isGoogleLogin={user.isGoogleLogin()}
              isPartner={user.isPartner()}
            />
          )}
        </div>
      </Wrapper>
    );
  }
}

function removeSplashLoader() {
  const $splash = document.getElementById('splash');
  if ($splash) {
    $splash.parentElement.removeChild($splash);
  }
}
