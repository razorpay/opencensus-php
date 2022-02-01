import { Component, Suspense } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router';
import moment from 'moment';
import { createSidetab, createPopup } from '@typeform/embed';
import Loader from 'common/ui/Loader';
import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import ModalDialog from 'common/ui/ModalDialog';
import { removeItem } from 'common/utils/localStorage';
import { analyticsTrack, initAnalytics } from 'common/utils/analytics';
import { initLumberjack, initRefiner, initSegment } from 'common/utils/trackers';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { initSentry } from 'common/utils/observability';
import Notifications from 'common/ui/Notifications';
import LocalStorageService from 'common/utils/localStorage';
import debounce from 'common/utils/debounce';
import Sidebar from 'merchant/components/Sidebar';
import HeaderNav from 'merchant/components/HeaderNav';
import HighlightTestMode from 'merchant/components/HighlightTestMode';
import Content from 'merchant/routes/Content';
import Footer from 'merchant/components/Footer';
import ActivationRequiredModal from 'merchant/components/ActivationRequiredModal';
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
import Wrapper from 'common/components/Bootstrap/Wrapper';
import { fetchActiveTickets, fetchTicketsRaisedByAgents } from 'merchant/reducers/config.js';
import { fetchTrustedBadgeStatus } from 'merchant/reducers/trustedBadge.js';
import LogoutDialog from 'merchant/components/LogoutDialog';
import { closeModal, openModal } from 'merchant_common/reducers/modals';
import { fetchMerchantReferralDetail } from 'merchant/reducers/merchantReferral';
import { fetchInstantSettlements, fetchPayments } from 'merchant/reducers/collection';
import { bindActionCreators, compose } from 'redux';
import { initChatbot } from '../chatbot-init';
import PartnerActivationRequiredModal from 'merchant/views/PartnerDashboard/Activation/Components/ActivationRequiredModal';
import _refiner from 'refiner-js';
import { getCookie, setCookie } from 'common/utils/cookies';
import RequestEmailModal from 'merchant_common/containers/ReportsAsync/GenerateReportPanel/AddEmail/RequestEmailModal';

initSentry('Merchant');

@RTracking()
class App extends Component {
  pendingRequests = [];

