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
import LocalStorageService from 'common/utils/localStorage';
import { getCookie } from '../../../common/utils/cookies';
import debounce from 'common/utils/debounce';
import { getFormattedAmountNew } from 'common/utils/rzp-utils';
import * as ModalActions from 'merchant_common/reducers/modals';
import { ModalMask, Modal, ModalContent } from 'common/new-ui/Modal';
import { activationDuration } from 'merchant/helpers/data';
import rolesList from 'merchant/helpers/permissions/roles-list';

import * as HomeActions from 'merchant/reducers/home';
import { fetch } from 'merchant/reducers/pokedex';
import { fetchPayments } from 'merchant/reducers/collection';
import { fetchOndemandRestrictions } from 'merchant/reducers/home';
import { fetchLateAuthConfig } from 'merchant/reducers/config';

import { API_ERROR, API_INVALID_RESP, isMobileDevice } from 'merchant/components/Home/data';
import WelcomeModal from 'merchant/components/Home/WelcomeModal';
import LakshmiVilasBankBanner from 'merchant/components/Announcements/LakshmiVilasBankBanner';

import InstantActivationSuccess from 'merchant/components/Home/InstantActivationSuccess';
import PANVerificationStatusModal from 'merchant/components/Home/PANVerificationStatusModal';
import KYCStatusModal from 'merchant/components/Home/KYCStatusModal';
import KycDetailsModal from 'merchant/components/Home/KycDetailsModal';
import FraudDetectionModal from 'merchant/components/Home/FraudDetectionModal';
import { showWhenUtil } from 'merchant/components/ShowWhen';
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
  trackSupportDetailPopupDisplay,
} from './ga';

import Banner from 'common/ui/Banner';
import Desktop from './Desktop';
import Mobile from './Mobile';
import ShowWhen from 'merchant/components/ShowWhen';
import RTracking from 'react-tracking';
import { fetchVirtualAccounts } from 'merchant/reducers/virtualaccounts';
import MerchantDataCollectionModal from 'merchant/views/Settings/SupportDetails/MerchantDataCollectionModal';
import CardPaymentsBlockedModal from 'merchant/views/Subscriptions/components/CardPaymentsBlocked/Modal';
import CardPaymentsBlockedBanner from 'merchant/views/Subscriptions/components/CardPaymentsBlocked/Banner';

import { fetchSupportDetail } from 'merchant/reducers/support_detail';

const dateRangePresets = [
    ['Past 7 Days', -7, 'days'],
    ['Past 30 Days', -30, 'days'],
    ['Past 90 Days', -90, 'days'],
    ['All Time', -10, 'years'],
  ],
  defaultPreset = 1;

const getPreviousDates = ({ startDate, endDate }) => {
  const diff = endDate.diff(startDate);

  return {
    startDate: startDate.clone().subtract(diff, 'ms'),
    endDate: endDate.clone().subtract(1, 'day').subtract(diff, 'ms').endOf('day'),
  };
};

const bodyClass = ' analytics-v2-active';

