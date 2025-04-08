import React, { Component } from 'react';
import moment from 'moment';
import { connect } from 'react-redux';
import rTracking from 'react-tracking';
import { compose } from 'redux';

import { withRouter } from 'common/deprecated/withRouter';
import { withI18Service } from 'common/i18';
import { ModalMask, Modal, ModalContent } from 'common/new-ui/Modal';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import { withSplitzService } from 'common/splitz';
import { isExperimentEnabled } from 'common/splitz/utils';
import { customRangeText } from 'common/ui/DateRangePicker';
import { analyticsTrack } from 'common/utils/analytics';
import { getCookie } from 'common/utils/cookies';
import debounce from 'common/utils/debounce';
import { getItem, setItem, removeItem } from 'common/utils/localStorage';
import {
  oldestTransactionQuery,
  getDefaultPaymentFilter,
  platformGroupingVals,
  groupByPlatform,
  OTHERS,
} from 'common/utils/pokedex';
import lazyLoader from 'merchant/routes/LazyLoader';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { API_ERROR, API_INVALID_RESP, isMobileDevice } from 'merchant/components/Home/data';
import WelcomeModal from 'merchant/components/Home/WelcomeModal';
import { selfServeTrackInitiate } from 'common/utils/selfServeAnalytics';
import {
  setRecommendedProduct,
} from 'merchant/components/Activation/ActivationUtils';
import LakshmiVilasBankBanner from 'merchant/components/Announcements/LakshmiVilasBankBanner';
import FraudDetectionModal from 'merchant/components/Home/FraudDetectionModal';
import InstantActivationSuccess from 'merchant/components/Home/InstantActivationSuccess';
import KYCStatusModal from 'merchant/components/Home/KYCStatusModal';
import KYCStatusModalOld from 'merchant/components/Home/KYCStatusModal-old';
import KycDetailsModal from 'merchant/components/Home/KycDetailsModal';
import PANVerificationStatusModal from 'merchant/components/Home/PANVerificationStatusModal';
import TnCModal from 'merchant/components/Home/TnCModal';
import M2MSuccessModal from 'merchant/components/M2M/M2MSuccessModal';
import ShowWhen, { showWhenUtil } from 'merchant/components/ShowWhen';
import { switchToMode } from 'merchant/containers/Home/OnboardingCard/SwitchToMode';
import rolesList from 'merchant/helpers/permissions/roles-list';
import { fetchPayments } from 'merchant/reducers/collection';
import { fetchLateAuthConfig } from 'merchant/reducers/config';
import { fetchAmount } from 'merchant/reducers/fetchTransaction';
import * as HomeActions from 'merchant/reducers/home';
import { fetch } from 'merchant/reducers/pokedex';
import { showOrHideHighlightMode, updateSession } from 'merchant/reducers/session';
import { fetchSupportDetail } from 'merchant/reducers/support_detail';
import * as EventActions from 'merchant/reducers/trackEvents';
import { fetchVirtualAccounts } from 'merchant/reducers/virtualaccounts';
import {
  fetchActivationDetails,
  getBannerAndModalVisibility,
  fetchMerchantWebsiteDetails,
} from 'merchant/reducers/websitecompliance';
import WorkflowStatus from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/components/WorkflowStatus';
import InternationalHPBanner from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/components/InternationalCards/components/InternationalHPBanner';
import {
  isBankAccountDetailsAllowed,
  isPaymentMethodEnabled,
} from 'merchant/views/AccountAndSettings/utils/conditionUtils';
import PartnerOnbr from 'merchant/views/PartnerDashboard/Onboarding/partnerOnbr';
import CardPaymentsBlockedBanner from 'merchant/views/Subscriptions/components/CardPaymentsBlocked/Banner';
import CardPaymentsBlockedModal from 'merchant/views/Subscriptions/components/CardPaymentsBlocked/Modal';
import * as ModalActions from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';

import FestiveAnimation from './FestiveAnimation';
import { isRTUXHomepageEnabled } from './RTUX/utils';
import {
  trackError,
  trackDatesChange,
  trackPlatformAnalyticsHidden,
  trackActivateAccount,
  trackTryDashboard,
  trackIAClose,
  iaActivations,
} from './ga';
import { isEligibleForFtuxV2 } from './FTUX/utils';

const Desktop = lazyLoader(() => import(/* webpackChunkName: 'merchantDesktop' */ './Desktop'));
const Mobile = lazyLoader(() => import(/* webpackChunkName: 'merchantMobile' */ './Mobile'));
const RTUXHomepage = lazyLoader(() => import(/* webpackChunkName: 'RTUXHomepage' */ './RTUX'));
const FTUXHomepage = lazyLoader(() => import(/* webpackChunkName: 'FTUXHomepage' */ './FTUX'));

const DATE_RANGE_PRESETS = [
  ['Past 7 Days', -7, 'days'],
  ['Past 30 Days', -30, 'days'],
  ['Past 90 Days', -90, 'days'],
  // ['All Time', -10, 'years'],
];
const defaultPreset = 1;