  constructor(props) {
    super(props);

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
      this.partnerModeToken = `rzp_partner_mode`;
    }
    this.logoutPopupShown = false;
    this.state = {
      isLoading: true,
      goLiveNPSSurveyPopup: false,
      nonGoLiveNPSSurveyPopup: false,
      isPartnerModeEnabled:
        this.props.location.pathname.startsWith('/partners/') &&
        this.props?.user?.isIndependentPartnerKYCEnabled,
      isPartnerKYCActivated: false,
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

  canMerchantMoveToLiveMode = (currentMode, isActivated, user) => {
    // if user is unregisterd or activation status is activated or activated_mcc_pending in test mode.
    // on behalf of merchant, system will change to the live mode first time
    // post that user can switch b/w any mode.

    return (
      ((user.isUnregisteredBusiness &&
        user.instantActivation.isL1Submitted &&
        user.poi_verification_status === 'verified' &&
        user.activation_status === 'instantly_activated') ||
        user.activation_status === 'activated' ||
        user.activation_status === 'activated_mcc_pending') &&
      currentMode === 'test' &&
      !isActivated
    );
  };

  canPartnerMoveToLiveMode = (currentMode, isPartnerKYCActivated, isAlreadyActivated) => {
    return isPartnerKYCActivated && currentMode === 'test' && !isAlreadyActivated;
  };

  componentWillMount() {
    const user = window.rzp_user;

    // Init lumberjack
    initLumberjack();

    const self = this;
    window.addEventListener('NOT_AUTHENTICATED', function (e) {
      if (this.logoutPopupShown) {
        return;
      }
      self.props.closeModal();
      self.props.openModal({
        size: 'large',
        component: <LogoutDialog user={user} />,
      });
      this.logoutPopupShown = true;
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
    let isActivated = LocalStorageService.getItem(`is_activated--${user?.current}`);
    const isUnregBiz = ['2', '11'].indexOf(user?.business_type) !== -1;

    let currentPartnerMode = LocalStorageService.getItem(this.partnerModeToken);
    let isPartnerKYCActivated = LocalStorageService.getItem(
      `is_partner_activated--${user?.current}`,
    );

    if (
      user &&
      currentMode === 'live' &&
      !isActivated &&
      (user.activation_status === 'activated' ||
        user.activation_status === 'activated_mcc_pending' ||
        (isUnregBiz &&
          user.activation_form_milestone === 'L1' &&
          user.poi_verification_status === 'verified' &&
          user.activation_status === 'instantly_activated'))
    ) {
      LocalStorageService.setItem(`is_activated--${user.current}`, 'true');
      isActivated = 'true';
    }

    this.props.fetchGST();

    this.props.fetchConfig();
    this.props.fetchRefundPricing();
    this.props.fetchTrustedBadgeStatus();
    this.props.fetchMerchantReferralDetail();

    Promise.all([
      this.fetchUser().then(({ data }) => {
        const user = data;
        const role = user.userRole;
        if (!currentMode) {
          currentMode = user.isActivated ? 'live' : 'test';
        } else if (this.canMerchantMoveToLiveMode(currentMode, isActivated, user)) {
          currentMode = 'live';
        } else if (!user.isActivated) {
          currentMode = 'test';
        }

        this.props.updateSession({ mode: currentMode });
        this.props.updateTwoFactorVerified({
          twoFactorVerified: user.isTwoFactorVerified,
        });
        this.redirectToRoute(role);
        this.setLiveTransactionDone(user);
        if (user.isChatbotLive) {
          initChatbot(user);
        } else {
          setTimeout(() => {
            initChat(user);
          });
        }

        if (user?.user) {
          // Initialize segment
          initSegment('Merchant', user, this.props.updateUserSegmentData);

          // Initialize refiner
          initRefiner(user);
        }

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

        // use partner mode if merchant kyc is not activated and it's enabled
        if (!user.isActivated && this.state.isPartnerModeEnabled) {
          this.fetchPartnerActivationStatus().then(({ data }) => {
            const isCurrentPartnerKYCActivated =
              data?.partner_activation?.activation_status === 'activated';
            if (user && isCurrentPartnerKYCActivated) {
              LocalStorageService.setItem(`is_partner_activated--${user.current}`, 'true');
              isPartnerKYCActivated = 'true';
            }
            this.setState({
              isPartnerKYCActivated: isCurrentPartnerKYCActivated,
              partnerActivationStatus: data?.partner_activation?.activation_status,
            });
            if (!currentPartnerMode) {
              currentPartnerMode = isPartnerKYCActivated ? 'live' : 'test';
            } else if (
              this.canPartnerMoveToLiveMode(
                currentMode,
                isCurrentPartnerKYCActivated,
                isPartnerKYCActivated,
              )
            ) {
              currentPartnerMode = 'live';
            } else if (!isPartnerKYCActivated) {
              currentPartnerMode = 'test';
            }

            this.props.updateSession({
              partnerMode: currentPartnerMode,
              isUsingPartnerMode: this.state.isPartnerModeEnabled,
            });
          });
        }
      })
      .catch(() => {
        removeSplashLoader();
        this.setState({ isLoading: false });
      });

    if (
      this.props.user?.isActivationFormFullView &&
      user?.merchants &&
      Object.keys(user.merchants).length === 1
    ) {
      if (!user?.activation_form_milestone && !user?.activated) {
        if (isMobileDevice()) {
          this.props.history.push('/onboarding/steps');
        } else {
          const firstStepToken = 'onboarding_first_step';
          removeItem(`${firstStepToken}--${user?.current}`);
          this.props.history.push('/kyc');
        }
      } else if (this.props.location.pathname === '/activation' && !isMobileDevice()) {
        this.props.history.push('/kyc');
      }
    }
  }

  openRequestEmailPopup() {
    const user = window.rzp_user;
    const { fetchPayments, openModal } = this.props;
    const EMAIL_REQUESTED = `email_requested_${user.user?.id}`;
    if (
      user.user &&
      user.role == rolesList.OWNER &&
      !user.user?.signup_via_email &&
      !user.user?.email &&
      !getCookie(EMAIL_REQUESTED)
    ) {
      fetchPayments({ count: 1, mode: 'live' }).then((res) => {
        if (res && res.success && res.data?.count > 0) {
          setCookie(EMAIL_REQUESTED, true, Infinity);
          openModal({
            size: 'medium',
            component: <RequestEmailModal />,
          });
        }
      });
    }
  }

  componentDidMount() {
    window.addEventListener('resize', this.handleResize);
    this.fetchUser().then(({ data }) => {
      const user = data;
      const hidden = {
        mid: `${user.id}`,
        source: 'dashboard',
        email: `${user.email}`,
      };
      if (user.showNPSSurvey() === true) {
        const GoLiveNPSEnableTypeForm = createSidetab(
          'QmdrkCr1', // go live survey
          {
            width: 500,
            buttonText: 'Feedback',
            hideHeaders: true,
            hideFooters: true,
            hidden,
            onSubmit: this.closeGoLiveSurvey,
          },
        );
        this.state.GoLiveNPSEnableTypeForm = GoLiveNPSEnableTypeForm; // saving reference typeform

        const NonGoLiveNPSEnableTypeForm = createSidetab(
          'GFoqmDtL', // non go live survey
          {
            width: 500,
            buttonText: 'Feedback',
            hideHeaders: true,
            hideFooters: true,
            hidden,
            onSubmit: this.closeNonGoLiveSurvey,
          },
        );
        this.state.NonGoLiveNPSEnableTypeForm = NonGoLiveNPSEnableTypeForm; // saving reference typeform
      }
    });
    const user = window.rzp_user;
    if (user) {
      this.openRequestEmailPopup();
      if (
        ((user.experiments || {})['csm_experince_survey'] || {}).result === 'on' &&
        !LocalStorageService.getItem('csm_experience_survey_showed')
      ) {
        LocalStorageService.setItem('csm_experience_survey_showed');
        createPopup('Sl6YqLtE', {
          hideHeaders: true,
          hideFooters: true,
          hidden,
        }).open();
      }

      const merchantsSettlementStatus = JSON.parse(
        LocalStorageService.getItem('merchantsSettlementStatus'),
      );

      const esOndemandSettlementDisabled = (user.features || []).indexOf('es_on_demand') === -1;
      if (esOndemandSettlementDisabled) return;

      if (!merchantsSettlementStatus) this.getSettlementDetails(user.current);
      else {
        const settlementStatus = merchantsSettlementStatus[user.current];
        const isMerchantPresent = user.current in merchantsSettlementStatus;
        const isAnimationDisabled =
          isMerchantPresent && !settlementStatus && settlementStatus !== 'disableAnimation';

        if (!isMerchantPresent) this.getSettlementDetails(user.current);
        else if (isAnimationDisabled) {
          const updatedMerchantSettlementStatus = {
            ...merchantsSettlementStatus,
            [user.current]: 'disableAnimationOnReload',
          };
          LocalStorageService.setItem(
            'merchantsSettlementStatus',
            JSON.stringify(updatedMerchantSettlementStatus),
          );
        }
      }
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
          ['2021-12-01', '2021-12-31'],
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
          ['2021-09-01', '2021-09-30'],
          ['2021-06-01', '2021-06-30'],
          ['2020-12-01', '2020-12-31'],
        ]);
        this.setState({ nonGoLiveNPSSurveyPopup: takeNonGoLiveNPSSurvey });
      }

      const newIsPartnerModeEnabled =
        location.pathname.startsWith('/partners/') && user.isIndependentPartnerKYCEnabled;
      if (this.state.isPartnerModeEnabled !== newIsPartnerModeEnabled) {
        this.setState({
          isPartnerModeEnabled: newIsPartnerModeEnabled,
        });
      }
    }
  }

  componentWillUnmount() {
    window.removeEventListener('resize', this.handleResize);
  }

  getSettlementDetails = (merchantId) => {
    const { fetchInstantSettlements } = this.props;

    fetchInstantSettlements({ count: 1 }).then(({ data: { items = [] } = {} } = {}) => {
      const merchantsSettlementStatus = JSON.parse(
        LocalStorageService.getItem('merchantsSettlementStatus'),
      );
      const updatedMerchantSettlementStatus = {
        ...merchantsSettlementStatus,
        [merchantId]: items.length > 0,
      };
      LocalStorageService.setItem(
        'merchantsSettlementStatus',
        JSON.stringify(updatedMerchantSettlementStatus),
      );
    });
  };

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
    this.state.GoLiveNPSEnableTypeForm.unmount();
  };

  closeNonGoLiveSurvey = () => {
    this.setState({ nonGoLiveNPSSurveyPopup: false });
    this.state.NonGoLiveNPSEnableTypeForm.unmount();
  };

  fetchSupportedCurrencies() {
    if (!window.rzp_user) {
      return Promise.resolve();
    }
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
      const isActivated = LocalStorageService.getItem(`is_activated--${user.current}`);

      if (!currentMode) {
        currentMode = user.isActivated ? 'live' : 'test';
      } else if (this.canMerchantMoveToLiveMode(currentMode, isActivated, user)) {
        currentMode = 'live';
        LocalStorageService.setItem(`is_activated--${user.current}`, 'true');
      } else if (!user.isActivated) {
        currentMode = 'test';
      }

      // making sure his current mode is remembered so that when he gets
      // activated, he wont be switched to live mode automatically
      // which may lead to mass confusion for merchants
      LocalStorageService.setItem(this.modeToken, currentMode);

      if (user && user.user) {
        if (window.rzpAnalytics) {
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

        if (window.trackHubs) {
          window.trackHubs({
            name: 'identify',
            id: user.id,
            email: user.user.email,
          });
        }
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

  fetchPartnerActivationStatus = () => {
    return merchantFetch({ url: 'partner/activation', mode: 'live' });
  };

  switchMode = (mode, callback = () => {}) => {
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
    if (this.state.isPartnerModeEnabled) {
      return this.handlePartnerModeSwitch(mode);
    }
    const user = this.props.user;
    if (mode === 'live' && !user.isActivated) {
      this.props.openModal({
        size: 'small',
        component: (
          <ActivationRequiredModal user={this.props.user} onCloseClick={this.props.closeModal} />
        ),
      });
    } else {
      callback();
      LocalStorageService.setItem(this.modeToken, mode);
      window.trackHubs({
        name: 'update_property',
        data: {
          is_live: true,
        },
      });
      location.reload();
    }
  };

  handlePartnerModeSwitch = (mode) => {
    if (mode === 'live' && this.state.isPartnerModeEnabled) {
      this.props.tracking.trackEvent(
        window.rzpQ.onbr().interaction('partnerships.partner_KYC.open', {
          partnerID: this.props.user?.merchant.id,
          source: 'Live mode',
        }),
      );
    }
    const user = this.props.user;
    if (mode === 'live' && !user.isActivated) {
      this.props.tracking.trackEvent(
        window.rzpQ.onbr().interaction('partnerships.partner_KYC.pop_up', {
          partnerID: this.props.user?.merchant.id,
        }),
      );
      if (this.state.isPartnerKYCActivated) {
        LocalStorageService.setItem(this.partnerModeToken, mode);
        location.reload();
      } else {
        this.props.openModal({
          size: 'small',
          component: (
            <PartnerActivationRequiredModal
              partnerActivationStatus={this.state.partnerActivationStatus}
              onCloseClick={this.props.closeModal}
            />
          ),
        });
      }
    } else {
      LocalStorageService.setItem(this.partnerModeToken, mode);
      location.reload();
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
    if (!goLiveNPSSurveyPopup && this.state.GoLiveNPSEnableTypeForm)
      this.state.GoLiveNPSEnableTypeForm.unmount();
    if (!nonGoLiveNPSSurveyPopup && this.state.NonGoLiveNPSEnableTypeForm)
      this.state.NonGoLiveNPSEnableTypeForm.unmount();
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

  getOnboardingExperiment = () => {
    const { user } = this.props;
    return {
      canGenerateTnCPage: user.canGenerateTnCPage,
      isBDAndAovEnabled: user.isBDAndAovEnabled,
      canSkipPoiValidation: user.canSkipPoiValidation,
      isInstantActivationEnabled: user.isInstantActivationEnabled,
      isAadharEkycMandatory: user.isAadharEkycMandatory,
      isGstinMandatory: user.isGstinMandatory,
      isSyncExperimentEnabled: user.isSyncExperimentEnabled,
      isGstinAutoPopulate: user.isGstinAutoPopulate,
      isLiteOnboarding: user.isLiteOnboarding,
      isL2AllowedForPoiInitiated: user.isL2AllowedForPoiInitiated,
      isUpdatedLiteOnboarding: user.isUpdatedLiteOnboarding,
      isSyncBankVerificationEnabled: user.isSyncBankVerificationEnabled,
      isEmailMandatoryOnL1: user.isEmailMandatoryOnL1,
      isEmailNonMandatoryOnL1: user.isEmailNonMandatoryOnL1,
      isEmailNonMandatoryOnL2Form: user.isEmailNonMandatoryOnL2Form,
      isActivationFormFullView: user.isActivationFormFullView,
      isGstinSyncFlowEnabled: user.isGstinSyncFlowEnabled,
      isLlpinSyncFlowEnabled: user.isLlpinSyncFlowEnabled,
      isCinSyncFlowEnabled: user.isCinSyncFlowEnabled,
      isGstinLLpinCinSyncFlowEnabled: user.isGstinLLpinCinSyncFlowEnabled,
      isActivationMccPendingProgressbarDisabled: user.isActivationMccPendingProgressbarDisabled,
    };
  };

  render() {
    const {
      user,
      config,
      org,
      mode,
      modeFormatted,
      partnerModeFormatted,
      merchant_gst,
      partnerMode,
    } = this.props;

    const hasGSTIN = this.props.merchant_gst.p_gstin || this.props.merchant_gst.gstin;
    const isPartnerModeEnabled = this.state.isPartnerModeEnabled;
    const currentMode = isPartnerModeEnabled ? partnerMode : mode;
    const currentModeFormatted = isPartnerModeEnabled ? partnerModeFormatted : modeFormatted;

    if (this.state.isLoading || !user.isAuthenticated) {
      return null;
    }

    return (
      <Wrapper
        context={{
          user,
          experiments: this.getOnboardingExperiment(),
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
                  mode={currentMode}
                  modeFormatted={currentModeFormatted}
                  showGSTModal={hasGSTIN ? undefined : this.showGSTModal}
                  onSwitchMode={this.switchMode}
                  onSwitchMerchant={this.switchMerchant}
                  showMobileNav={this.props.windowWidth < 950}
                  org={this.props.org}
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
              modeFormatted={currentModeFormatted}
              fullPageView={this.renderFullPageView}
            />

            {!this.renderFullPageView && (
              <Footer showMobileNav={this.props.windowWidth < 950} user={user} />
            )}

            {/* Creates Portal for the comp */}
            <ModalDialog />
            <Notifications />
          </TwoFactorVerificationProvider>
        </div>
        {currentMode === 'test' && !isMobileDevice() && (
          <div style={{ position: 'relative' }}>
            <HighlightTestMode onSwitchMode={this.switchMode} />
          </div>
        )}
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

const mapStateToProps = (state) => {
  return {
    ...state.session,
    baseLocation: state.app.baseLocation,
    config: state.config,
    windowWidth: state.app.windowWidth,
    merchant_gst: state.profile.merchant_gst,
  };
};

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      ...ModalActions,
      ...SessionActions,
      ...ConfigActions,
      ...NotificationActions,
      updateTwoFactorVerified,
      fetchGST,
      fetchTicketsRaisedByAgents,
      fetchActiveTickets,
      resizeWindow,
      openModal,
      closeModal,
      fetchInstantSettlements,
      fetchTrustedBadgeStatus,
      fetchMerchantReferralDetail,
      fetchPayments,
    },
    dispatch,
  );

export default compose(
  withRouter,
  connect(mapStateToProps, mapDispatchToProps),
  // eslint-disable-next-line babel/new-cap
  RTracking(
    ({ user, mode }) => {
      let utm = null;
      let gclid = null; //Google click id, analytics will try to capture and save to cookie if present.
      let browser_details = {};
      const query = qs.parse(window.location.search);
      let source = 'pg';
      let u = {};
      if (user && user.user) {
        const device_type = isMobileDevice() ? 'mweb' : 'dweb';
        u = {
          email_id: user.user.email,
          user_id: user.user.id,
          mid: user.current,
          user_role: user.role,
          business_type: user.business_type,
          activation_status: user.activated,
          is_reg_auto_kyc_enabled: user.isRegAutoKYCEnabled,
          is_instant_activation_enabled: user.isInstantActivationEnabled,
          is_aadhar_ekyc_mandatory: user.isAadharEkycMandatory,
          is_gstin_mandatory: user.isGstinMandatory,
          user_business_category: user.business_category,
          user_business_sub_category: user.business_subcategory,
          device_type,
        };
      }
      if (query.merchant) {
        source = query.merchant;
      }
      if (typeof window.razorpayAnalytics !== 'undefined') {
        utm = window.razorpayAnalytics.utils.getLandingParams();
        gclid = window.razorpayAnalytics.utils.getCookie('gclid');
        if (typeof window.razorpayAnalytics.utils.getBrowserDetails !== 'undefined') {
          browser_details = window.razorpayAnalytics.utils.getBrowserDetails();
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
  ),
)(App);
