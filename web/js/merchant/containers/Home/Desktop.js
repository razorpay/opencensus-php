import React, { Component } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router';
import { Link } from 'react-router-dom';
import { fetchInternationalProductsStatus as fnFetchInternationalProductsStatus } from 'merchant/reducers/config';
import * as NotificationActions from 'merchant_common/reducers/notifications';
import Header from 'common/ui/Header';
import moment from 'moment';
import Amount from 'common/ui/Amount';
import Group, { GroupItem } from 'common/ui/Group';
import DateRangePicker from 'common/ui/DateRangePicker';
import Popover, { PopoverBody } from 'common/ui/Popover';
import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';
import NewUserOnboardingCard from 'merchant/containers/Home/OnboardingCard';
import ProductRecommendationnCard from 'merchant/containers/Home/ProductRecommendationnCard';
import KeyMetrics from 'merchant/containers/Home/KeyMetrics';
import PaymentMethods from 'merchant/containers/Home/PaymentMethods';
import Traffic from 'merchant/containers/Home/Traffic';
import RecentActivity from 'merchant/containers/Home/RecentActivity';
import Announcement from 'merchant/components/Announcements/Instant';
import BaseDashboardBanner from '../../../common/ui/DashboardBanner/BaseDashboardBanner';
import NPSAnnouncement from 'merchant/components/Announcements/NPSAnnouncement';
import CapitalAnnouncement from 'merchant/components/Announcements/Capital';
import CovidCampaignAnnouncement from 'merchant/components/Announcements/CovidCampaign';
import RepaymentAnnouncment from 'merchant/components/Announcements/PaymentRecovery';
import CatalystCampaignBannerPhase2 from 'merchant/components/Announcements/CatalystCampaignBannerPhase2';
import PersonaliseBanner from 'merchant/components/Announcements/PersonaliseAccount';
import InternationalRequestStatusAnnouncement from 'merchant/components/Announcements/InternationalRequestStatus';
import OndemandModal from 'merchant/views/Settlements/Settlements/components/Modals/OndemandModal';
import { openModal as fnOpenModal } from 'merchant_common/reducers/modals';
import { trackPersonaliseBanner } from 'merchant/containers/Home/OnboardingCard/Instant/ga';
import CovidKnowMore from 'common/ui/CovidKnowMore';
import CreditPullModal from 'merchant/containers/CreditPullModal';
import {
  trackPresetChange,
  trackSettlementsClick,
  trackSettleNow,
  EVENT_CATEGORY_DASHBOARD_HOME,
} from './ga';
import SettlementDetail from 'merchant/views/Settlements/Settlements/components/SettlementDetail';
import {
  handleNegativeBalanceLimit,
  getCommonAnalyticsProperties,
  getFormattedAmountNew,
  checkHTML5APIvalidity,
} from 'common/utils/rzp-utils';
import Time from 'common/ui/Time';
import { merchantFetch } from 'merchant/utils/ajax';
import { analyticsTrack } from 'common/utils/analytics';
import { fetchUser } from 'merchant/reducers/session';
import { fetchSettlementConfig as fnFetchSettlementConfig } from 'merchant/reducers/settlements/details';
import AsyncButton from 'react-async-button';
import { getSettlementStatus } from 'merchant/views/Capital/utils';
import SettleNowButton from 'merchant/views/Settlements/Settlements/components/SettleNowButton';
import {
  showKYCStatusModal,
  fetchEscalations as fnFetchEscalations,
  showProductsModal,
} from 'merchant/reducers/home';
import { fetchBankAccountChangeStatus as fnFetchBankAccountChangeStatus } from 'merchant/reducers/profile';
import { getActivationState } from 'merchant/components/Activation/ActivationUtils';
import NCModal from 'merchant/components/Activation/NCModal';
import DedupeModal from 'merchant/components/Home/DedupeModal';
import NeoStoneTracker from 'common/ui/NotificationsDropdown/Neostone/Tracker';
import CongratulatoryBanner from 'merchant/components/Announcements/CongratulatoryBanner';
import { getXCAStatus } from 'common/ui/NotificationsDropdown/Neostone/common/utils';
import ShowWhen from '../../components/ShowWhen';
import ABCBanner from '../../components/Announcements/ABCBanner';
import StartupCongratulationBanner from '../../components/Announcements/StartupCongratulationBanner';
import CrossBorderPaymentsBanner from '../../components/Announcements/CrossBorderPaymentsBanner';
import EasterEgg from 'merchant/components/EasterEgg';
import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import NitroICICIBanner from '../../components/Announcements/NitroICICIBanner';
import NitroCCCampaign from '../../components/Announcements/NitroCCCampaign';
import NitroMMRemarketingBanner from '../../components/Announcements/NitroMMRemarketingBanner';
import M2MBanner from 'merchant/components/M2M/M2MBanner';
import DashboardBanner from 'common/ui/DashboardBanner';
import SupportRequest from 'merchant/components/Announcements/SupportRequest';
import * as EventActions from 'merchant/reducers/trackEvents';

