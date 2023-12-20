import { Component, Suspense } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'common/deprecated/withRouter';
import moment from 'moment';
import { createSidetab, createPopup } from '@typeform/embed';
import errorService from '@razorpay/universe-utils/errorService';
import Loader from 'common/ui/Loader';
import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import ModalDialog from 'common/ui/ModalDialog';
import { removeItem } from 'common/utils/localStorage';
import { analyticsTrack } from 'common/utils/analytics';
import { initLumberjack, initRefiner, initSegment } from 'common/utils/trackers';
import { initSentry } from 'common/utils/observability';
import Notifications from 'common/ui/Notifications';
import LocalStorageService from 'common/utils/localStorage';
import debounce from 'common/utils/debounce';
import Sidebar from 'merchant/components/Sidebar';
import SidebarV2 from 'merchant/components/SidebarV2';
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
import { fetchEligibilityForNcRevamp } from 'merchant/reducers/home';
import { matchFullPageView } from 'merchant/routes';
import {
  classList,
  isPresent,
  paiseToRupees,
  mergeCurrencyFormatting,
  getCommonAnalyticsProperties,
  isConfigTagAPISupported,
} from 'common/utils/rzp-utils';
import { isMobileDevice } from 'merchant/components/Home/data';
import ajax, { merchantFetch } from 'merchant/utils/ajax';
import rolesList from 'merchant/helpers/permissions/roles-list';
import RTracking from 'react-tracking';
import qs from 'query-string';
import Wrapper from 'common/components/Bootstrap/Wrapper';
import { fetchTrustedBadgeStatus } from 'merchant/reducers/trustedBadge.js';
import * as EventActions from 'merchant/reducers/trackEvents';
import LogoutDialog from 'merchant/components/LogoutDialog';
import { closeModal, openModal } from 'merchant_common/reducers/modals';
import { fetchMerchantReferralDetail } from 'merchant/reducers/merchantReferral';
import { fetchInstantSettlements, fetchPayments } from 'merchant/reducers/collection';
import { fetchAmount } from 'merchant/reducers/fetchTransaction';
import { bindActionCreators, compose } from 'redux';
import PartnerActivationRequiredModal from 'merchant/views/PartnerDashboard/Activation/Components/ActivationRequiredModal';
import _refiner from 'refiner-js';
import { getCookie, setCookie } from 'common/utils/cookies';
import RequestEmailModal from 'merchant_common/containers/ReportsAsync/GenerateReportPanel/AddEmail/RequestEmailModal';
import { isPartnerPage } from 'merchant/utils/isPartnerPage';
import getMobileDetect from 'common/utils/mobileDetect';
import { isPgMerchant } from 'merchant/components/Activation/ActivationUtils';
import currencies from '../constants/currency';
import { setRecommendedProduct } from 'merchant/components/Activation/ActivationUtils';
import { Teams, Ranks } from 'common/new-ui/ErrorBoundary';
import { Helmet, HelmetProvider } from 'react-helmet-async';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import { LOGOUT_ERROR, DEFAULT_TIMEOUT_IN_SECONDS } from 'merchant/constants/dates';
import lazy from 'merchant/routes/LazyLoader';
import { SplitzRoutesBasedService } from 'common/splitz/components/SplitzRoutesBasedService';
import cloneDeep from 'lodash/cloneDeep';
import { withSplitzService } from 'common/splitz';
import { withI18Service } from 'common/i18';
import { fetchConfigTags } from 'merchant/reducers/session';
import graphqlClient from 'common/services/graphql/graphql-client';

const PARTNER_ACTIVATION_APPLICABLE_TYPES = ['reseller'];

// const WebViewHeader = lazy(() =>
//   import(/* webpackChunkName: 'webview header' */ 'merchant/components/HeaderNav/WebViewHeader'),
// );