const getPreviousDates = ({ startDate, endDate }) => {
  const diff = endDate.diff(startDate);

  return {
    startDate: startDate.clone().subtract(diff, 'ms'),
    endDate: endDate.clone().subtract(1, 'day').subtract(diff, 'ms').endOf('day'),
  };
};

const bodyClass = ' analytics-v2-active';

// used to show titles for sections and also GA
const keymetricsSectionTitle = 'Transactions Overview';
const paymentInsightsTitle = 'Payment Insights';
const trafficSectionTitle = 'Traffic split on platforms';
const recentActivityTitle = 'Recent Activity';

class HomeContainer extends Component {
  constructor(props) {
    super(props);

    // recording new analytics interactions in hotjar
    if (typeof window.hj === 'function') {
      window.hj('trigger', 'new_analytics');
      window.hj('tagRecording', ['new_analytics']);
    }

    const endDate = moment().endOf('day');
    const startDate = endDate.clone().startOf('day');

    startDate.add(...DATE_RANGE_PRESETS[defaultPreset].slice(1));

    const { user, mode } = props;
    // onboarding card is shown if this is present in localstorage
    const onboardingCardToken = 'show_onboarding_card';
    // onboarding card first step is shown if this is present in localstorage
    const firstStepToken = 'onboarding_first_step';
    // partner onboarding is shown if user logs in for 1st time
    const partnerOnBoarding = 'partner_on_boarding_shown';

    // tokens particular for the current merchant
    this.onboardingBannerToken = `${onboardingCardToken}--${user.current}`;
    this.firstStepToken = `${firstStepToken}--${user.current}`;
    this.partnerOnBoardingToken = `${partnerOnBoarding}--${user.current}`;

    this.couponCode = 'UNLOCKFEST';
    this.isFestive = getCookie(`coupon_code--${user.current}`) === this.couponCode;
    this.autoOpenL1FormModal = true;

    /*
     * Earlier , the tokens apply at browser level, if old tokens are present
     * converting them specific to the merchants the current user can switch to
     */
    if (getItem(onboardingCardToken)) {
      Object.keys(user.merchants).forEach((key) => {
        setItem(`${onboardingCardToken}--${key}`, 'true');
      });

      removeItem(onboardingCardToken);
    }

    if (getItem(firstStepToken)) {
      Object.keys(user.merchants).forEach((key) => {
        setItem(`${firstStepToken}--${key}`, 'true');
      });

      removeItem(firstStepToken);
    }

    const hasAccessToOnboardingBanner =
      [rolesList.MANAGER, rolesList.OWNER, rolesList.ADMIN].indexOf(user.role) >= 0;

    this.hasAccessToOnboardingBanner = hasAccessToOnboardingBanner;

    const showOnboardingBanner = hasAccessToOnboardingBanner && getItem(this.onboardingBannerToken);
    const showOnboardingBannerFirstStep = getItem(this.firstStepToken);

    const roleToShowSupportDetailForm =
      [
        rolesList.MANAGER,
        rolesList.OPERATIONS,
        rolesList.ADMIN,
        rolesList.SUPPORT,
        rolesList.OWNER,
      ].indexOf(user.role) >= 0;
    const SUPPORT_DETAIL_LAST_POP_DISPLAY_DATE = getItem(
      `SUPPORT_DETAIL_LAST_POP_DISPLAY_DATE--${user.current}`,
    );
    if (mode === 'live' && !SUPPORT_DETAIL_LAST_POP_DISPLAY_DATE && roleToShowSupportDetailForm) {
      setItem(
        `SUPPORT_DETAIL_LAST_POP_DISPLAY_DATE--${user.current}`,
        moment().subtract(1, 'days').format('DD/MM/YYYY'),
      );
    }

    if (
      mode === 'live' &&
      !getItem(`SUPPORT_DETAIL_CURRENT_POPUP_COUNT--${user.current}`) &&
      roleToShowSupportDetailForm
    ) {
      setItem(`SUPPORT_DETAIL_CURRENT_POPUP_COUNT--${user.current}`, 10);
    }

    this.state = {
      startDate,
      endDate,
      oldestTransactionDate: {
        value: null,
        loading: false,
        error: '',
        ...getPreviousDates({ startDate, endDate }),
      },
      isMobile: isMobileDevice(),
      dateRangePresets: DATE_RANGE_PRESETS,
      showGroupingByPtfm: false,
      scrollAmountToStickHeader: 0,
      expandOnboardingBanner: showOnboardingBanner, // used for transition
      showOnboardingBanner,
      showOnboardingBannerFirstStep,
      // payments is used to change content in the integration step
      payments: {
        loading: true,
        items: [],
      },
      hasMinTransactionSD: false,
      referredMerchants: [],
      referredAmount: 0,
      isReferee: false,
      canShowL1ActivationModals: false,
    };

    /*
     * If token not present to show the banner,
     * Need to show the banner until the user integrates in live mode
     * which we can check by checking his live transactions
     *
     * If the user is in live mode, we make fetchAll payments in
     * RecentActivity component, which will be done using `onFetchPayments`
     * below
     */
    if (this.hasAccessToOnboardingBanner && !showOnboardingBanner) {
      if (user.activation_status !== 'activated' || !user.isActivated) {
        this.state = {
          ...this.state,
          showOnboardingBanner: true,
          showOnboardingBannerFirstStep: user.showInstantActivation
            ? user.isInstantActivationEnabled
              ? !user.instantActivation.isL1Submitted
              : !user.submitted
            : true,
          expandOnboardingBanner: true,
        };

        setItem(this.onboardingBannerToken, 'true');

        if (this.state.showOnboardingBannerFirstStep) {
          setItem(this.firstStepToken, 'true');
          this.props.showOrHideHighlightMode(false);
        }
      } else if (mode !== 'live') {
        this.props.fetchPayments({ mode: 'live' }).then((data) => {
          data = data.data;

          if (data && data.items && data.items.length === 0) {
            this.setShowOnboardingBanner();
          }
        });
      }
    }

    this.oldestTxnReqId = 0;
    this.onDatesChange = this.onDatesChange.bind(this);
    this.onFetchPayments = this.onFetchPayments.bind(this);
    this.setScrollAmountToStickHeader = this.setScrollAmountToStickHeader.bind(this);
    this.onHideOnboardingBanner = this.onHideOnboardingBanner.bind(this);
    this.onFirstStepClose = this.onFirstStepClose.bind(this);
    this.onExtraContentMount = this.onExtraContentMount.bind(this);
    this.onResize = debounce(this.onResize.bind(this), 500);
    this.onInstantActivationSuccess = this.onInstantActivationSuccess.bind(this);
    this.fetchRestrictionsIfAny = this.fetchRestrictionsIfAny.bind(this);
  }

