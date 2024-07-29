import React, { Component, Suspense } from 'react';
import moment from 'moment';
import AsyncButton from 'react-async-button';
import LazyLoad from 'react-lazyload';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';

import Carousel from 'common/components/Carousel';
import { withRouter } from 'common/deprecated/withRouter';
import { withI18Service } from 'common/i18';
import { withSplitzService } from 'common/splitz';
import Amount from 'common/ui/Amount';
import CovidKnowMore from 'common/ui/CovidKnowMore';
import DashboardBanner from 'common/ui/DashboardBanner';
import DateRangePicker from 'common/ui/DateRangePicker';
import Group, { GroupItem } from 'common/ui/Group';
import GrowthAssetEB from 'common/ui/GrowthAssetEB';
import Header from 'common/ui/Header';
import NeoStoneTracker from 'common/ui/NotificationsDropdown/Neostone/Tracker';
import { getXCAStatus } from 'common/ui/NotificationsDropdown/Neostone/common/utils';
import Popover, { PopoverBody } from 'common/ui/Popover';
import PricingSubscriptionWrapper from 'common/ui/PricingSubscription';
import Time from 'common/ui/Time';
import { analyticsTrack } from 'common/utils/analytics';
import * as LocalStorageService from 'common/utils/localStorage';
import {
  checkHTML5APIvalidity,
  getCommonAnalyticsProperties,
  getFormattedAmountNew,
  isExperimentActive,
} from 'common/utils/rzp-utils';
import {
  getActivationState,
  isNewNcActivationStatus,
} from 'merchant/components/Activation/ActivationUtils';
import NCModal from 'merchant/components/Activation/NCModal';
import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';
import CapitalAnnouncement from 'merchant/components/Announcements/Capital';
import CatalystCampaignBannerPhase2 from 'merchant/components/Announcements/CatalystCampaignBannerPhase2';
import CongratulatoryBanner from 'merchant/components/Announcements/CongratulatoryBanner';
import CovidCampaignAnnouncement from 'merchant/components/Announcements/CovidCampaign';
import Announcement from 'merchant/components/Announcements/Instant';
import InternationalFormStatusAnnouncement from 'merchant/components/Announcements/InternationalFormStatus';
import InternationalRequestStatusAnnouncement from 'merchant/components/Announcements/InternationalRequestStatus';
import IntlPaymentsAnnouncement from 'merchant/components/Announcements/IntlPaymentsAnnouncement';
import NPSAnnouncement from 'merchant/components/Announcements/NPSAnnouncement';
import RepaymentAnnouncment from 'merchant/components/Announcements/PaymentRecovery';
import PersonaliseBanner from 'merchant/components/Announcements/PersonaliseAccount';
import SupportRequest from 'merchant/components/Announcements/SupportRequest';
import WebsiteComplianceBanner from 'merchant/components/Announcements/WebsiteCompliance';
import EasterEgg from 'merchant/components/EasterEgg';
import DedupeModal from 'merchant/components/Home/DedupeModal';
import { isMobileDevice } from 'merchant/components/Home/data';
import M2MBanner from 'merchant/components/M2M/M2MBanner';
import ShowWhen from 'merchant/components/ShowWhen';
import CreditPullModal from 'merchant/containers/CreditPullModal';
import KeyMetrics from 'merchant/containers/Home/KeyMetrics';
import NewUserOnboardingCard from 'merchant/containers/Home/OnboardingCard';
import { trackPersonaliseBanner } from 'merchant/containers/Home/OnboardingCard/Instant/ga';
import PaymentMethods from 'merchant/containers/Home/PaymentMethods';
import ProductOnboardingCard from 'merchant/containers/Home/ProductOnboardingCard';
import ProductRecommendationnCard from 'merchant/containers/Home/ProductRecommendationnCard';
import IntlPaymentsRecommendation from 'merchant/containers/Home/ProductRecommendationnCard/IntlPaymentsRecommendation';
import RecentActivity from 'merchant/containers/Home/RecentActivity';
import Traffic from 'merchant/containers/Home/Traffic';
import {
  fetchInternationalProductsStatus as fnFetchInternationalProductsStatus,
  fetchInternationalSettingStatus as fnFetchInternationalSettingStatus,
} from 'merchant/reducers/config';
import { fetchCarouselBanner as fetchCarouselBannerProp } from 'merchant/reducers/growthService';
import {
  fetchEscalations as fnFetchEscalations,
  showKYCStatusModal,
  showProductsModal,
} from 'merchant/reducers/home';
import { fetchBankAccountChangeStatus as fnFetchBankAccountChangeStatus } from 'merchant/reducers/profile';
import { fetchUser } from 'merchant/reducers/session';
import { fetchSettlementConfig as fnFetchSettlementConfig } from 'merchant/reducers/settlements/details';
import * as EventActions from 'merchant/reducers/trackEvents';
import lazy from 'merchant/routes/LazyLoader';
import { merchantFetch } from 'merchant/utils/ajax';
import { checkEligibilityForFeeBasedGating } from 'merchant/utils/feeBasedGatingUtils';
import { getNCUrlOnEasyOrPhantom } from 'merchant/utils/urls';
import WebsiteCompliancePrompt from 'merchant/views/Account/WebsiteAppDetails/Prompt.desktop';
import {
  isPolicyWizardV2Enabled,
  shouldShowWebsiteComplianceModal,
} from 'merchant/views/Account/WebsiteAppDetails/utils';
import CashAdvanceNudge from 'merchant/views/Capital/CashAdvanceNudges';
import { getSettlementStatus } from 'merchant/views/Capital/utils';
import OndemandModal from 'merchant/views/Settlements/Settlements/components/Modals/OndemandModal';
import SettleNowButton from 'merchant/views/Settlements/Settlements/components/SettleNowButton';
import SettlementDetail from 'merchant/views/Settlements/Settlements/components/SettlementDetail';
import { STATUSES } from 'merchant/views/TicketSupport/utils';
import { openModal as fnOpenModal } from 'merchant_common/reducers/modals';
import * as NotificationActions from 'merchant_common/reducers/notifications';