class AnalyticsDesktop extends Component {
  state = {
    showNcPopup: true,
    settlementExists: true,
    shouldShowTnCBannerForAxis: false,
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
  }

  componentDidMount() {
    const {
      user,
      fetchEscalations,
      fetchInternationalProductsStatus,
      fetchSettlementConfig,
      fetchBankAccountChangeStatus,
    } = this.props;
    fetchEscalations();
    analyticsTrack({
      objectName: 'home page',
      actionName: 'displayed',
      screen: 'home page',
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
    if (user.isNewSettlementServiceEnabled) {
      fetchSettlementConfig(user.id);
    }
    fetchBankAccountChangeStatus(user.id);

    this.checkIfFirstEverSettlement();

    const activationState = getActivationState(user, user.isUnregisteredBusiness);
    const shouldShowModal =
      activationState === 'L2_dedupe_blocked' ||
      activationState === 'needs_clarification_mcc_pending' ||
      activationState === 'needs_clarification' ||
      activationState === 'funds_on_hold' ||
      activationState === 'rejected';

    if (user.isInstantActivationEnabled && shouldShowModal) {
      this.props.showKYCStatusModal({
        modalType: 'KYC_ACTIVATION_SUBMIT_MODAL',
      });
    }
    this.canShowBannerForAxis(activationState, user.isOrgAxis, user.merchant_tnc);
  }

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

  onNcModalClose = () => {
    this.setState({
      showNcPopup: false,
    });
  };

  isWhatsappNotificationEnabled = (user) => {
    return (
      user.isWhatsappNotificationEnabled() &&
      user.contact_mobile &&
      user.activation_status === 'activated' &&
      user.role === 'owner'
    );
  };

  showGSTOptOutFlow = () => {
    if (this.props.user.features) {
      const show = this.props.user.features.filter((f) => f.feature === `suggested_address_opt_in`);
      if (show.length > 0) return true;
      else;
      return false;
    } else {
      return false;
    }
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

  renderOnboardingAndRecommendationWidget = () => {
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

    const onboardingCard = (
      <div className={`v2-onboarding-card${expandOnboardingBanner ? ' expand' : ''}`}>
        {showOnboardingBanner && (
          <NewUserOnboardingCard
            payments={payments}
            onClose={onHideOnboardingBanner}
            onFirstStepClose={onFirstStepClose}
            isFirstStep={showOnboardingBannerFirstStep}
            showInstantActivation={showInstantActivation}
            limitBreach={limitBreach}
          />
        )}
      </div>
    );
    /* Recommended product widget */
    const ProductRecommendationWidget = user.isProductRecommendationEnabled && (
      <ProductRecommendationnCard user={user} />
    );

    if (user.activation_form_milestone && user.activated) {
      return [ProductRecommendationWidget, onboardingCard];
    }
    return [onboardingCard, ProductRecommendationWidget];
  };

  onKnowMoreClick = () => {
    const { user, settlement_amount, openModal } = this.props;
    openModal({
      size: 'medium',
      component: <SettlementDetail user={user} settlementAmount={settlement_amount.data} />,
    });
    window.rzpAnalytics({
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
      showOnboardingBannerFirstStep,
      expandOnboardingBanner,
      payments,
      showOnboardingBanner,
      showInstantActivation,
      onHideOnboardingBanner,
      onFirstStepClose,
      onDatesChange,
      onFetchPayments,
      onExtraContentMount,
      settlement_amount,
      defaultPreset,
      keymetricsSectionTitle,
      paymentInsightsTitle,
      recentActivityTitle,
      trafficSectionTitle,
      merchantBalanceConfigs,
      lateAuthConfig,
      ondemand_restrictions,
      settleNowRestrictionMsg,
      limitBreach,
      isOnDemandDisabled,
      settlementConfig,
    } = this.props;

    const {
      data: { items },
    } = lateAuthConfig;

    const hasSecondaryBanner =
      showInstantActivation && config.config && !config.config.hasPersonalised;

    const nextSettlement = !settlement_amount.data.next_settlement_time;
    const { no_settlement } = settlement_amount.data;

    const attemptsLeft = ondemand_restrictions && ondemand_restrictions.data.attempts_left;
    const isOndemandRestrictionsLoading = ondemand_restrictions && ondemand_restrictions.loading;
    const settlableAmount = ondemand_restrictions && ondemand_restrictions.data.settlable_amount;
    const isSettleNowRestricted =
      ondemand_restrictions && (!attemptsLeft || !settlableAmount || isOndemandRestrictionsLoading);
    const checkIfSettlementDisabled =
      isSettleNowRestricted ||
      current_balance.loading ||
      current_balance.data.balance < 100 ||
      isOnDemandDisabled;
    let balance = current_balance.data.balance;
    let negativeBalanceClassName = '';
    const esOndemandSettlementEnabled = user.isFeatureEnabled('es_on_demand');
    const isOnTemporaryHold = settlementConfig.data?.config?.features?.hold?.status;
    const isOnHold =
      no_settlement?.on_hold || settlementConfig.data?.config?.features?.disable?.status;
    const isSettlementOnHold = isOnTemporaryHold || isOnHold;

    if (balance < 0) {
      balance = Math.abs(current_balance.data.balance);
      negativeBalanceClassName = 'negative-balance';
    }
    const ticketsRaisedByAgents = this.props.ticketsRaisedByAgents.filter((ticket) =>
      new Date(ticket.created_at).getSeconds(),
    );

    return (
      <div className="home-analytics-desktop">
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

          {/* needs clarification modal */}
          {this.state.showNcPopup &&
            user.needsClarification &&
            !this.props.user.isInstantActivationEnabled && (
              <NCModal
                isActivationFormFullView={this.props.user.isActivationFormFullView}
                onClose={this.onNcModalClose}
              />
            )}

          {!this.props.user.isInstantActivationEnabled &&
            !!this.props.user.locked &&
            this.props.user.activation_status === 'under_review' &&
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
          {this.isCaptureSettingsDefault(items) && user.instantActivation.isWhitelistFlow === true && (
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
              >
                here
              </Link>{' '}
              to configure your capture setting.
            </AnnouncementBanner>
          )}
          {this.showGSTOptOutFlow() === true && (
            <AnnouncementBanner
              title="GST Address Mismatch"
              theme="warning"
              canBeClosed={false}
              card_id="gst-address-mismatch-banner"
            >
              The business address you provided to Razorpay does not match with your address details
              on your GST certificate. On Jan 25, 2021, we will update your address to the same as
              your GST details.
              <Link
                className="Button--secondary Button scheduled-btn-act btn-border mt-4 gst-mismatch-banner-link"
                to="/profile#gst"
              >
                Review address
              </Link>
            </AnnouncementBanner>
          )}
          {current_balance.data.balance < 0 && (
            <AnnouncementBanner
              title="Add Funds"
              theme="warning"
              canBeClosed={true}
              card_id="negative-balance-add-funds-banner"
            >
              Your balance went into negative value. Add funds to avoid the transaction failures.{' '}
              <Link
                onClick={() => {
                  analyticsTrack({
                    objectName: 'banner',
                    actionName: 'clicked',
                    screen: 'home page',
                    properties: {
                      hyperlinkClicked: 'Add Funds',
                      title: 'Add Funds',
                      ...getCommonAnalyticsProperties(window.rzp_user),
                    },
                  });
                }}
                to="/addfunds"
                target="_blank"
              >
                {' '}
                Add Funds
              </Link>
            </AnnouncementBanner>
          )}

          {handleNegativeBalanceLimit(merchantBalanceConfigs, current_balance.data.balance) && (
            <AnnouncementBanner
              title="On Hold!"
              theme="danger"
              canBeClosed={true}
              card_id="on-hold-add-funds-banner"
            >
              Your current balance had reached the maximum negative limit. Transactions will start
              to fail now. Please add funds to avoid transaction failures.{' '}
              <Link
                onClick={() => {
                  analyticsTrack({
                    objectName: 'banner',
                    actionName: 'clicked',
                    screen: 'home page',
                    properties: {
                      hyperlinkClicked: 'Add Funds',
                      title: 'On Hold!',
                      ...getCommonAnalyticsProperties(window.rzp_user),
                    },
                  });
                }}
                to="/addfunds"
                target="_blank"
              >
                {' '}
                Add Funds
              </Link>
            </AnnouncementBanner>
          )}
          <ShowWhen additionalCondition={(usr) => usr.isAbcBannerEnabled}>
            <ABCBanner user={user} />
          </ShowWhen>
          <ShowWhen additionalCondition={(usr) => usr.isQrCodeEnable}>
            <BaseDashboardBanner
              title="QR Codes"
              bannerText="QR Codes Get unlimited branded QR and collect payments via UPI and cards"
              bannerId="OCT21-QR-GTM"
              cta1Text="Try Now"
              cta1ClickHandler={this.cta1ClickHandler}
              cta2Text="Learn More"
              cta2Link="https://razorpay.com/docs/qr-codes/"
              productName="dashboard_home"
            />
          </ShowWhen>
          <ShowWhen additionalCondition={(usr) => usr.isStartupCongratulationBannerEnabled}>
            <StartupCongratulationBanner user={user} />
          </ShowWhen>
          <DashboardBanner />
          <ShowWhen additionalCondition={(usr) => usr.isCrossBorderPaymentsCampaignEnabled}>
            <CrossBorderPaymentsBanner productName="CrossBorderPayment-Create" />
          </ShowWhen>
          <ShowWhen
            additionalCondition={(usr) =>
              usr.isNitroIciciBrandedCampaignEnabled ||
              usr.isNitroIciciRemarketingCampaignEnabled ||
              usr.isProjectNitroEnabled
            }
          >
            <NitroICICIBanner productName="home" />
          </ShowWhen>
          <ShowWhen additionalCondition={(usr) => usr.isNitroCCCampaignEnabled}>
            <NitroCCCampaign productName="home" />
          </ShowWhen>
          <ShowWhen
            additionalCondition={(usr) => usr.isNitroNitromidmarketRemarketingCampaignEnabled}
          >
            <NitroMMRemarketingBanner productName="home" />
          </ShowWhen>
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

          {user.isNeostoneFlowEnabled('neostone-tracker') && (
            <div className="nss-tracker-wrapper">
              <ErrorBoundary
                FallbackComponent={() => {
                  return null;
                }}
              >
                <NeoStoneTracker proceededBank={getXCAStatus(user).proceededBank} user={user} />
              </ErrorBoundary>
            </div>
          )}

          {this.props.can_refer ? <M2MBanner /> : null}

          {/* Announcement Banners End */}
          {/* TODO: Move announcement section to different file */}

          {user.canSwitchOnboardingCard ? (
            this.renderOnboardingAndRecommendationWidget()
          ) : (
            <>
              <div className={`v2-onboarding-card${expandOnboardingBanner ? ' expand' : ''}`}>
                {showOnboardingBanner && (
                  <NewUserOnboardingCard
                    payments={payments}
                    onClose={onHideOnboardingBanner}
                    onFirstStepClose={onFirstStepClose}
                    isFirstStep={showOnboardingBannerFirstStep}
                    showInstantActivation={showInstantActivation}
                    limitBreach={limitBreach}
                  />
                )}
              </div>

              {/* Recommended product widget */}
              {user.isProductRecommendationEnabled && <ProductRecommendationnCard user={user} />}
            </>
          )}

          {hasSecondaryBanner && (
            <div className="secondary-announcement-banner">
              <PersonaliseBanner track={trackPersonaliseBanner} />
            </div>
          )}
        </div>

        {/* <Sticky stickWhen={scrollAmountToStickHeader} stickAt={50}> */}
        <Header className="clearfix" title="" showMode={false}>
          <div id="analytics-daterange-picker" className="pull-left date-range-container">
            <DateRangePicker
              presets={dateRangePresets}
              onDatesChange={onDatesChange}
              defaultPreset={defaultPreset}
              onSelectPreset={trackPresetChange}
            />
          </div>
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
                          currency="INR"
                          className={negativeBalanceClassName}
                        />
                      )}
                    </span>
                    <br />
                    {isSettlementOnHold && (
                      <div className="text-right full-width no-margin">
                        {isOnHold
                          ? 'Your settlements have been put on hold.'
                          : 'Your settlements have been put on Temporary hold.'}
                        <span className="pr-5">
                          <i className="i i-info-circle" />
                          <Popover theme="dark" align="bottom">
                            <PopoverBody>
                              Your settlements are currently not being processed.
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
                    !user.isNewSettlementServiceEnabled &&
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
                            currency="INR"
                          />
                        </strong>
                        <span className="pr-5">will be settled on</span>
                        <Time
                          className="pr-5"
                          value={settlement_amount.data.next_settlement_time}
                          format="DD MMM YYYY, hh:mm:ss a"
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
            <div className="col-md-12">
              <EasterEgg extraClass="ftx-home-page" page="Home" />
              <div className="section-title payment-insights-title">
                {paymentInsightsTitle}&nbsp;
                <small>
                  <i className="i i-help" />
                  <Popover align="top">
                    <PopoverBody>
                      <p>
                        This graph helps you gain insights into your overall payments by seeing how
                        different payment methods stack up against each other in your revenue pool.
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
              <PaymentMethods
                startDate={startDate}
                endDate={endDate}
                mode={mode}
                analyticsFetch={analyticsFetch}
                sectionTitle={paymentInsightsTitle}
              />
            </div>
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
                    <Traffic
                      startDate={startDate}
                      endDate={endDate}
                      mode={mode}
                      analyticsFetch={analyticsFetch}
                      sectionTitle={trafficSectionTitle}
                    />
                  </div>
                </div>
              )}
              {!isAdmin && (
                <div className="activity-container">
                  <p className="content-title section-title">{recentActivityTitle}</p>
                  <div className="content">
                    <RecentActivity
                      startDate={startDate}
                      endDate={endDate}
                      sectionTitle={recentActivityTitle}
                      onFetchPayments={onFetchPayments}
                      user={this.props.user}
                      currentBalance={current_balance}
                      onSelect={this.showOndemandSettlementForm}
                    />
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
  internationalProductsStatus: state.config.internationalProductsStatus,
  limitBreach: state.home.limitBreach,
  settlementConfig: state.settlement.config,
  can_refer: state.merchantReferral.data.can_refer,
  referee: state.merchantReferral.data.referee,
  transactionAmount: state.transactionAmount.amount,
  ticketsRaisedByAgents: state.config.ticketsRaisedByAgents.data[1],
});

export default withRouter(
  connect(mapStateToProps, {
    openModal: fnOpenModal,
    fetchInternationalProductsStatus: fnFetchInternationalProductsStatus,
    ...NotificationActions,
    fetchUser,
    showKYCStatusModal,
    fetchEscalations: fnFetchEscalations,
    fetchSettlementConfig: fnFetchSettlementConfig,
    fetchBankAccountChangeStatus: fnFetchBankAccountChangeStatus,
    showProductsModal,
    ...EventActions,
  })(AnalyticsDesktop),
);