  restrictedFeatures = [
    'disable_ondemand_for_loc',
    'disable_ondemand_for_card',
    'disable_ondemand_for_loan',
  ];

  featureName = {
    disable_ondemand_for_loc: 'LOC',
    disable_ondemand_for_card: 'Card',
    disable_ondemand_for_loan: 'Loan',
  };

  get isOnDemandDisabled() {
    const { user } = this.props;

    return this.restrictedFeatures.some((feature) => user.isFeatureEnabled(feature));
  }

  get settlementRestricted() {
    return this.props.user.isFeatureEnabled('es_on_demand_restricted') || this.isOnDemandDisabled;
  }

  fetchRestrictionsIfAny() {
    if (this.settlementRestricted) {
      this.props.fetchOndemandRestrictions();
    }
  }

  onInstantActivationSuccess(url) {
    return switchToMode(this.props.user.current, 'live', url);
  }

  onExtraContentMount(node) {
    this.extraContent = node;
  }

  fetchTxnsGroupedByPlatform() {
    // need to figureout whether we should show group by platform
    // or not

    const { startDate, endDate } = this.state;
    const { isAdmin, analyticsFetch } = this.props;

    const query = {
      filters: {
        default: [getDefaultPaymentFilter(startDate.unix(), endDate.unix())],
      },
      aggregations: {
        records: {
          agg_type: 'count',
          details: {
            index: 'payments',
            group_by: platformGroupingVals,
          },
        },
      },
    };

    return (analyticsFetch || fetch)(query, this.props.mode)
      .then((data) => {
        if (!data.success) {
          return API_ERROR;
        }

        if (!data.data || !data.data.records) {
          return API_INVALID_RESP;
        }

        data = groupByPlatform(data.data.records.result);

        return data;
      })
      .catch((err) => {
        if (window.APP_ENV !== 'production') console.error(err);

        return API_ERROR;
      }) /* eslint-disable */
      .then((data) => {
        /* eslint-enable */
        if (data.error) {
          trackError(`While Fetching Txns Grouped by Ptfm`);
          return this.props.showNotification({
            type: 'error',
            message: data.error,
            hidePrevious: true,
          });
        }

        if (!isAdmin) {
          const platforms = Object.keys(data);

          // if we do not get platforms for given daterange
          // do not show grouping
          if (platforms.length === 0) {
            return false;
          }

          let grandTotal = 0;

          const totalByPlatform = platforms.reduce((group, platform) => {
            group[platform] = data[platform].reduce((sum, entry) => {
              return sum + entry.value;
            }, 0);

            grandTotal += group[platform];

            return group;
          }, {});

          // if the txn count of platforms for given daterange
          // do not show grouping
          if (grandTotal === 0) {
            return false;
          }

          const ratio = (totalByPlatform[OTHERS] || 0) / grandTotal;

          // if `Others` platform count is greater than 30%
          // do not show grouping
          if (ratio > 0.3) {
            trackPlatformAnalyticsHidden(ratio * 100);
            return false;
          }
        }

        // this will show group by platform dropdowns and also
        // traffic graph
        this.setState({
          showGroupingByPtfm: true,
        });

        return false;
      });
  }