import DateRangeTooltip from './DateRangeTooltip';
import {
  EVENT_CATEGORY_DASHBOARD_HOME,
  selfServeSettleTracking,
  trackPresetChange,
  trackSettleNow,
  trackSettlementsClick,
} from './ga';
import { IsOutsideDateRangeForHPAnalytics } from './utils';

const TerminalStatus = lazy(() =>
  import(
    /* webpackChunkName: 'terminal-status-banner' */ 'merchant/components/Announcements/TerminalStatus'
  ),
);

const PaymentsRecapBanner = lazy(() =>
  import(
    /* webpackChunkName: 'payments-recap-banner' */ 'merchant/components/HeaderNav/PaymentsRecap/Banner'
  ),
);

class AnalyticsDesktop extends Component {
  state = {
    showNcPopup: true,
    settlementExists: true,
    shouldShowTnCBannerForAxis: false,
    isWebsiteComplianceModalShown: false,
  };
  constructor(props) {
    super(props);

    this.showOndemandSettlementForm = this.showOndemandSettlementForm.bind(this);
  }

  popupCredit = () => {
    if (this.props.location.hash === '#creditscore') {
      this.resetHash();
      this.props.openModal({
        component: <CreditPullModal fromWhere="Announcements" />,
        size: 'regular',
      });
    }
  };