const IdleTimer = lazy(() =>
  import(/* webpackChunkName: "IdleTimer" */ 'merchant/containers/Home/IdleTimer'),
);

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
        this.props.location.pathname.startsWith('/partners') &&
        this.props?.user?.isIndependentPartnerKYCEnabled,
      isPartnerKYCActivated: false,
      isFeedbackFormCreated: false,
      isWebView: false,
      isShowFestiveAnimation: false,
    };
    this.handleResize = debounce(this.handleResize.bind(this), 200);
    this.handleFestiveAnimeAction = this.handleFestiveAnimeAction.bind(this);
  }

  onIdle = () => {
    const { logout, showNotification } = this.props;
    try {
      document.body.dispatchEvent(new CustomEvent('NOT_AUTHENTICATED', { bubbles: true }));
      // Show session timeout popup for 1.5 seconds & then hit logout API
      setTimeout(async () => {
        const resp = await logout();
        const { success } = resp;
        !success &&
          showNotification({
            type: 'error',
            message: LOGOUT_ERROR,
          });
      }, 1500);
    } catch (error) {
      showNotification({
        type: 'error',
        message: error ? error : LOGOUT_ERROR,
      });
    }
  };

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

  partnerActivationKycCallback = ({ data, user, currentMode }) => {
    let currentPartnerMode = LocalStorageService.getItem(this.partnerModeToken);
    let isPartnerKYCActivated = LocalStorageService.getItem(
      `is_partner_activated--${user?.current}`,
    );
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
  };

  fetchPartnerActivationStatusUpdate = ({ data }) => {
    let user = cloneDeep(this.props.user);
    const { activation_status = '' } = data?.partner_activation;

    if (user.merchants[user.current]) {
      user.merchants[user.current] = {
        ...user.merchants[user.current],
        partner: {
          ...user.merchants[user.current]?.partner,
          activation_status,
        },
      };

      this.props.updateSession({ user });
    }
  };

  fetchPartnerActivationStatusCallback = ({
    data,
    user,
    currentMode,
    partner_type,
    isBankingRequest,
  }) => {
    if (!user.isActivated && this.state.isPartnerModeEnabled) {
      this.partnerActivationKycCallback({ data, user, currentMode });
    }
    if (!isBankingRequest && PARTNER_ACTIVATION_APPLICABLE_TYPES.includes(partner_type)) {
      this.fetchPartnerActivationStatusUpdate({ data });
    }
  };

  // nosemgrep
  UNSAFE_componentWillMount() {
    const user = window.rzp_user;

    // Init lumberjack
    initLumberjack();

    const self = this;
    window.addEventListener('NOT_AUTHENTICATED', function () {
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

    if (this.props.user?.isProductLedOnboarding) {
      Promise.all([this.props.fetchTransactionAmount(user?.created_at), this.fetchOrg()])
        .then((response) => {
          const isOrgRZP = response?.[1]?.data?.custom_code === 'rzp';
          const transactionAmount = response?.[0]?.data?.firstTransaction?.result?.length
            ? paiseToRupees(data.firstTransaction.result[0].base_amount)
            : 0;

          const pathname = this.props.history.location.pathname;
          if (
            transactionAmount === 0 &&
            isPgMerchant(user) &&
            user?.activated &&
            isOrgRZP &&
            (!pathname || ['/', '/dashboard'].includes(pathname))
          ) {
            this.props.history.push('/api-keys');
          }
        })
        .catch(() => {});
    }

    Promise.all([
      this.fetchUser().then(({ data }) => {
        const user = data;
        const role = user.userRole;
        //set graphql x-dashboard-user-id, x-dashboard-merchant-id
        /** gql.setHeader('x-dashboard-user-id', user.user.id ) */
        graphqlClient.setHeaders({
          'x-dashboard-user-id': user.current,
          'x-dashboard-merchant-id': user.merchant.id,
        });

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

        if (user.isProductRecommendationEnabled) {
          setRecommendedProduct();
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
        graphqlClient.setHeader('x-org-id', data.id);
        const orgCode = (this.orgCode = data.custom_code);
        if (orgCode && orgCode !== 'rzp') {
          applyTheme(data);
        }
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
        const promiseList = [fetchFeaturesAjax(response[0].current)];
        const countryCode = this.props?.user?.merchant?.country_code;
        const isSupported = isConfigTagAPISupported(countryCode);
        if (isSupported) {
          const configTagPromise = this.props.fetchConfigTags(countryCode.toLowerCase());
          promiseList.push(configTagPromise);
        }
        /**
         * Utilizing allSettled instead of all because in case
         * of Promise.all if one promise fails, we would
         * receive only failed api response resulting in loss
         * of other api which might have succeeded.
         * Promise.allSettled will return all error/response.
         */
        // Fetch features before displaying other views
        Promise.allSettled(promiseList)
          .catch((_) => _)
          .then(([featureApi, configApi]) => {
            // setting default values incase either api fails
            const data = featureApi.value ? featureApi.value : {};
            const configTags = configApi?.value ? configApi.value.data.UIControls : {};
            const user = new User(response[0]);
            user.features = setFeatures(data.success ? data.data.features : []);
            user.configTags = { ...configTags };

            this.props.updateSession({ user, mode: currentMode });
            this.renderFullPageView = this.getFPView(this.props.location);

            removeSplashLoader();
            this.setState({ isLoading: false });

            // Decoupled this api from SSR can be loaded later
            // Calling these APIs post the features are  because features are overriding campaigns and tags
            // Can be refactored later for optimizing render
            this.props.fetchUserTags();
            this.props.fetchCampaigns();

            const merchantsSettlementStatus = JSON.parse(
              LocalStorageService.getItem('merchantsSettlementStatus'),
            );

            const esOndemandSettlementDisabled = !user.isOndemandSettlementEnabled;
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
          });

        const { partner_type } = user?.merchants?.[user.current] || {};
        const isBankingRequest = window?.is_banking_request;
        // use partner mode if merchant kyc is not activated and it's enabled
        if (
          (!user.isActivated && this.state.isPartnerModeEnabled) ||
          (!isBankingRequest && PARTNER_ACTIVATION_APPLICABLE_TYPES.includes(partner_type))
        ) {
          this.fetchPartnerActivationStatus().then(({ data }) => {
            this.fetchPartnerActivationStatusCallback({
              data,
              user,
              currentMode,
              partner_type,
              isBankingRequest,
            });
          });
        }
      })
      .catch(() => {
        removeSplashLoader();
        this.setState({ isLoading: false });
      });
    this.props.fetchConfig();
    this.props.fetchTrustedBadgeStatus();
    this.props.fetchMerchantReferralDetail();
    this.props.fetchGST();
    this.fetchSupportedCurrencies();

    const signUpFormStatus = LocalStorageService.getItem('sign_up_exp_status');
    if (user?.merchants && Object.keys(user.merchants).length === 1) {
      if (this.props.user?.isActivationFormFullView) {
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
      } else if (
        this.props.user?.showL1FormOnLogin &&
        signUpFormStatus &&
        signUpFormStatus === 'sign_up_completed'
      ) {
        this.props.trackEvents({
          objectName: 'L1 form on login',
          actionName: 'displayed',
          screen: 'KYC Document',
          properties: {
            experiment_name: 'show_L1_Form_on_login',
            ...getCommonAnalyticsProperties(window.rzp_user),
          },
        });
        LocalStorageService.setItem('sign_up_exp_status', 'kyc_form_fill_started');
        if (isMobileDevice()) {
          this.props.history.push('/onboarding/steps');
        } else {
          this.props.history.push('/activation');
        }
      }
    }
    if (this.props.user.isFeEasyDashboardNCEnabled) {
      try {
        this.props.fetchEligibilityForNcRevamp();
      } catch (error) {
        errorService.captureError(error, {
          tags: {
            team: Teams.GROWTH,
          },
          rank: Ranks.P2,
        });
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

  handleFestiveAnimation() {
    setTimeout(() => {
      this.setState({
        isShowFestiveAnimation: true,
      });
    }, 1500);
  }

  handleFestiveAnimeAction() {
    if (this.state.isShowFestiveAnimation) {
      this.setState({
        isShowFestiveAnimation: false,
      });
    }
  }

  createFeedbackForms() {
    this.setState({ isFeedbackFormCreated: true });
    this.fetchUser().then(({ data }) => {
      const user = data;
      const hidden = {
        mid: `${user.id}`,
        source: 'dashboard',
        email: `${user.email}`,
      };
      if (!isPartnerPage()) {
        if (user.showNPSSurvey() === true) {
          const goLiveNPSEnableTypeForm = createSidetab(
            'piCrdFI8', // go live survey
            {
              width: 500,
              buttonText: 'Feedback',
              hideHeaders: true,
              hideFooters: true,
              hidden,
              onSubmit: this.closeGoLiveSurvey,
            },
          );
          const nonGoLiveNPSEnableTypeForm = createSidetab(
            'LLaFW6pQ', // non go live survey
            {
              width: 500,
              buttonText: 'Feedback',
              hideHeaders: true,
              hideFooters: true,
              hidden,
              onSubmit: this.closeNonGoLiveSurvey,
            },
          );
          // saving reference typeform
          this.setState({
            goLiveNPSEnableTypeForm,
            nonGoLiveNPSEnableTypeForm,
          });
        }
      }
    });
  }
  componentDidMount() {
    window.addEventListener('resize', this.handleResize);
    this.createFeedbackForms();
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

      this.handleFestiveAnimation();

      if (getMobileDetect().isWebView()) {
        this.setState({ isWebView: true });
      }
    }
  }
  componentDidUpdate(prevProps) {
    const { isFeedbackFormCreated, goLiveNPSEnableTypeForm, nonGoLiveNPSEnableTypeForm } =
      this.state;
    const { location } = this.props;
    if (prevProps.location.pathname !== location.pathname) {
      if (
        isPartnerPage() &&
        isFeedbackFormCreated &&
        goLiveNPSEnableTypeForm &&
        nonGoLiveNPSEnableTypeForm
      ) {
        this.setState({ isFeedbackFormCreated: false });
        this.closeGoLiveSurvey();
        this.closeNonGoLiveSurvey();
      } else if (!isFeedbackFormCreated) {
        this.createFeedbackForms();
      }
    }
  }
  UNSAFE_componentWillReceiveProps({ user, location, baseLocation, org }) {
    const { goLiveNPSEnableTypeForm, nonGoLiveNPSEnableTypeForm, isPartnerModeEnabled } =
      this.state;
    if (user.isAuthenticated) {
      const role = user.userRole;
      this.redirectToRoute(role);

      this.renderFullPageView = this.getFPView(baseLocation || location);
    }
    if (org && user) {
      if (!isPartnerPage()) {
        const goLiveSurveyShowed = !!LocalStorageService.getItem(
          'razorpay_go_live_nps_survey_showed',
        );
        if (
          org?.custom_code?.toLowerCase() === 'rzp' && // only for razorpay org
          user.showNPSSurvey() && // experiment check
          !goLiveSurveyShowed &&
          !isMobileDevice() &&
          goLiveNPSEnableTypeForm
        ) {
          const takeGoLiveNPSSurvey = this.dateIsInRange(user.created_at, [
            ['2022-02-01', '2022-02-28'],
          ]);
          this.setState({ goLiveNPSSurveyPopup: takeGoLiveNPSSurvey });
        }

        const nonGoLiveSurveyShowed = !!LocalStorageService.getItem(
          'razorpay_non_go_live_nps_survey_showed',
        );
        if (
          org?.custom_code?.toLowerCase() === 'rzp' && // only for razorpay org
          user.showNPSSurvey() && // experiment check
          !nonGoLiveSurveyShowed &&
          !isMobileDevice() &&
          nonGoLiveNPSEnableTypeForm
        ) {
          const takeNonGoLiveNPSSurvey = this.dateIsInRange(user.created_at, [
            ['2021-11-01', '2021-11-30'],
            ['2021-08-01', '2021-08-31'],
            ['2021-02-01', '2021-02-28'],
          ]);
          this.setState({ nonGoLiveNPSSurveyPopup: takeNonGoLiveNPSSurvey });
        }
      }
      const newIsPartnerModeEnabled =
        location.pathname.startsWith('/partners') &&
        user.isIndependentPartnerKYCEnabled &&
        user.partner_type === 'reseller';

      if (isPartnerModeEnabled !== newIsPartnerModeEnabled) {
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
    this.state.goLiveNPSEnableTypeForm.unmount();
  };

  closeNonGoLiveSurvey = () => {
    this.setState({ nonGoLiveNPSSurveyPopup: false });
    this.state.nonGoLiveNPSEnableTypeForm.unmount();
  };

  fetchSupportedCurrencies() {
    if (!window.rzp_user) {
      return Promise.resolve();
    }
    return merchantFetch('currency/all/proxy')
      .then(({ data }) => {
        if (data === null) {
          window.currencyList = currencies;
          return;
        }
        window.currencyList = mergeCurrencyFormatting(data);
      })
      .catch(() => {
        window.currencyList = currencies;
      });
  }

  fireMTUFunnelEvents = (user) => {
    const isUnregisteredBusiness = user.isUnregisteredBusiness;
    const eventLabel = `MTU-Funnel${user.isUnregisteredBusiness ? '-Unreg' : ''}`;
    setTrackData({
      eventCategory: 'Dashboard - Instant Activations Live',
      eventAction: 'Login',
      eventLabel,
    })();

    analyticsTrack({
      objectName: 'New',
      actionName: 'MTU',
      screen: 'home page',
      properties: {
        eventLabel,
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
      toFacebook: true,
    });

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

    analyticsTrack({
      objectName: 'Active',
      actionName: 'Login',
      screen: 'home page',
      properties: {
        eventLabel,
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
      toFacebook: true,
    });

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
          .catch(() => {});
        break;
      case 2:
        this.fireMTUAudienceEvents(user);
        break;
    }
  };

  fetchUser() {
    // Test purpose code
    // window.rzp_user = {
    //   ...window.rzp_user,
    //   merchant: {
    //     ...window.rzp_user.merchant,
    //     currency: 'MYR',
    //   },
    // };
    // This need to be refactored, we should not be using window.rzp_user
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

        window.trackHubs?.({
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
        case rolesList.SELLERAPP:
        case rolesList.AGENT:
          this.props.history.replace('/paymentlinks');
          break;

        case rolesList.SUPPORT:
          this.props.history.replace('/payments');
          break;

        case null:
          this.props.history.replace('/profile');
          break;
      }
    }
  }

  fetchPartnerActivationStatus = () => {
    return merchantFetch({ url: 'partner/activation', mode: 'live' });
  };

  switchMode = (mode, callback = () => {}) => {
    window.rzpAnalytics?.({
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
      window.trackHubs?.({
        name: 'update_property',
        data: {
          is_live: true,
        },
      });
      location.reload();
    }
  };

  handlePartnerModeSwitch = (mode) => {
    const user = this.props.user;
    const merchantId = user?.current;

    if (mode === 'live' && this.state.isPartnerModeEnabled) {
      analyticsTrack({
        objectName: 'Partner KYC Form',
        actionName: 'Opened',
        screen: 'Switch Mode',
        properties: {
          source: 'Live mode',
          section: 'Switch Mode',
          partnerID: merchantId,
        },
      });
    }
    const isPartnerActivated =
      user?.merchants[merchantId]?.partner?.activation_status === 'activated';
    if (mode === 'live' && !isPartnerActivated) {
      analyticsTrack({
        objectName: 'Partner KYC Activation Required Modal',
        actionName: 'Opened',
        screen: 'Switch Mode',
        properties: {
          source: 'Live mode',
          section: 'Pop up',
          partnerID: merchantId,
        },
      });
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
    const matchView = matchFullPageView(location.pathname, {
      i18: this.props.i18,
      splitz: {
        abExperiments: this.props.splitz.abExperiments,
      },
    });
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
    const {
      goLiveNPSSurveyPopup,
      nonGoLiveNPSSurveyPopup,
      goLiveNPSEnableTypeForm,
      nonGoLiveNPSEnableTypeForm,
    } = this.state;
    if (!goLiveNPSSurveyPopup && goLiveNPSEnableTypeForm) {
      goLiveNPSEnableTypeForm.unmount();
    }
    if (!nonGoLiveNPSSurveyPopup && nonGoLiveNPSEnableTypeForm) {
      nonGoLiveNPSEnableTypeForm.unmount();
    }
    return (
      <>
        {goLiveNPSSurveyPopup &&
          !LocalStorageService.getItem('razorpay_go_live_nps_survey_showed') && (
            <>
              {LocalStorageService.setItem('razorpay_go_live_nps_survey_showed', 1)}
              {goLiveNPSEnableTypeForm.open()}
            </>
          )}
        {nonGoLiveNPSSurveyPopup &&
          !LocalStorageService.getItem('razorpay_non_go_live_nps_survey_showed') && (
            <>
              {LocalStorageService.setItem('razorpay_non_go_live_nps_survey_showed', 1)}
              {nonGoLiveNPSEnableTypeForm.open()}
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
      isAdharEkycRequired: user.isAdharEkycRequired,
      isAdharEkycRequiredForTrustSocietyNgo: user.isAdharEkycRequiredForTrustSocietyNgo,
      isDigilockerEkyc: user.isDigilockerEkyc,
      isFeEasyDashboardNCEnabled: user.isFeEasyDashboardNCEnabled,
      isMsmeCertificateEnabled: user.isMsmeCertificateEnabled,
    };
  };

  getSubMerchantId = () => {
    const regex = /\/partners\/submerchants\/onboarding\/acc_(.+)\//g;
    const found = regex.exec(this.props.location.pathname);
    return found && found[1] ? found[1] : '';
  };

  showSidebarV2 = () => {
    const { user } = this.props;
    return (
      user.isOrgRZP &&
      !isMobileDevice() &&
      user.isLeftNavRevampEnabled &&
      !user.isPartner() &&
      !user.isSourceRX
    );
  };

  render() {
    const { user, config, org, mode, modeFormatted, partnerModeFormatted, partnerMode } =
      this.props;

    const hasGSTIN = this.props.merchant_gst.p_gstin || this.props.merchant_gst.gstin;
    const isPartnerModeEnabled = this.state.isPartnerModeEnabled;
    const currentMode = isPartnerModeEnabled ? partnerMode : mode;
    const currentModeFormatted = isPartnerModeEnabled ? partnerModeFormatted : modeFormatted;
    const submerchantId = this.getSubMerchantId();
    const mobileWidth = user.isUniversalSearchEnabled ? 1280 : 950;
    const isTimeoutEnabled = org?.features?.indexOf('logout_admin_inactivity') > -1;
    const timeoutInMilliseconds =
      (org?.merchant_session_timeout_in_seconds ?? DEFAULT_TIMEOUT_IN_SECONDS) * 1000;
    if (this.state.isLoading || !user.isAuthenticated) {
      return null;
    }
    const sidebarProps = {
      user: user,
      logoURL: org.main_logo_url,
      config: config.config,
      org_custom_code: org.custom_code,
    };

    return (
      <Wrapper
        context={{
          user,
          experiments: this.getOnboardingExperiment(),
          org,
          mode,
          submerchantId,
          isShowFestiveAnimation: this.state.isShowFestiveAnimation,
          handleFestiveAnimeAction: this.handleFestiveAnimeAction,
        }}
      >
        {isTimeoutEnabled && (
          <SuspenseWithLoader>
            <IdleTimer timeoutInMillisecond={timeoutInMilliseconds} onIdle={this.onIdle} />
          </SuspenseWithLoader>
        )}
        {this.props?.user?.isOrgCurlec && (
          <HelmetProvider>
            <Helmet>
              <title>Curlec By Razorpay</title>
            </Helmet>
          </HelmetProvider>
        )}
        <div className={classList('layout', this.orgCode, this.renderFullPageView && 'layout--fp')}>
          <TwoFactorVerificationProvider merchantFetch={merchantFetch} ajax={ajax}>
            {!this.renderFullPageView && !this.state.isWebView && (
              <React.Fragment>
                <HeaderNav
                  user={user}
                  mode={currentMode}
                  modeFormatted={currentModeFormatted}
                  showGSTModal={hasGSTIN ? undefined : this.showGSTModal}
                  onSwitchMode={this.switchMode}
                  onSwitchMerchant={this.switchMerchant}
                  showMobileNav={this.props.windowWidth < mobileWidth}
                  org={this.props.org}
                />
                {this.showSidebarV2() ? (
                  <SidebarV2 {...sidebarProps} />
                ) : (
                  <Sidebar {...sidebarProps} />
                )}
              </React.Fragment>
            )}

            {/* {this.state.isWebView && ( //@NOTE: this will be uncommented when we go live with new ui on webview.
              <Suspense fallback={null}>
                <WebViewHeader history={this.props.history} />
              </Suspense>
            )} */}

            {this.getSurveyForm()}
            <SplitzRoutesBasedService>
              <Content
                user={user}
                modeFormatted={currentModeFormatted}
                fullPageView={this.renderFullPageView}
                isWebView={this.state.isWebView}
              />
            </SplitzRoutesBasedService>
            {!this.renderFullPageView && !this.state.isWebView && (
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
    isWebView: state.app.isWebView,
  };
};

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      ...ModalActions,
      ...SessionActions,
      ...ConfigActions,
      ...NotificationActions,
      ...EventActions,
      updateTwoFactorVerified,
      fetchGST,
      resizeWindow,
      openModal,
      closeModal,
      fetchInstantSettlements,
      fetchTrustedBadgeStatus,
      fetchMerchantReferralDetail,
      fetchPayments,
      fetchTransactionAmount: fetchAmount,
      fetchEligibilityForNcRevamp,
      fetchConfigTags,
    },
    dispatch,
  );

export default compose(
  withRouter,
  connect(mapStateToProps, mapDispatchToProps),
  // eslint-disable-next-line babel/new-cap
  RTracking(
    ({ user, mode, isWebView }) => {
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
          is_web_view: isWebView,
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
)(withI18Service(withSplitzService(App)));