  fetchOldestTransactionDate() {
    let { oldestTransactionDate } = this.state;

    const { onFirstTxnDate, analyticsFetch } = this.props;

    const oldestTxnReqId = ++this.oldestTxnReqId;

    oldestTransactionDate = { ...oldestTransactionDate };

    oldestTransactionDate.error = '';
    oldestTransactionDate.loading = true;

    this.setState({
      oldestTransactionDate: { ...oldestTransactionDate },
    });

    return (analyticsFetch || fetch)(oldestTransactionQuery, this.props.mode)
      .then((data) => {
        if (oldestTxnReqId !== this.oldestTxnReqId) {
          return false;
        }

        if (!data.success) {
          return API_ERROR;
        }

        if (!data.data || !data.data.records) {
          return API_INVALID_RESP;
        }

        const records = data.data.records.result[0];
        const value = records && records.created_at;

        return { value };
      })
      .catch((err) => {
        if (window.APP_ENV !== 'production') console.error(err);

        return API_ERROR;
      })
      .then((data) => {
        oldestTransactionDate.loading = false;

        if (!data || data.error) {
          if (data.error) {
            oldestTransactionDate.error = data.error;

            trackError(`While Fetching Oldest txn date`);

            this.props.showNotification({
              type: 'error',
              message: data.error,
              hidePrevious: true,
            });
          }

          this.setState({
            oldestTransactionDate,
          });

          return onFirstTxnDate && onFirstTxnDate();
        }

        this.setState({
          oldestTransactionDate: {
            ...oldestTransactionDate,
            value: data.value,
          },
        });

        return onFirstTxnDate && onFirstTxnDate(data.value);
      });
  }

  onDatesChange(startDate, endDate, selectedPreset) {
    const { oldestTransactionDate } = this.state;

    this.setState({
      startDate,
      endDate,
      oldestTransactionDate: {
        ...oldestTransactionDate,
        ...getPreviousDates({ startDate, endDate }),
      },
    });

    if (selectedPreset.name === customRangeText) {
      trackDatesChange(startDate, endDate);
    }

    selfServeTrackInitiate({
      selfServeAction: 'Payment Details Fetched',
      page: 'Home',
      screen: 'Home',
    });
  }

  UNSAFE_componentWillMount() {
    // to style react-power-selct specific to this tab
    document.body.className += bodyClass;
    const { abExperiments } = this.props.splitz;

    const isRTUXHomepage = isRTUXHomepageEnabled({ user: this.props.user, abExperiments });
    if (!isRTUXHomepage) {
      this.fetchOldestTransactionDate();
      this.fetchTxnsGroupedByPlatform();
    }
  }

  componentWillUnmount() {
    document.body.className = document.body.className.replace(bodyClass, '');
    window.removeEventListener('resize', this.onResize);
    window.removeEventListener('click', () => {});
  }

  setScrollAmountToStickHeader() {
    const scrollAmountToStickHeader = this.extraContent ? this.extraContent.clientHeight : 0;

    this.setState({ scrollAmountToStickHeader });
  }

  onResize() {
    this.setState({
      isMobile: isMobileDevice(),
    });

    this.setScrollAmountToStickHeader();
  }

  componentDidMount() {
    const { abExperiments } = this.props.splitz;
    const isRTUXHomepage = isRTUXHomepageEnabled({ user: this.props.user, abExperiments });

    if (!isRTUXHomepage) {
      this.props.fetchCurrentBalance();
      this.props.fetchSettlementAmount();
      this.fetchRestrictionsIfAny();
      this.props.fetchBalanceConfig();
      this.props.fetchLateAuthConfig();

      const { user, fetchMerchantWebsiteDetails, websiteSectionDetailsData } = this.props;
      if (user.isWebsiteComplianceFlowEnabled) {
        const {
          data: websiteSectionData,
          error,
          loading: isDetailsLoading,
        } = websiteSectionDetailsData;
        if (!Object.keys(websiteSectionData).length && !error && !isDetailsLoading) {
          fetchMerchantWebsiteDetails();
        }
      }
    }

    this.setScrollAmountToStickHeader();
    this.props.fetchSupportDetail();

    window.addEventListener('resize', this.onResize);

    const shouldShowMobileHotjarSurvey = showWhenUtil({
      additionalCondition: () => {
        const { abExperiments } = this.props.splitz || {
          abExperiments: { mobile_hotjar_survey: undefined },
        };
        return isExperimentEnabled(abExperiments?.mobile_hotjar_survey);
      },
    });

    if (shouldShowMobileHotjarSurvey) {
      setTimeout(() => {
        /* eslint-disable */
        window.hj && window.hj('trigger', 'MOBILE_SURVEY');
        /* eslint-enable */
      }, 0);
    }

    window.addEventListener('click', () => {
      if (this.autoOpenL1FormModal) {
        this.autoOpenL1FormModal = false;
      }
    });

    const signUpFormStatus = getItem('sign_up_exp_status');

    if (
      signUpFormStatus &&
      signUpFormStatus === 'sign_up_completed' &&
      this.props.user.autoOpenL1Form &&
      !this.props.user.activation_form_milestone
    ) {
      setTimeout(() => {
        if (this.autoOpenL1FormModal) {
          analyticsTrack({
            objectName: 'Auto Open Activation form on login',
            actionName: 'open',
            screen: 'home page',
            properties: {
              loginL1Experiment: 'auto open activation form modal',
              ...getCommonAnalyticsProperties(window.rzp_user),
            },
          });
          this.setState({
            showOnboardingBannerFirstStep: false,
            showOnboardingBanner: false,
          });
          this.onFirstStepClose();
          this.hideWelcomeModalCTAs = false;
          this.props.history.push('/activation');
        }
      }, 7000);
      this.hideWelcomeModalCTAs = true;
    }
    setItem('sign_up_exp_status', 'kyc_form_fill_started');

    const { getBannerAndModalVisibility, fetchActivationDetails } = this.props;

    Promise.all([fetchActivationDetails(this.props.user.id), getBannerAndModalVisibility()]).then(
      (responses) => {
        const activationData = responses?.[0]?.data ?? {};
        const { business_website, appstore_url, playstore_url } = activationData;
        if (business_website || appstore_url || playstore_url) {
          setRecommendedProduct({ overrideProduct: 'payment_gateway' });
        }
        this.setState({ canShowL1ActivationModals: true });
        const referredMerchantData = responses[1];
        this.setReferredMerchantData(referredMerchantData);
      },
    );
  }