// used to show titles for sections and also GA
const keymetricsSectionTitle = 'Transactions Overview',
  paymentInsightsTitle = 'Payment Insights',
  trafficSectionTitle = 'Traffic split on platforms',
  recentActivityTitle = 'Recent Activity';

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

    let endDate = moment().endOf('day'),
      startDate = endDate.clone().startOf('day');

    startDate.add(...dateRangePresets[defaultPreset].slice(1));

    const { user, mode, isAdmin } = props,
      // onboarding card is shown if this is present in localstorage
      onboardingCardToken = 'show_onboarding_card',
      // onboarding card first step is shown if this is present in localstorage
      firstStepToken = 'onboarding_first_step',
      // partner onboarding is shown if user logs in for 1st time
      partnerOnBoarding = 'partner_on_boarding_shown';

    // tokens particular for the current merchant
    this.onboardingBannerToken = `${onboardingCardToken}--${user.current}`;
    this.firstStepToken = `${firstStepToken}--${user.current}`;
    this.partnerOnBoardingToken = `${partnerOnBoarding}--${user.current}`;

    this.couponCode = 'UNLOCKFEST';
    this.isFestive = getCookie(`coupon_code--${user.current}`) === this.couponCode;

    /*
     * Earlier , the tokens apply at browser level, if old tokens are present
     * converting them specific to the merchants the current user can switch to
     */
    if (LocalStorageService.getItem(onboardingCardToken)) {
      Object.keys(user.merchants).forEach((key) => {
        LocalStorageService.setItem(`${onboardingCardToken}--${key}`, 'true');
      });

      LocalStorageService.removeItem(onboardingCardToken);
    }

    if (LocalStorageService.getItem(firstStepToken)) {
      Object.keys(user.merchants).forEach((key) => {
        LocalStorageService.setItem(`${firstStepToken}--${key}`, 'true');
      });

      LocalStorageService.removeItem(firstStepToken);
    }

    const hasAccessToOnboardingBanner = (this.hasAccessToOnboardingBanner =
      [rolesList.MANAGER, rolesList.OWNER, rolesList.ADMIN].indexOf(user.role) >= 0);

    const showOnboardingBanner =
        hasAccessToOnboardingBanner && LocalStorageService.getItem(this.onboardingBannerToken),
      showOnboardingBannerFirstStep = LocalStorageService.getItem(this.firstStepToken);

    const roleToShowSupportDetailForm =
      [
        rolesList.MANAGER,
        rolesList.OPERATIONS,
        rolesList.ADMIN,
        rolesList.SUPPORT,
        rolesList.OWNER,
      ].indexOf(user.role) >= 0;
    const SUPPORT_DETAIL_LAST_POP_DISPLAY_DATE = LocalStorageService.getItem(
      `SUPPORT_DETAIL_LAST_POP_DISPLAY_DATE--${user.current}`,
    );
    if (mode === 'live' && !SUPPORT_DETAIL_LAST_POP_DISPLAY_DATE && roleToShowSupportDetailForm) {
      LocalStorageService.setItem(
        `SUPPORT_DETAIL_LAST_POP_DISPLAY_DATE--${user.current}`,
        moment().subtract(1, 'days').format('DD/MM/YYYY'),
      );
    }

    if (
      mode === 'live' &&
      !LocalStorageService.getItem(`SUPPORT_DETAIL_CURRENT_POPUP_COUNT--${user.current}`) &&
      roleToShowSupportDetailForm
    ) {
      LocalStorageService.setItem(`SUPPORT_DETAIL_CURRENT_POPUP_COUNT--${user.current}`, 10);
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
      dateRangePresets,
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
      hideDiwaliPromotion: LocalStorageService.getItem('hide_diwali_promotional_banner') || false,
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
          showOnboardingBannerFirstStep: user.showInstantActivation ? !user.submitted : true,
          expandOnboardingBanner: true,
        };

        LocalStorageService.setItem(this.onboardingBannerToken, 'true');

        if (this.state.showOnboardingBannerFirstStep) {
          LocalStorageService.setItem(this.firstStepToken, 'true');
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

  get settlementRestricted() {
    return this.props.user.isFeatureEnabled('es_on_demand_restricted');
  }

  get settleNowRestrictionMsg() {
    return 'Ondemand Settlements feature is temporarily disabled';

    if (!this.settlementRestricted) return;
    const {
      attempts_left,
      settlable_amount,
      max_amount_limit,
      settlements_count_limit,
    } = this.props.ondemand_restrictions.data;

    if (!attempts_left && !settlable_amount) {
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
    } else return;
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

    const { startDate, endDate } = this.state,
      { isAdmin, analyticsFetch } = this.props;

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
      })
      .then((data) => {
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
            return;
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
            return;
          }

          const ratio = (totalByPlatform[OTHERS] || 0) / grandTotal;

          // if `Others` platform count is greater than 30%
          // do not show grouping
          if (ratio > 0.3) {
            trackPlatformAnalyticsHidden(ratio * 100);
            return;
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

    var oldestTxnReqId = ++this.oldestTxnReqId;

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

        const records = data.data.records.result[0],
          value = records && records.created_at;

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

        const presetsLastIndex = dateRangePresets.length - 1,
          presetsLastItem = dateRangePresets[presetsLastIndex];

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
        window.hj && window.hj('trigger', 'MOBILE_SURVEY');
      }, 0);
    }
  }

  closeOnboardingStep() {
    this.props.closeOnboardingStep();
  }

  onFirstStepClose() {
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

    LocalStorageService.removeItem(this.firstStepToken);
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

    LocalStorageService.removeItem(this.onboardingBannerToken);
  }

  setShowOnboardingBanner() {
    const { user } = this.props,
      showOnboardingBannerFirstStep = user.showInstantActivation
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
      LocalStorageService.setItem(this.onboardingBannerToken, 'true');
    }

    LocalStorageService.setItem(this.firstStepToken, 'true');
  }

  onFetchPayments(data) {
    const { user, mode } = this.props;

    const items = (data && data.items) || [];

    const { showOnboardingBanner } = this.state;

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
    LocalStorageService.setItem('hide_diwali_promotional_banner', true);
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

  openSupportDetailModal = (isHomePage) => {
    const { tracking, user } = this.props;
    if (isHomePage) {
      LocalStorageService.setItem(
        `SUPPORT_DETAIL_LAST_POP_DISPLAY_DATE--${user.current}`,
        moment().format('DD/MM/YYYY'),
      );
      let remainigCount = LocalStorageService.getItem(
        `SUPPORT_DETAIL_CURRENT_POPUP_COUNT--${user.current}`,
      );
      if (remainigCount > 0) {
        LocalStorageService.setItem(
          `SUPPORT_DETAIL_CURRENT_POPUP_COUNT--${user.current}`,
          remainigCount - 1,
        );
      }
    }
    setTimeout(() => {
      return this.props.openModal({
        size: 'small',
        component: (
          <MerchantDataCollectionModal closeModal={this.props.closeModal} supportModal={true} />
        ),
      });
    }, 500);
    trackSupportDetailPopupDisplay();
    tracking.trackEvent(
      window.rzpQ.onbr().success('support_details.popup_displayed', {
        action: 'display_support_detail_popup',
      }),
    );
    tracking.trackEvent(
      window.rzpQ.onbr().initiated('action_popup', {
        clickSource: 'Display popup',
      }),
    );
  };

  render() {
    let {
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
      openSupportDetailModal,
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
    const maxPopupCountOfSupportDetail = LocalStorageService.getItem(
      `SUPPORT_DETAIL_CURRENT_POPUP_COUNT--${user.current}`,
    );
    const isValueFilled =
      support_detail.error && support_detail.error[0] === 'Merchant email type does not Exist'
        ? false
        : true;

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
      openSupportDetailModal,
    };

    const { dismissDiwaliPromotion, hideDiwaliPromotion } = this.state;

    const isPartnerOnBoardingModalShown = LocalStorageService.getItem(this.partnerOnBoardingToken);

    if (user.isPartnerIntent() && !isPartnerOnBoardingModalShown) {
      LocalStorageService.setItem(this.partnerOnBoardingToken, true);
      this.props.openModal({
        size: 'xlarge',
        disableClose: true,
        component: <PartnerOnbr disableClose={true} />,
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
                        additionalCondition={(user) =>
                          user.isOrgAllowedFunctionality('external_links')
                        }
                      >
                        <a href="https://razorpay.com/pricing" target="_blank">
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
                  />
                </ModalContent>
              </Modal>
            </ModalMask>
          )}

        {/* merchant support details data collection modal */}
        {this.compareDate(
          moment().format('DD/MM/YYYY'),
          LocalStorageService.getItem(`SUPPORT_DETAIL_LAST_POP_DISPLAY_DATE--${user.current}`),
        ) &&
        hasMinTransactionSD &&
        roleToShowSupportDetailForm &&
        !isValueFilled &&
        maxPopupCountOfSupportDetail > 0 &&
        !showOnboardingBannerFirstStep
          ? openSupportDetailModal(true)
          : null}

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
        {showKYCStatus && (
          <KYCStatusModal
            onClose={() => {
              iaActivations.trackClose(activation_flow);
              this.props.hideKYCStatusModal();
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
        {isMobile ? <Mobile {...commonProps} /> : <Desktop {...commonProps} />}

        {showRBIChangesBanners && <CardPaymentsBlockedModal />}
      </div>
    );
  }
}
