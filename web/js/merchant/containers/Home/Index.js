/* eslint-disable */
import React, { Component } from 'react';
import { connect } from 'react-redux';
import moment from 'moment';
import { showNotification } from 'merchant_common/reducers/notifications';
import { customRangeText } from 'common/ui/DateRangePicker';
import {
  oldestTransactionQuery,
  getDefaultPaymentFilter,
  platformGroupingVals,
  groupByPlatform,
  OTHERS,
} from 'common/utils/pokedex';
import { getItem, setItem, removeItem } from 'common/utils/localStorage';
import { getCookie } from '../../../common/utils/cookies';
import debounce from 'common/utils/debounce';
import { getFormattedAmountNew, getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import * as ModalActions from 'merchant_common/reducers/modals';
import { ModalMask, Modal, ModalContent } from 'common/new-ui/Modal';
import rolesList from 'merchant/helpers/permissions/roles-list';
import * as HomeActions from 'merchant/reducers/home';
import { fetch } from 'merchant/reducers/pokedex';
import { fetchPayments } from 'merchant/reducers/collection';
import { fetchLateAuthConfig } from 'merchant/reducers/config';
import { API_ERROR, API_INVALID_RESP, isMobileDevice } from 'merchant/components/Home/data';
import WelcomeModal from 'merchant/components/Home/WelcomeModal';
import LakshmiVilasBankBanner from 'merchant/components/Announcements/LakshmiVilasBankBanner';
import InstantActivationSuccess from 'merchant/components/Home/InstantActivationSuccess';
import PANVerificationStatusModal from 'merchant/components/Home/PANVerificationStatusModal';
import KYCStatusModal from 'merchant/components/Home/KYCStatusModal';
import KYCStatusModalOld from 'merchant/components/Home/KYCStatusModal-old';
import KycDetailsModal from 'merchant/components/Home/KycDetailsModal';
import FraudDetectionModal from 'merchant/components/Home/FraudDetectionModal';
import ShowWhen, { showWhenUtil } from 'merchant/components/ShowWhen';
import { switchToMode } from 'merchant/containers/Home/OnboardingCard/SwitchToMode';
import PartnerOnbr from 'merchant/views/PartnerDashboard/Onboarding/partnerOnbr';
import {
  trackError,
  trackDatesChange,
  trackPlatformAnalyticsHidden,
  trackActivateAccount,
  trackTryDashboard,
  trackIAClose,
  iaActivations,
} from './ga';
import Banner from 'common/ui/Banner';
import Desktop from './Desktop';
import Mobile from './Mobile';
import RTracking from 'react-tracking';
import { fetchVirtualAccounts } from 'merchant/reducers/virtualaccounts';
import CardPaymentsBlockedModal from 'merchant/views/Subscriptions/components/CardPaymentsBlocked/Modal';
import CardPaymentsBlockedBanner from 'merchant/views/Subscriptions/components/CardPaymentsBlocked/Banner';
import TnCModal from 'merchant/components/Home/TnCModal';
import { fetchSupportDetail } from 'merchant/reducers/support_detail';
import { showOrHideHighlightMode, updateSession } from 'merchant/reducers/session';
import { merchantFetch } from 'merchant/utils/ajax';
import User from 'merchant/models/User';
import { analyticsTrack } from 'common/utils/analytics';

const DATE_RANGE_PRESETS = [
  ['Past 7 Days', -7, 'days'],
  ['Past 30 Days', -30, 'days'],
  ['Past 90 Days', -90, 'days'],
  ['All Time', -10, 'years'],
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

const { fetchOndemandRestrictions, hideTnC } = HomeActions;
@connect(
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
    fetchOndemandRestrictions,
    hideTnC,
    showOrHideHighlightMode,
    updateSession,
  },
)
@RTracking(() => window.rzpQ.component('HomeContainer'))
export default class HomeContainer extends Component {
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
      dismissDiwaliPromotion: false,
      hideDiwaliPromotion: getItem('hide_diwali_promotional_banner') || false,
      hasMinTransactionSD: false,
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
    if (hasAccessToOnboardingBanner && !showOnboardingBanner) {
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
    this.onHideDiwaliPromotion = this.onHideDiwaliPromotion.bind(this);
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

  get settleNowRestrictionMsg() {
    return 'Due to a technical issue, On-demand Settlements is not available. Please check back in some time.';
    /* if (!this.settlementRestricted) return false;
    const {
      attempts_left,
      settlable_amount,
      max_amount_limit,
      settlements_count_limit,
    } = this.props.ondemand_restrictions.data;

    if (this.isOnDemandDisabled) {
      const restrictedItem = this.restrictedFeatures
        .filter((feat) => this.props.user.isFeatureEnabled(feat))
        .map((feat) => this.featureName[feat]);

      const renderFeatureComponent = () => {
        return restrictedItem.map((item, i) => {
          if (i === restrictedItem.length - 1 && i != 0) {
            return (
              <>
                & <span className="highlight-tooltip"> {item}.</span>
              </>
            );
          } else {
            return (
              <span className="highlight-tooltip">
                {item}
                {i === restrictedItem.length - 1
                  ? '.'
                  : i === restrictedItem.length - 2
                  ? ' '
                  : ', '}
              </span>
            );
          }
        });
      };
      return (
        <div className="disable-ondemand-msg">
          On-demand Instant Settlements have been disabled because you have delayed the repayments
          on {renderFeatureComponent()}
          <br /> <br />
          Please complete the repayments to re-enable Instant Settlements.
        </div>
      );
    } else if (!attempts_left && !settlable_amount) {
      return `You’ve already settled your maximum allowed limit of ${getFormattedAmountNew(
        max_amount_limit,
        true,
      )} for the day.`;
    } else if (!attempts_left) {
      return `You've already settled your maximum allowed limit of ${settlements_count_limit} times for the day.`;
    } else if (!settlable_amount) {
      return `You’ve already settled your maximum allowed limit of ${getFormattedAmountNew(
        max_amount_limit,
        true,
      )} for the day.`;
      /* eslint-disable 
    } else return;
    /* eslint-enable */
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
        console.error(err);

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
      });
  }

  fetchOldestTransactionDate() {
    let { oldestTransactionDate, dateRangePresets } = this.state;

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
          return null;
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
        console.error(err);

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

        const presetsLastIndex = dateRangePresets.length - 1;
        const presetsLastItem = dateRangePresets[presetsLastIndex];

        // updates All Time present in daterange picker
        dateRangePresets = [...dateRangePresets];

        dateRangePresets.splice(presetsLastIndex, 1, [
          presetsLastItem[0],
          -(moment().unix() - data.value),
          'seconds',
        ]);

        this.setState({
          oldestTransactionDate: {
            ...oldestTransactionDate,
            value: data.value,
          },
          dateRangePresets,
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
  }

  componentWillMount() {
    // to style react-power-selct specific to this tab
    document.body.className += bodyClass;
    const { user } = this.props;

    this.props.fetchCurrentBalance();
    this.fetchOldestTransactionDate();
    this.fetchTxnsGroupedByPlatform();

    if (user && user.isVirtualAccountsEnabled) {
      this.props.fetchVirtualAccounts({
        skip: 0,
        count: 25,
      });
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

  fetchMerchantDetails = async () => {
    const response = await merchantFetch({
      url: 'merchant/activation',
      mode: 'live',
    });
    return response;
  };

  triggerTimerToUpdateMode = () => {
    /* eslint-disable */
    const { user, mode, updateSession } = this.props;
    /* eslint-enable */

    if (
      user.isAutoRefreshExperimentEnabled &&
      mode === 'test' &&
      user.isUnregisteredBusiness &&
      user.instantActivation.isL1Submitted &&
      user.poi_verification_status === 'initiated' &&
      !user.activation_status
    ) {
      setTimeout(() => {
        this.fetchMerchantDetails().then((res) => {
          if (
            res?.data &&
            res?.data?.poi_verification_status === 'verified' &&
            res?.data?.activation_status === 'instantly_activated'
          ) {
            const {
              activation_progress,
              activated,
              activation_status,
              activation_form_milestone,
              poi_verification_status,
              business_type,
            } = res.data;

            const userData = new User({
              ...user,
              activation_progress,
              activated,
              activation_status,
              activation_form_milestone,
              poi_verification_status,
              business_type,
            });

            setItem(`rzp_mode--${user.current}`, 'live');
            setItem(`is_activated--${user.current}`, 'true');
            updateSession({ user: userData, mode: 'live' });
            this.props.showNotification({
              type: 'success',
              message: 'Congratulations, you are now in live mode, start accepting payments now!',
              hidePrevious: true,
            });
            analyticsTrack({
              objectName: 'IA Page',
              actionName: 'refreshed',
              screen: 'home page',
              properties: {
                ...getCommonAnalyticsProperties(user),
              },
            });
          }
        });
      }, 30000);
    }
  };

  componentDidMount() {
    this.props.fetchSettlementAmount();
    this.fetchRestrictionsIfAny();
    this.props.fetchBalanceConfig();
    this.props.fetchLateAuthConfig();
    this.setScrollAmountToStickHeader();

    this.props.fetchSupportDetail();

    window.addEventListener('resize', this.onResize);

    const shouldShowMobileHotjarSurvey = showWhenUtil({
      additionalCondition: (user) => user.isMobileHotjarSurveyEnabled,
    });

    if (shouldShowMobileHotjarSurvey) {
      setTimeout(() => {
        /* eslint-disable */
        window.hj && window.hj('trigger', 'MOBILE_SURVEY');
        /* eslint-enable */
      }, 0);
    }
    this.triggerTimerToUpdateMode();

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

  onHideDiwaliPromotion() {
    setItem('hide_diwali_promotional_banner', true);
    this.setState(
      {
        dismissDiwaliPromotion: true,
      },
      () => {
        window.setTimeout(() => {
          this.setState({
            hideDiwaliPromotion: true,
          });
        }, 500);
      },
    );
  }

  compareDate = (curr, prev) => {
    const currDate = moment(curr, 'DD/MM/YYYY');
    const prevDate = moment(prev, 'DD/MM/YYYY');
    if (currDate > prevDate) return true;
    else return false;
  };

  render() {
    const {
      mode,
      current_balance,
      tabsMeta,
      user,
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
    } = this.props;

    const { activation_flow } = user;

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
    } = this.state;

    const {
      onHideOnboardingBanner,
      onFirstStepClose,
      onDatesChange,
      onFetchPayments,
      onExtraContentMount,
      setScrollAmountToStickHeader,
      settleNowRestrictionMsg,
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
      settleNowRestrictionMsg,
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
      isOnDemandDisabled: this.isOnDemandDisabled,
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
    };

    const { dismissDiwaliPromotion, hideDiwaliPromotion } = this.state;

    const isPartnerOnBoardingModalShown = getItem(this.partnerOnBoardingToken);

    if (user.isPartnerIntent() && !isPartnerOnBoardingModalShown) {
      setItem(this.partnerOnBoardingToken, true);
      this.props.openModal({
        size: 'xlarge',
        disableClose: true,
        component: <PartnerOnbr disableClose={true} />,
        className: this.state.isMobile
          ? 'partner-onboarding-popup mobile-app-popup'
          : 'partner-onboarding-popup',
      });
    }

    const hasLakhmiVilasBankAcc =
      user && user.bank_branch_ifsc && user.bank_branch_ifsc.substring(0, 4) === 'LAVB';

    const showRBIChangesBanners =
      user.isCardRecurringPaymentsBlocked &&
      user.isAccepted &&
      (user.isSubscriptionsEnabled || user.isChargeAtWillEnabled);
    return (
      <div class="react-root dashboard-home">
        {showRBIChangesBanners ? (
          <CardPaymentsBlockedBanner isCAW={user.isChargeAtWillEnabled} />
        ) : (
          <>
            {/* Lakshmi Vilas Bank Moratorium */}
            {user.isAccepted && hasLakhmiVilasBankAcc && <LakshmiVilasBankBanner />}

            {this.props.user.isDiwaliPromoEnabled && !hideDiwaliPromotion && (
              <div
                className={`diwali-promotion-banner v2-tour-banner${
                  dismissDiwaliPromotion ? ' dismiss' : ''
                }`}
              >
                <div className="banner-content">
                  <Banner cta="View T&Cs">
                    <span class="badge m-r">SPECIAL OFFER</span>
                    <span>
                      {this.props.user.transaction_value
                        ? 'You are currently active at a slashed pricing of 1.75%! Make the most of it, benefits last till 31st January, 2019'
                        : 'Start transacting with us and enjoy our slashed pricing - 1.75%. Valid on payments till 31st January, 2019'}
                    </span>
                    <span class="m-l btn-link">
                      <ShowWhen
                        additionalCondition={() => user.isOrgAllowedFunctionality('external_links')}
                      >
                        <a href="https://razorpay.com/pricing" target="_blank" rel="noreferrer">
                          <b>View T&Cs</b>
                        </a>
                      </ShowWhen>
                    </span>
                  </Banner>
                </div>
                <div className="banner-close">
                  <a className="banner-close-icon" onClick={this.onHideDiwaliPromotion}>
                    <i className="i i-close" />
                  </a>
                </div>
              </div>
            )}
          </>
        )}

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
                    }}
                    onActivate={() => {
                      trackActivateAccount();
                      onFirstStepClose();
                    }}
                    isFestive={this.isFestive}
                    isOnboardingV2Enabled={user.isOnboardingV2Enabled}
                    isProductRecommendationEnabled={user.isProductRecommendationEnabled}
                    hideCTAs={this.hideWelcomeModalCTAs}
                  />
                </ModalContent>
              </Modal>
            </ModalMask>
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
        {showKYCStatus && !this.props.user.isInstantActivationEnabled && (
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

        {isMobile ? <Mobile {...commonProps} /> : <Desktop {...commonProps} />}

        {showRBIChangesBanners && <CardPaymentsBlockedModal />}
      </div>
    );
  }
}