  closeOnboardingStep() {
    this.props.closeOnboardingStep();
  }

  onFirstStepClose() {
    this.props.showOrHideHighlightMode(true);
    this.setState(
      {
        showOnboardingBannerFirstStep: false,
      },
      () => {
        // when first step is closed, the banner height gets changes,
        // adjusting the scroll amount when the datepicker bar should stick
        // on top of the page
        this.setScrollAmountToStickHeader();
      },
    );

    removeItem(this.firstStepToken);
  }

  onHideOnboardingBanner() {
    this.setState(
      {
        expandOnboardingBanner: false,
      },
      () => {
        this.setState({
          showOnboardingBanner: false,
        });

        this.setScrollAmountToStickHeader();
      },
    );

    removeItem(this.onboardingBannerToken);
  }

  setShowOnboardingBanner() {
    const { user } = this.props;
    const showOnboardingBannerFirstStep = user.showInstantActivation
      ? !user.instantActivation.isL1Submitted
      : true;

    this.setState(
      {
        showOnboardingBanner: true,
        showOnboardingBannerFirstStep,
      },
      () => {
        this.setState(
          {
            expandOnboardingBanner: true,
          },
          () => {
            this.setScrollAmountToStickHeader();
          },
        );
      },
    );

    if (showOnboardingBannerFirstStep) {
      setItem(this.onboardingBannerToken, 'true');
    }

    setItem(this.firstStepToken, 'true');
  }

  onFetchPayments(data) {
    const { user, mode } = this.props;

    const items = (data && data.items) || [];

    this.setState({
      payments: {
        loading: false,
        items,
      },
    });

    /*
     * When fetched payments in live mode, using recent activity component
     * we use it to show the banner , if there are no trasaction
     */
    if (user.isActivated && mode === 'live') {
      if (
        this.hasAccessToOnboardingBanner &&
        !this.state.showOnboardingBanner &&
        items.length === 0
      ) {
        this.setShowOnboardingBanner();
      }
    }

    /* To show support detail popup check num of transaction is >= 3
     * & mode === live. if yes set to true
     */
    if (mode === 'live' && items.length >= 3) {
      this.setState({ hasMinTransactionSD: true });
    }
  }

  compareDate = (curr, prev) => {
    const currDate = moment(curr, 'DD/MM/YYYY');
    const prevDate = moment(prev, 'DD/MM/YYYY');
    if (currDate > prevDate) return true;
    else return false;
  };

  setReferredMerchantData = (res) => {
    const referralSuccessCount = parseInt(res?.data?.referral_success_popup_count ?? 0, 10);
    const refereeSuccessCount = parseInt(res?.data?.referee_success_popup_count ?? 0, 10);
    if (referralSuccessCount !== 0 || refereeSuccessCount !== 0) {
      this.setState({
        referredMerchants: res.data.referee_name ?? [],
        referredAmount: res.data.referral_amount,
        isReferee: !!refereeSuccessCount,
      });
    }
  };

  closePartnerExplore = () => {
    const { history, closeModal } = this.props;
    history.replace('/dashboard');
    closeModal();
  };