  componentDidUpdate() {
    this.popupCredit();
    this.renderL1ActivationModals();
  }
  hideGluOnE2E() {
    return LocalStorageService.getItem('CUSTOMER_GLU_E2E') !== 'off';
  }
  componentDidMount() {
    const {
      user,
      fetchEscalations,
      fetchInternationalProductsStatus,
      fetchSettlementConfig,
      fetchBankAccountChangeStatus,
      fetchCarouselBanner,
      fetchInternationalSettingStatus,
      isNcEligibile,
      splitz,
      mode,
    } = this.props;

    const { abExperiments: { STREAKS_REWARDS_GROWTH } = {} } = splitz;

    if (this.hideGluOnE2E() && isExperimentActive(STREAKS_REWARDS_GROWTH) && mode === 'live') {
      // TODO: remove 'mode' once new splitz flow have support for 'request_data'
      import('merchant/views/Growth/StreaksReferralIncentiveProgram/CustomerGluSdk').then(
        (loadedModule) => {
          loadedModule?.loadCustomerGluSdk();
        },
      );
    }

    fetchEscalations();
    analyticsTrack({
      objectName: 'home page',
      actionName: 'displayed',
      screen: 'home page',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    this.props.trackEvents({
      objectName: 'Page',
      actionName: 'Viewed',
      screen: 'home page',
      properties: {
        slug: window.location.pathname,
        pageUrl: window.location.href,
        PageTitle: 'Razorpay Dashboard',
      },
    });
    fetchInternationalProductsStatus();
    fetchCarouselBanner({ fromWhere: 'HomeCarouselBanner' });

    fetchSettlementConfig();
    fetchBankAccountChangeStatus(user.id);

    this.checkIfFirstEverSettlement();

    const activationState = getActivationState(user, user.isUnregisteredBusiness, isNcEligibile);
    const shouldShowModal =
      activationState === 'L2_dedupe_blocked' ||
      activationState === 'needs_clarification_mcc_pending' ||
      activationState === 'needs_clarification' ||
      activationState === 'needs_clarification_payments_settlement_enabled' ||
      activationState === 'needs_clarification_with_payment_disabled' ||
      activationState === 'needs_clarification_with_payments_enabled' ||
      activationState === 'funds_on_hold' ||
      activationState === 'rejected' ||
      activationState === 'needs_clarification_for_pos';

    if (user.isInstantActivationEnabled && shouldShowModal) {
      this.props.showKYCStatusModal({
        modalType: 'KYC_ACTIVATION_SUBMIT_MODAL',
      });
    }

    this.canShowBannerForAxis(activationState, user.isOrgAxis, user.merchant_tnc);

    if (user.isOrgRZP && Boolean(user.activated)) fetchInternationalSettingStatus();
  }

  renderL1ActivationModals = () => {
    const {
      user,
      // from parent component
      canShowL1ActivationModals,
    } = this.props;

    if (!canShowL1ActivationModals) return;

    const isWebsitePolicyVerified = user?.website_policy_verification_status === 'verified';

    // if website policy verification status is verified don't show modal
    if (user.isWebsiteComplianceFlowEnabled && !isWebsitePolicyVerified) {
      this.renderWebsiteCompliancePrompt();
    }
  };

  renderWebsiteCompliancePrompt = () => {
    if (this.state.isWebsiteComplianceModalShown) return;
    const {
      activationData,
      websiteSectionDetailsData,
      websiteComplianceModalVisibility,
      splitz,
      user,
    } = this.props;

    if (
      activationData.data &&
      websiteSectionDetailsData.data &&
      websiteComplianceModalVisibility.data
    ) {
      const shouldShowModal =
        !isPolicyWizardV2Enabled({ splitz, user, activationData: activationData.data }) &&
        shouldShowWebsiteComplianceModal(
          activationData,
          websiteSectionDetailsData,
          websiteComplianceModalVisibility,
        );

      if (shouldShowModal && this.state.isWebsiteComplianceModalShown === false) {
        this.setState({
          isWebsiteComplianceModalShown: true,
        });
        this.props.openModal({
          component: <WebsiteCompliancePrompt />,
          size: 'small',
        });
      }
    }
  };

  canShowBannerForAxis = (activationState, isOrgAxis, isTncGenerated) => {
    const showTncForAxis =
      [
        'activated_mcc_pending_without_tnc',
        'under_review_without_tnc_passed',
        'under_review_without_tnc',
        'under_review_without_tnc_partial',
      ].includes(activationState) &&
      isOrgAxis &&
      !isTncGenerated;
    //default false not to show other banner for axis org
    this.setState({ shouldShowTnCBannerForAxis: showTncForAxis });
  };

  checkIfFirstEverSettlement = (callbackSettlementStatus) => {
    const settlementStatus = getSettlementStatus(this.props.user.current, callbackSettlementStatus);
    const isDisabled =
      settlementStatus === 'disableAnimation' || settlementStatus === 'disableAnimationOnReload';

    this.setState({
      settlementExists: isDisabled || settlementStatus,
    });
  };

  resetHash = () => {
    this.props.history.push({
      pathname: this.props.history.location.pathname,
      hash: '',
    });
  };

  showOndemandSettlementForm() {
    const { current_balance, ondemand_restrictions, user } = this.props;
    trackSettleNow();
    selfServeSettleTracking();
    const esOndemandSettlementEnabled = user.isFeatureEnabled('es_on_demand');
    const balance = current_balance.data.balance;
    const settlableAmount = ondemand_restrictions && ondemand_restrictions.data.settlable_amount;

    this.props.openModal({
      component: (
        <OndemandModal
          animatedSettlemnetBtn={!this.state.settlementExists && esOndemandSettlementEnabled}
          currentBalance={balance}
          settlableAmount={settlableAmount}
          eventCategory={EVENT_CATEGORY_DASHBOARD_HOME}
          fromWhere="Home"
          checkIfFirstEverSettlement={this.checkIfFirstEverSettlement}
          goBackToInitialModalView={this.showOndemandSettlementForm}
        />
      ),
      size: 'small',
      disableClose: true,
    });
  }

  isCaptureSettingsDefault = (items) => {
    if (!items) return false;
    else if (items[0]) {
      // check for created_at & updated_at in config
      const config = items[0];
      const isSame = moment(config.created_at).isSame(config.updated_at);
      // If both created_at & updated_at are same, only then show banner
      return isSame;
    } else return false;
  };

  isWhatsappNotificationEnabled = (user) => {
    return (
      user.isWhatsappNotificationEnabled() &&
      user.contact_mobile &&
      user.activation_status === 'activated' &&
      user.role === 'owner'
    );
  };

  onClickCovidEnableNow = async () => {
    try {
      await merchantFetch({
        url: `merchants/me/features?features[covid_19_relief]=1`,
        mode: `${this.props.mode}`,
        method: 'POST',
      });

      await this.props.fetchUser();

      this.props.openModal({
        size: 'medium',
        component: <CovidKnowMore isCovidDonations />,
      });
      this.props.history.push('/config');

      return true;
    } catch (err) {
      this.props.showNotification({
        type: 'error',
        message: err.errors,
      });
      return false;
    }
  };

  renderOnboardingWidgets = () => {
    const {
      user,
      showOnboardingBannerFirstStep,
      expandOnboardingBanner,
      payments,
      showOnboardingBanner,
      showInstantActivation,
      onHideOnboardingBanner,
      onFirstStepClose,
      limitBreach,
    } = this.props;

    const isEligibleForFeeBasedGating = checkEligibilityForFeeBasedGating(user);

    const onboardingCard = (
      <div className={`v2-onboarding-card${expandOnboardingBanner ? ' expand' : ''}`}>
        {showOnboardingBanner || isEligibleForFeeBasedGating ? (
          <NewUserOnboardingCard
            payments={payments}
            onClose={onHideOnboardingBanner}
            onFirstStepClose={onFirstStepClose}
            isFirstStep={showOnboardingBannerFirstStep}
            showInstantActivation={showInstantActivation}
            limitBreach={limitBreach}
          />
        ) : null}
      </div>
    );

    const ProductLedOnboardingWidget =
      user?.isProductLedOnboardingRZP && !!user?.activated ? <ProductOnboardingCard /> : null;

    /* Recommended product widget */
    const ProductRecommendationWidget = user.isProductRecommendationEnabled && (
      <ProductRecommendationnCard user={user} />
    );
    const widget = ProductLedOnboardingWidget ?? ProductRecommendationWidget;

    // if Payment Enabled Merchant - switch the order of the widgets
    if (user.activation_form_milestone && user.activated) {
      return [widget, onboardingCard];
    }
    return [onboardingCard, widget];
  };

  onKnowMoreClick = () => {
    const { user, settlement_amount, openModal } = this.props;
    openModal({
      size: 'medium',
      component: <SettlementDetail user={user} settlementAmount={settlement_amount.data} />,
    });
    window.rzpAnalytics?.({
      eventCategory: 'Settlement Revamp',
      eventAction: 'Know more - Next Settlement',
      eventLabel: `Home`,
    });
  };
  cta1ClickHandler = () => {
    const { history = {} } = this?.props;
    history?.push('/qr_codes');
  };

  render() {
    const { settlementExists, shouldShowTnCBannerForAxis } = this.state;
    const {
      mode,
      user,
      config,
      current_balance,
      tabsMeta,
      isAdmin,
      analyticsFetch,
      onFilterChange,
      startDate,
      endDate,
      oldestTransactionDate,
      dateRangePresets,
      showGroupingByPtfm,
      payments,
      showOnboardingBanner,
      showInstantActivation,
      onDatesChange,
      onFetchPayments,
      onExtraContentMount,
      settlement_amount,
      defaultPreset,
      keymetricsSectionTitle,
      paymentInsightsTitle,
      recentActivityTitle,
      trafficSectionTitle,
      lateAuthConfig,
      ondemand_restrictions,
      settleNowRestrictionMsg,
      limitBreach,
      isOnDemandDisabled,
      settlementConfig,
      bannerCarouselData: { banner_carousel_items = [] } = {},
      internationalSettingStatus,
      i18: { isConfigTagEnabled },
    } = this.props;
    const {
      data: { items },
    } = lateAuthConfig;

    const hasSecondaryBanner =
      showInstantActivation && config.config && !config.config.hasPersonalised;

    const nextSettlement = !settlement_amount.data.next_settlement_time;
    const { no_settlement, settlement_currency: settlementCurrency } = settlement_amount.data;
    const attemptsLeft = ondemand_restrictions && ondemand_restrictions.data.attempts_left;
    const isOndemandRestrictionsLoading = ondemand_restrictions && ondemand_restrictions.loading;
    const settlableAmount = ondemand_restrictions && ondemand_restrictions.data.settlable_amount;
    const isEsOnDemandBlocked = user.isEsOnDemandBlocked;
    const isSettleNowRestricted =
      ondemand_restrictions && (!attemptsLeft || !settlableAmount || isOndemandRestrictionsLoading);
    const checkIfSettlementDisabled =
      isSettleNowRestricted ||
      current_balance.loading ||
      current_balance.data.balance < 100 ||
      isOnDemandDisabled ||
      isEsOnDemandBlocked;
    let balance = current_balance.data.balance;
    let negativeBalanceClassName = '';
    const esOndemandSettlementEnabled = user.isFeatureEnabled('es_on_demand');
    const isOnTemporaryHold = settlementConfig.data?.config?.features?.hold?.status;
    const isOnHold = no_settlement?.on_hold;
    const isSettlementOnHold = isOnTemporaryHold || isOnHold;
    if (balance < 0) {
      balance = Math.abs(current_balance.data.balance);
      negativeBalanceClassName = 'negative-balance';
    }
    const ticketsRaisedByAgents = this.props.ticketsRaisedByAgents.filter(
      (ticket) =>
        Boolean(new Date(ticket.created_at).getSeconds()) &&
        STATUSES[ticket?.status] === 'AWAITING_YOUR_REPLY',
    );
    const { showState, proceededBank } = getXCAStatus(user);
    const showNitroStatusTracker = showState === 'neostone-tracker';
    let carouselItem = [];
    if (banner_carousel_items.length) carouselItem = [...banner_carousel_items];

    const activationState = getActivationState(
      user,
      user.isUnregisteredBusiness,
      this.props.isNcEligibile,
    );

    const onNcModalClose = () => {
      const sessionExpired = window.session_id !== window.sessionStorage.getItem('isNewNc');
      if (isNewNcActivationStatus(activationState)) {
        if (sessionExpired) {
          window.sessionStorage.setItem('isNewNc', window.session_id);
        }
      }
      this.setState({
        showNcPopup: false,
      });
    };

    const goToNCOnEasy = () => {
      analyticsTrack({
        objectName: 'NC Resolve Now',
        actionName: 'Clicked',
        screen: 'home page',
        properties: {
          funnelStage: 'NC',
          formName: 'We need a few more details to complete KYC verification',
          ctaClicked: 'Resolve Now',
          clickSource: 'NC Modal',
          activationState,
          ncCount: `${user?.kyc_clarification_reasons?.nc_count}`,
          deviceType: isMobileDevice(768) ? 'mweb' : 'dweb',
          ...getCommonAnalyticsProperties(window.rzp_user),
        },
      });
      const needsClarificationOnEasyUrl = getNCUrlOnEasyOrPhantom();
      onNcModalClose();
      window.open(needsClarificationOnEasyUrl, '_self', 'noopener');
    };

    return (
      <div className="home-analytics-desktop">
        <PaymentsRecapBanner bannerVariant="desktop" user={user} />
        <PricingSubscriptionWrapper />
        <ShowWhen additionalCondition={() => !isConfigTagEnabled('onboarding.getting_started')}>
          {/* Announcement Banner Start */}
          <div
            ref={(node) => onExtraContentMount(node)}
            className={`extra-content${showOnboardingBanner ? ' has-ob-banner' : ''}${
              !showOnboardingBanner && hasSecondaryBanner ? ' has-secondary-banner' : ''
            }`}
          >
            {ticketsRaisedByAgents.length && user.isMobileSignupCareActive ? (
              <SupportRequest tickets={ticketsRaisedByAgents} />
            ) : null}
            <CongratulatoryBanner user={user} />
            {/* nps banner */}
            {user.isAccepted && <NPSAnnouncement user={user} />}
            {/* onboarding banner */}
            {(showInstantActivation || shouldShowTnCBannerForAxis) && (
              <Announcement
                mode={mode}
                user={user}
                payments={payments}
                limitBreach={limitBreach}
                shouldShowTnCBannerForAxis={shouldShowTnCBannerForAxis}
              />
            )}
            <WebsiteComplianceBanner screen="Home page" />
            {checkHTML5APIvalidity() && (
              <AnnouncementBanner title="Outdated Browser" theme="warning">
                Please update your web browser. We recommend you to download the latest version of
                Google Chrome, Edge, Safari, Firefox.
              </AnnouncementBanner>
            )}
            {user.isPaymentsEnabled && this.props.referee?.status === 'signup' ? (
              <AnnouncementBanner
                title="Unlock Pending Credits"
                theme="warning"
                card_id="merchant_referral"
              >
                Accept payments of minimum ₹2,000 to receive{' '}
                {getFormattedAmountNew(this.props.referee.referral_amount, true)} in collections -
                100% FREE*
                <div class="big-circle-seprator" />
                <a className="btn-link" onClick={() => this.props.showProductsModal()}>
                  Accept payments.
                </a>{' '}
              </AnnouncementBanner>
            ) : null}
            {/* international onboarding banner */}
            {mode === 'live' &&
              user.instantActivation.isGraylistFlow &&
              user.internationalActivationFlow.isGraylistFlow && (
                <InternationalRequestStatusAnnouncement
                  internationalProductsStatus={this.props.internationalProductsStatus}
                />
              )}
            {mode === 'live' && (
              <InternationalFormStatusAnnouncement
                status={this.props.internationalSettingStatus}
                businessName={user.business_name}
              />
            )}
            {/* needs clarification modal */}
            {this.state.showNcPopup &&
              user.needsClarification &&
              !this.props.user.isInstantActivationEnabled && (
                <NCModal
                  isActivationFormFullView={this.props.user.isActivationFormFullView}
                  onClose={onNcModalClose}
                  activationState={activationState}
                  kycClarificationsReasons={this.props.user?.kyc_clarification_reasons}
                  goToNCOnEasy={goToNCOnEasy}
                  user={this.props.user}
                />
              )}
            {!this.props.user.isInstantActivationEnabled &&
              !!this.props.user.locked &&
              (this.props.user.activation_status === 'under_review' ||
                this.props.user.activation_status === 'kyc_qualified_unactivated') &&
              this.props.user.isDedupe && <DedupeModal />}
            {!user.isFeatureEnabled('covid_19_relief') &&
              user.isCovidReliefFlowEnabled &&
              user.business_type !== 7 &&
              user.business_type !== 9 && (
                <AnnouncementBanner
                  title="Donations for Covid Relief"
                  theme="primary"
                  card_id="donations-covid-relif-banner"
                >
                  Enable donations on Checkout Page and help India Fight COVID-19.{' '}
                  <Link to="/config" className="pointer">
                    <strong>Know More</strong>
                  </Link>{' '}
                  <AsyncButton
                    type="button"
                    className="Button--secondary Button scheduled-btn-act btn-border"
                    onClick={this.onClickCovidEnableNow}
                    text="Enable Now"
                    pendingText="Enabling..."
                  />
                </AnnouncementBanner>
              )}
            {this.isCaptureSettingsDefault(items) &&
              user.instantActivation.isWhitelistFlow === true && (
                <AnnouncementBanner
                  title="Capture Settings"
                  theme="success"
                  canBeClosed={true}
                  card_id="capture-settings-banner"
                >
                  Currently all payments with order id are being captured by default, click{' '}
                  <Link
                    onClick={() => {
                      analyticsTrack({
                        objectName: 'banner',
                        actionName: 'clicked',
                        screen: 'home page',
                        properties: {
                          hyperlinkClicked: 'here',
                          title: 'Capture Settings',
                          ...getCommonAnalyticsProperties(window.rzp_user),
                        },
                      });
                    }}
                    to="/config"
                    target="_blank"
                    rel="noreferrer noopener"
                  >
                    here
                  </Link>{' '}
                  to configure your capture setting.
                </AnnouncementBanner>
              )}
            <DashboardBanner />
            <ShowWhen additionalCondition={(usr) => usr.isCatalystBannerFL}>
              <CatalystCampaignBannerPhase2
                productName="Transactions"
                title="Boost your revenue 🚀"
                bannerText="Use Payment Links to retarget users and boost conversions by up to 40%"
                cardId="NOV21-CATALYSTP1V2FL"
                cta1Text="Know More"
                cta2Text="Try Now"
                cta2Link="paymentlinks/new"
                type="FL"
              />
            </ShowWhen>
            <ShowWhen additionalCondition={(usr) => usr.isCatalystBannerEF}>
              <CatalystCampaignBannerPhase2
                productName="Transactions"
                title="Boost your revenue 🚀"
                bannerText="Use Payment Links for instant payment collection by offering 100+ payment methods"
                cardId="NOV21-CATALYSTP1V2EF"
                cta1Text="Know More"
                cta2Text="Try Now"
                cta2Link="paymentlinks/new"
                type="EF"
              />
            </ShowWhen>
            <ShowWhen additionalCondition={(usr) => usr.isCatalystBannerG}>
              <CatalystCampaignBannerPhase2
                productName="Transactions"
                title="Grow your business 🚀"
                bannerText="Use payment links for payment collections. 100+ payment modes available. Set reminders and never miss any payment."
                cardId="NOV21-CATALYSTP1V2GE"
                cta1Text="Know More"
                cta2Text="Try Now"
                cta2Link="paymentlinks/new"
                type="G"
              />
            </ShowWhen>
            {/* capital banner*/}
            {user.isCapitalBannerEnabled && <CapitalAnnouncement userId={user.current} />}
            {user.isCovidFeatureEnabled && <CovidCampaignAnnouncement userId={user.current} />}
            {/* Free Credits Repayments Banner */}
            {user.isRepaymentBannerEnabled && <RepaymentAnnouncment userId={user.current} />}
            {/* Announcement - Enable International Cards */}
            {mode === 'live' && user.showTerminalStatusBanner ? (
              <Suspense fallback={null}>
                <TerminalStatus user={user} />
              </Suspense>
            ) : null}
            <ShowWhen
              additionalCondition={(usr) =>
                usr.isOrgRZP &&
                Boolean(usr.activated) &&
                internationalSettingStatus?.data?.enableIntlCards
              }
            >
              <IntlPaymentsAnnouncement
                bannerKey="international_cards"
                userId={user?.current}
                internationalSettingStatus={this.props.internationalSettingStatus}
              />
            </ShowWhen>
            {/* Announcement - Link Paypal */}
            <ShowWhen
              additionalCondition={(usr) =>
                usr.isOrgRZP &&
                Boolean(usr.activated) &&
                internationalSettingStatus?.data?.enableLinkPaypal
              }
            >
              <IntlPaymentsAnnouncement
                bannerKey="link_paypal"
                userId={user?.current}
                internationalSettingStatus={this.props.internationalSettingStatus}
              />
            </ShowWhen>
            {showNitroStatusTracker && (
              <div className="nss-tracker-wrapper">
                <NeoStoneTracker proceededBank={proceededBank} user={user} />
              </div>
            )}
            {this.props.can_refer ? <M2MBanner /> : null}
            {/* Announcement Banners End */}
            {/* TODO: Move announcement section to different file */}
            <GrowthAssetEB>
              {carouselItem.length ? (
                <Carousel enableLazy minHeight={200} carouselItem={carouselItem} />
              ) : null}
            </GrowthAssetEB>

            {this.renderOnboardingWidgets()}
            <ShowWhen additionalCondition={(usr) => usr.isOrgRZP && Boolean(usr.activated)}>
              <IntlPaymentsRecommendation
                user={user}
                internationalSettingStatus={this.props.internationalSettingStatus}
              />
            </ShowWhen>
            {hasSecondaryBanner && (
              <div className="secondary-announcement-banner">
                <PersonaliseBanner track={trackPersonaliseBanner} />
              </div>
            )}
          </div>
        </ShowWhen>

        {/* <Sticky stickWhen={scrollAmountToStickHeader} stickAt={50}> */}
        <Header className="clearfix" title="" showMode={false}>
          <div
            id="analytics-daterange-picker"
            className="pull-left date-range-container date-range-tooltip-container"
          >
            <DateRangePicker
              presets={dateRangePresets}
              onDatesChange={onDatesChange}
              defaultPreset={defaultPreset}
              onSelectPreset={trackPresetChange}
              isOutsideRange={IsOutsideDateRangeForHPAnalytics}
            />
            <DateRangeTooltip />
          </div>

          <ShowWhen additionalCondition={() => !isConfigTagEnabled('announcements.announcements')}>
            <div
              className={`pull-right ${
                this.props.user.isOndemandSettlementEnabled ? 'ondemand-enabled' : ''
              }`}
            >
              <Group>
                {this.props.user.isOrgAllowedFunctionality('current_balance') && (
                  <GroupItem>
                    <div className="text-right">
                      <span className="settlement-balance-amount">
                        <strong>Current Balance: </strong>
                        {!current_balance.loading && (
                          <Amount
                            value={balance}
                            currency={user.merchant.currency}
                            className={negativeBalanceClassName}
                          />
                        )}
                      </span>
                      <CashAdvanceNudge />
                      <br />
                      {isSettlementOnHold && (
                        <div className="text-right full-width no-margin">
                          {isOnHold
                            ? 'Settlements under review.'
                            : 'Your settlements have been put on Temporary hold.'}
                          <span className="pr-5">
                            <i className="i i-info-circle" />
                            <Popover theme="dark" align="bottom">
                              <PopoverBody>
                                {isOnHold
                                  ? 'Your settlements are currently under review and not getting processed.'
                                  : 'Your settlements are currently not being processed.'}
                              </PopoverBody>
                            </Popover>
                          </span>
                          <span className="btn-link pointer" onClick={this.onKnowMoreClick}>
                            Know More
                          </span>
                        </div>
                      )}
                      {no_settlement &&
                      !isSettlementOnHold &&
                      payments &&
                      payments.items.length > 0 &&
                      mode === 'live' ? (
                        <div className="text-right full-width no-margin">
                          {no_settlement.caption}
                          {no_settlement.reason && (
                            <span>
                              <i className="i i-info-circle" />
                              <Popover theme="dark" align="left">
                                <PopoverBody>
                                  <div>{no_settlement.reason}</div>
                                </PopoverBody>
                              </Popover>
                            </span>
                          )}
                        </div>
                      ) : null}
                      {!isSettlementOnHold && !no_settlement && !nextSettlement ? (
                        <div className="text-right full-width no-margin">
                          <strong>
                            <Amount
                              className="pr-5"
                              value={settlement_amount.data.settlement_amount}
                              currency={user.merchant.currency}
                            />
                          </strong>
                          <span className="pr-5">will be settled on</span>
                          <Time
                            className="pr-5"
                            value={settlement_amount.data.next_settlement_time}
                            format="DD MMM YYYY, hh:mm a"
                          />
                          {settlement_amount.data.reason_for_delay && (
                            <span>
                              <i className="i i-info-circle" />
                              <Popover theme="dark" align="left">
                                <PopoverBody>
                                  <div>{settlement_amount.data.reason_for_delay}</div>
                                </PopoverBody>
                              </Popover>
                            </span>
                          )}
                          <span className="btn-link ml-5 pointer" onClick={this.onKnowMoreClick}>
                            <strong>Know more</strong>
                          </span>
                        </div>
                      ) : null}
                    </div>
                  </GroupItem>
                )}
                <GroupItem>
                  {this.props.user.isOndemandSettlementEnabled &&
                  this.props.user.isAllowedView('early_settlement') ? (
                    <div className="settlenow-container">
                      <SettleNowButton
                        disabled={checkIfSettlementDisabled}
                        merchantId={user.current}
                        fromWhere="Home"
                        settlementExists={settlementExists}
                        esOndemandSettlementEnabled={esOndemandSettlementEnabled}
                        showOndemandSettlementForm={this.showOndemandSettlementForm}
                        checkIfFirstEverSettlement={this.checkIfFirstEverSettlement}
                      />

                      {settleNowRestrictionMsg && (
                        <Popover
                          align="top"
                          parentQuerySelector=".settle-btn .settle-now--desktop"
                          theme="dark"
                        >
                          <PopoverBody>{settleNowRestrictionMsg}</PopoverBody>
                        </Popover>
                      )}
                    </div>
                  ) : (
                    <Link className="pull-right btn-text" to="/settlements">
                      <span
                        className="text-no-wrap"
                        onClick={() => {
                          trackSettlementsClick();
                          analyticsTrack({
                            objectName: 'settlements',
                            actionName: 'clicked',
                            screen: 'home page',
                            properties: {
                              location: 'analytics',
                              ...getCommonAnalyticsProperties(window.rzp_user),
                            },
                          });
                        }}
                      >
                        View Settlements
                      </span>
                    </Link>
                  )}
                </GroupItem>
              </Group>
            </div>
          </ShowWhen>
        </Header>
        {/* </Sticky> */}
        <div className="dashboard">
          <div className="row">
            <div className="col-md-12">
              <KeyMetrics
                startDate={startDate}
                endDate={endDate}
                oldestTransactionDate={oldestTransactionDate}
                mode={mode}
                showGroupingByPtfm={showGroupingByPtfm}
                sectionTitle={keymetricsSectionTitle}
                tabsMeta={tabsMeta}
                isAdmin={isAdmin}
                analyticsFetch={analyticsFetch}
                onFilterChange={onFilterChange}
              />
            </div>
          </div>
          <div className="row">
            <LazyLoad height={100} offset={50} once>
              <div className="col-md-12">
                <ShowWhen
                  additionalCondition={() => !isConfigTagEnabled('announcements.announcements')}
                >
                  <EasterEgg extraClass="ftx-home-page" page="Home" />
                </ShowWhen>

                <div className="section-title payment-insights-title">
                  {paymentInsightsTitle}&nbsp;
                  <small>
                    <i className="i i-help" />
                    <Popover align="top">
                      <PopoverBody>
                        <p>
                          This graph helps you gain insights into your overall payments by seeing
                          how different payment methods stack up against each other in your revenue
                          pool.
                        </p>
                        <div>
                          <span className="popover-highlight">Click tiles</span> to drill-down into
                          the hierarchy.
                        </div>
                        <div>
                          <span className="popover-highlight">Hover</span> to view information for
                          smaller tiles.
                        </div>
                      </PopoverBody>
                    </Popover>
                  </small>
                </div>
              </div>
              <div className="col-md-12">
                <Suspense fallback={null}>
                  <PaymentMethods
                    startDate={startDate}
                    endDate={endDate}
                    mode={mode}
                    analyticsFetch={analyticsFetch}
                    sectionTitle={paymentInsightsTitle}
                  />
                </Suspense>
              </div>
            </LazyLoad>
          </div>
          <div className="row">
            <div
              className={`col-md-12 traffic-activity-row clearfix${
                showGroupingByPtfm ? '' : ' traffic-hidden'
              }`}
            >
              {showGroupingByPtfm && (
                <div className="traffic-container">
                  <p className="content-title section-title">{trafficSectionTitle}</p>
                  <div className="content">
                    <LazyLoad height={100} offset={50} once>
                      <Traffic
                        startDate={startDate}
                        endDate={endDate}
                        mode={mode}
                        analyticsFetch={analyticsFetch}
                        sectionTitle={trafficSectionTitle}
                      />
                    </LazyLoad>
                  </div>
                </div>
              )}
              {!isAdmin && (
                <div className="activity-container">
                  <p className="content-title section-title">{recentActivityTitle}</p>
                  <div className="content">
                    {/* <LazyLoad height={100} offset={50} once> */}
                    {/* TODO: Load lazy once onFetchPayments api resolve for Onboarding card */}
                    <RecentActivity
                      startDate={startDate}
                      endDate={endDate}
                      sectionTitle={recentActivityTitle}
                      onFetchPayments={onFetchPayments}
                      user={this.props.user}
                      currentBalance={current_balance}
                      onSelect={this.showOndemandSettlementForm}
                      settlementCurrency={settlementCurrency}
                    />
                    {/* </LazyLoad> */}
                  </div>
                </div>
              )}
            </div>
          </div>
        </div>
      </div>
    );
  }
}

const mapStateToProps = (state) => ({
  user: state.session.user,
  mode: state.session.mode,
  config: state.config,
  websiteSectionDetailsData: state.websiteCompliance.websiteSectionDetailsData,
  websiteComplianceModalVisibility: state.websiteCompliance.bannerAndModalVisibility,
  activationData: state.websiteCompliance.activationData,
  internationalProductsStatus: state.config.internationalProductsStatus,
  limitBreach: state.home.limitBreach,
  settlementConfig: state.settlement.config,
  can_refer: state.merchantReferral.data.can_refer,
  referee: state.merchantReferral.data.referee,
  transactionAmount: state.transactionAmount.amount,
  ticketsRaisedByAgents: state.config.ticketsRaisedByAgents.data[1],
  bannerCarouselData: state?.growthService?.banner_carousel_items,
  internationalSettingStatus: state.config.internationalSettingStatus,
  isNcEligibile: state.home.isNcEligibile,
});

export default withRouter(
  connect(mapStateToProps, {
    openModal: fnOpenModal,
    fetchInternationalProductsStatus: fnFetchInternationalProductsStatus,
    fetchInternationalSettingStatus: fnFetchInternationalSettingStatus,
    ...NotificationActions,
    fetchUser,
    showKYCStatusModal,
    fetchEscalations: fnFetchEscalations,
    fetchSettlementConfig: fnFetchSettlementConfig,
    fetchBankAccountChangeStatus: fnFetchBankAccountChangeStatus,
    fetchCarouselBanner: fetchCarouselBannerProp,
    showProductsModal,
    ...EventActions,
  })(withSplitzService(withI18Service(AnalyticsDesktop))),
);