  render() {
    const {
      mode,
      current_balance,
      tabsMeta,
      user,
      location,
      // following three props will be sent by admin analytics
      // - web/pokedex.js
      isAdmin,
      analyticsFetch,
      onFilterChange,
      showInstantActivationSuccess,
      showKYCDetails,
      showInstantActivationFraudModal,
      hideKYCDetailsModal,
      tracking,
      showPANStatus,
      showKYCStatus,
      kycStatusModalType,
      settlement_amount,
      merchantBalanceConfigs,
      lateAuthConfig,
      support_detail,
      kycStatusActivationDuration,
      ondemand_restrictions,
      showTnCModal,
      trackEvents,
      i18: { isConfigTagEnabled },
      splitz,
    } = this.props;

    const { activation_flow } = user;
    const { abExperiments } = splitz;

    const {
      startDate,
      endDate,
      oldestTransactionDate,
      dateRangePresets,
      showGroupingByPtfm,
      scrollAmountToStickHeader,
      showOnboardingBanner,
      expandOnboardingBanner,
      payments,
      showOnboardingBannerFirstStep,
      isMobile,
      hasMinTransactionSD,
      canShowL1ActivationModals,
    } = this.state;

    const {
      onHideOnboardingBanner,
      onFirstStepClose,
      onDatesChange,
      onFetchPayments,
      onExtraContentMount,
      setScrollAmountToStickHeader,
      closePartnerExplore,
    } = this;

    const roleToShowSupportDetailForm =
      [
        rolesList.MANAGER,
        rolesList.OPERATIONS,
        rolesList.ADMIN,
        rolesList.SUPPORT,
        rolesList.OWNER,
      ].indexOf(user.role) >= 0;

    const isValueFilled = !(
      support_detail.error && support_detail.error[0] === 'Merchant email type does not Exist'
    );

    const commonProps = {
      mode,
      current_balance,
      ondemand_restrictions: this.settlementRestricted && ondemand_restrictions,
      tabsMeta,
      isAdmin,
      analyticsFetch,
      onFilterChange,
      startDate,
      endDate,
      oldestTransactionDate,
      dateRangePresets,
      showGroupingByPtfm,
      scrollAmountToStickHeader,
      showOnboardingBannerFirstStep,
      expandOnboardingBanner,
      payments,
      // Handling first step in a different way if its instant activations
      showOnboardingBanner: showOnboardingBannerFirstStep
        ? !user.showInstantActivation || user.instantActivation.isL1Submitted
        : showOnboardingBanner,
      isMobile,
      showInstantActivation: user.showInstantActivation,

      onHideOnboardingBanner,
      onFirstStepClose,
      onDatesChange,
      onFetchPayments,
      onExtraContentMount,
      setScrollAmountToStickHeader,

      defaultPreset,
      keymetricsSectionTitle,
      paymentInsightsTitle,
      recentActivityTitle,
      trafficSectionTitle,
      settlement_amount,
      merchantBalanceConfigs,
      lateAuthConfig,
      hasMinTransactionSD,
      isValueFilled,
      roleToShowSupportDetailForm,
      canShowL1ActivationModals,
    };

    commonProps.showOnboardingBanner =
      user.isOrgAxis || user.isProductTourScreenHidden ? null : commonProps.showOnboardingBanner;

    const isPartnerOnBoardingModalShown = getItem(this.partnerOnBoardingToken);

    // if existing merchant or partner is coming via partner sign up page
    const isExistingMerchantPartnerComingFromPartnerSignUpPage = getItem('partner_intent');

    // We want to redirect the existing partner to dashboard if they are coming from
    // partner sign up page and partner_type is not null
    const shouldRedirectToPartnerDashboard = this.props.user?.isPartner();
    if (isExistingMerchantPartnerComingFromPartnerSignUpPage && shouldRedirectToPartnerDashboard) {
      removeItem('partner_intent');
      this.props.history.push('/partners');
    }

    // we want to by default show the explore partner program modal if partner_type is null
    if (!shouldRedirectToPartnerDashboard && isExistingMerchantPartnerComingFromPartnerSignUpPage) {
      removeItem('partner_intent');
      this.props.openModal({
        size: 'xlarge',
        disableClose: true,
        component: <PartnerOnbr disableClose={true} />,
        className: this.state.isMobile
          ? 'partner-onboarding-popup mobile-app-popup'
          : 'partner-onboarding-popup',
      });
    }

    const showPartnerExplore = location?.search === '?partnerExplore=true';

    if (
      (user.isPartnerIntent() &&
        !isPartnerOnBoardingModalShown &&
        !isExistingMerchantPartnerComingFromPartnerSignUpPage) ||
      showPartnerExplore
    ) {
      setItem(this.partnerOnBoardingToken, true);
      this.props.openModal({
        size: 'xlarge',
        disableClose: !showPartnerExplore,
        component: (
          <PartnerOnbr disableClose={!showPartnerExplore} closeModal={closePartnerExplore} />
        ),
        className: this.state.isMobile
          ? 'partner-onboarding-popup mobile-app-popup'
          : 'partner-onboarding-popup',
      });
    }

    const isShowBankAccountWokrflow =
      user.isAccountAndSettingsRevampEnabled &&
      user.isBankAccountUpdateRevampEnabled &&
      user.activation_status === 'activated' &&
      isBankAccountDetailsAllowed({ abExperiments, isConfigTagEnabled });
    const isRTUXHomepage = isRTUXHomepageEnabled({ user, abExperiments });
    const isFtuxV2Enabled = isEligibleForFtuxV2({ user, abExperiments });

    const hasLakhmiVilasBankAcc =
      user && user.bank_branch_ifsc && user.bank_branch_ifsc.substring(0, 4) === 'LAVB';

    const showRBIChangesBanners =
      user.isCardRecurringPaymentsBlocked &&
      user.isAccepted &&
      (user.isSubscriptionsEnabled || user.isChargeAtWillEnabled) &&
      !isConfigTagEnabled('product_recommendations_kyc.product_recommendation_kyc');
    return (
      <div className="react-root dashboard-home">
        <FestiveAnimation isMobile={isMobile} user={user.user} />
        <ShowWhen additionalCondition={() => !isConfigTagEnabled('onboarding.onboarding')}>
          {showRBIChangesBanners ? (
            <CardPaymentsBlockedBanner isCAW={user.isChargeAtWillEnabled} />
          ) : (
            <>
              {/* Lakshmi Vilas Bank Moratorium */}
              {user.isAccepted && hasLakhmiVilasBankAcc && <LakshmiVilasBankBanner />}
            </>
          )}
          {isShowBankAccountWokrflow && <WorkflowStatus isHomepageWorkflow />}
          {/* Show Diwali Promotional Banner */}

          {user.showInstantActivation &&
            !user.submitted &&
            showOnboardingBannerFirstStep &&
            !user.isPartnerIntent() && (
              <ModalMask>
                <Modal
                  className={`welcome-modal${this.isFestive ? ' festive' : ''}`}
                  onClose={() => {
                    trackIAClose();
                    this.closeOnboardingStep();
                    onFirstStepClose();
                    tracking.trackEvent(
                      window.rzpQ.onbr().success('login.first_login_modal', {
                        action: 'Close_Popup',
                      }),
                    );
                    tracking.trackEvent(
                      window.rzpQ.onbr().initiated('act.popup', {
                        clickSource: 'Close',
                      }),
                    );
                  }}
                >
                  <ModalContent>
                    <WelcomeModal
                      onClose={() => {
                        trackTryDashboard();
                        this.closeOnboardingStep();
                        onFirstStepClose();
                        trackEvents({
                          objectName: 'Pop Up',
                          actionName: 'Closed',
                          screen: 'home page',
                          properties: {
                            'Pop-up Label': 'Welcome to Razorpay',
                          },
                        });
                      }}
                      onActivate={() => {
                        trackActivateAccount();
                        onFirstStepClose();
                      }}
                      isFestive={this.isFestive}
                      isOnboardingV2Enabled={user.isOnboardingV2Enabled}
                      isOrgAxis={user.isOrgAxis}
                      isOrgRZP={user.isOrgRZP}
                      isProductRecommendationEnabled={user.isProductRecommendationEnabled}
                      hideCTAs={this.hideWelcomeModalCTAs}
                      referee={this.props.referee}
                      isActivationFormFullView={user.isActivationFormFullView}
                    />
                  </ModalContent>
                </Modal>
              </ModalMask>
            )}
          {user.isAccountAndSettingsRevampEnabled && isPaymentMethodEnabled(user, mode) && (
            <InternationalHPBanner />
          )}
          {showInstantActivationSuccess && (
            <InstantActivationSuccess
              onClose={() => {
                iaActivations.trackClose(activation_flow);
                tracking.trackEvent(window.rzpQ.onbr().dropped('act.whitelist_popup_action'));
                this.closeOnboardingStep();
                this.onInstantActivationSuccess();
              }}
              onGoToDashboard={() => {
                tracking.trackEvent(
                  window.rzpQ.onbr().initiated('act.whitelist_popup_action', {
                    actions: 'Go to Dashboard',
                  }),
                );
                iaActivations.trackGoToDashboard();
                this.closeOnboardingStep();
                this.onInstantActivationSuccess();
              }}
              onCompleteKYC={() => {
                tracking.trackEvent(
                  window.rzpQ.onbr().initiated('kyc.form_fill', {
                    clickSource: 'Complete_kyc',
                  }),
                );
                this.onInstantActivationSuccess(
                  `/app/activation?basePath=${encodeURIComponent('/dashboard')}`,
                );
              }}
              user={user}
            />
          )}
          {showKYCStatus && this.props.user.isInstantActivationEnabled && (
            <KYCStatusModal
              onClose={() => {
                iaActivations.trackClose(activation_flow);
                this.props.hideKYCStatusModal();
                if (isMobile && user.isOnboardingV2Enabled) {
                  window.location.reload();
                }
              }}
              onGoToDashboard={() => {
                iaActivations.trackClose(activation_flow);
                this.onInstantActivationSuccess();
              }}
              user={user}
              modalType={kycStatusModalType}
              activationDuration={kycStatusActivationDuration}
            />
          )}
          {showKYCStatus &&
            !!this.props.user.submitted &&
            !this.props.user.isInstantActivationEnabled && (
              <KYCStatusModalOld
                onClose={() => {
                  iaActivations.trackClose(activation_flow);
                  this.props.hideKYCStatusModal();
                  if (isMobile && user.isOnboardingV2Enabled) {
                    window.location.reload();
                  }
                }}
                onGoToDashboard={() => {
                  iaActivations.trackClose(activation_flow);
                  this.onInstantActivationSuccess();
                }}
                user={user}
                modalType={kycStatusModalType}
                activationDuration={kycStatusActivationDuration}
              />
            )}
          {showPANStatus && (
            <PANVerificationStatusModal
              onClose={() => {
                this.props.hidePANStatusModal();
              }}
              onGoToDashboard={() => {
                this.onInstantActivationSuccess();
              }}
              onCompleteKYC={() => {
                tracking.trackEvent(
                  window.rzpQ.onbr().initiated('kyc.form_fill', {
                    clickSource: 'Complete_kyc',
                  }),
                );
                this.onInstantActivationSuccess(
                  `/app/activation?basePath=${encodeURIComponent('/dashboard')}`,
                );
              }}
              user={user}
            />
          )}
          {showKYCDetails && (
            <KycDetailsModal
              onClose={() => {
                iaActivations.trackCloseKYCDetails();
                tracking.trackEvent(window.rzpQ.onbr().dropped('act.greylist_popup_action'));
                hideKYCDetailsModal();
              }}
              onGiveDetails={() => {
                iaActivations.trackGiveKYCDetails();
                tracking.trackEvent(
                  window.rzpQ.onbr().initiated('act.greylist_popup_action', {
                    actions: 'Give Details',
                  }),
                );
                tracking.trackEvent(
                  window.rzpQ.onbr().initiated('kyc.form_fill', {
                    actions: 'GreyList Popup',
                  }),
                );
                hideKYCDetailsModal();
              }}
            />
          )}

          {showInstantActivationFraudModal && (
            <FraudDetectionModal onClose={() => this.props.hideFraudDetectionModal()} />
          )}
          {showTnCModal && (
            <TnCModal
              onClose={this.props.hideTnC}
              tracking={tracking}
              closeModal={this.props.closeModal}
              openModal={this.props.openModal}
            />
          )}

          {this.state.referredAmount > 0 && (
            <M2MSuccessModal
              referredMerchants={this.state.referredMerchants}
              referredAmount={this.state.referredAmount}
              isReferee={this.state.isReferee}
            />
          )}
        </ShowWhen>
        {isFtuxV2Enabled ? (
          <SuspenseWithLoader type={isMobile ? 'full' : 'centerToMainContent'}>
            <FTUXHomepage />
          </SuspenseWithLoader>
        ) : isRTUXHomepage ? (
          <SuspenseWithLoader type={isMobile ? 'full' : 'centerToMainContent'}>
            <RTUXHomepage />
          </SuspenseWithLoader>
        ) : isMobile ? (
          <SuspenseWithLoader type="full">
            <Mobile {...commonProps} />
          </SuspenseWithLoader>
        ) : (
          <SuspenseWithLoader type="centerToMainContent">
            <Desktop {...commonProps} />
          </SuspenseWithLoader>
        )}

        {showRBIChangesBanners && <CardPaymentsBlockedModal />}
      </div>
    );
  }
}

export default compose(
  connect(
    (state) => {
      return {
        user: state.session.user,
        mode: state.session.mode,
        current_balance: state.home.current_balance,
        ondemand_restrictions: state.home.ondemand_restrictions,
        merchantBalanceConfigs: state.home.merchantBalanceConfigs,
        showInstantActivationSuccess: state.home.instantActivations.showInstantActivationSuccess,
        showKYCDetails: state.home.instantActivations.showKYCDetails,
        showPANStatus: state.home.instantActivations.showPANStatus,
        showKYCStatus: state.home.instantActivations.showKYCStatus,
        showInstantActivationFraudModal:
          state.home.instantActivations.showInstantActivationFraudModal,
        kycStatusModalType: state.home.kycStatusModalType,
        kycStatusActivationDuration: state.home.kycStatusActivationDuration,
        settlement_amount: state.home.settlement_amount,
        virtualAccounts: state.virtualaccounts,
        lateAuthConfig: state.config.lateAuthConfig,
        support_detail: state.supportdetails.merchantSupportDetail,
        showTnCModal: state.home.showTnCModal,
        referee: state.merchantReferral.data.referee,
        websiteSectionDetailsData: state.websiteCompliance.websiteSectionDetailsData,
      };
    },
    {
      ...HomeActions,
      ...ModalActions,
      showNotification,
      fetchPayments,
      fetchVirtualAccounts,
      fetchLateAuthConfig,
      fetchSupportDetail,
      showOrHideHighlightMode,
      updateSession,
      ...EventActions,
      fetchAmount,
      fetchMerchantWebsiteDetails,
      getBannerAndModalVisibility,
      fetchActivationDetails,
    },
  ),
  rTracking(() => window.rzpQ.component('HomeContainer')),
  withRouter,
  withSplitzService,
  withI18Service,
)(HomeContainer);
