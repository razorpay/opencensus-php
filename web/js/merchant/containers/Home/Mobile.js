import React, { Component, Suspense } from 'react';
import Space from '@razorpay/blade-old/src/atoms/Space';
import Text from '@razorpay/blade-old/src/atoms/Text';
import View from '@razorpay/blade-old/src/atoms/View';
import LazyLoad from 'react-lazyload';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';

import Carousel from 'common/components/Carousel';
import { withI18Service } from 'common/i18';
import { withSplitzService } from 'common/splitz';
import Amount from 'common/ui/Amount';
import DashboardBanner from 'common/ui/DashboardBanner';
import DateRangePicker from 'common/ui/DateRangePicker';
import Header from 'common/ui/Header';
import Popover, { PopoverBody } from 'common/ui/Popover';
import PricingSubscriptionWrapper from 'common/ui/PricingSubscription';
import Sticky from 'common/ui/Sticky';
import { getFormattedAmountNew, checkHTML5APIvalidity } from 'common/utils/rzp-utils';
import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';
import Announcement from 'merchant/components/Announcements/Instant';
import RepaymentAnnouncment from 'merchant/components/Announcements/PaymentRecovery';
import PersonaliseBanner from 'merchant/components/Announcements/PersonaliseAccount';
import SupportRequest from 'merchant/components/Announcements/SupportRequest';
import EasterEgg from 'merchant/components/EasterEgg';
import M2MBanner from 'merchant/components/M2M/M2MBanner';
import ShowWhen from 'merchant/components/ShowWhen';
import { isJKOfflineMerchant } from 'merchant/components/Sidebar/helpers';
import KeyMetrics from 'merchant/containers/Home/KeyMetrics';
import NewUserOnboardingCard from 'merchant/containers/Home/OnboardingCard';
import { trackPersonaliseBanner } from 'merchant/containers/Home/OnboardingCard/Instant/ga';
import PaymentMethods from 'merchant/containers/Home/PaymentMethods';
import ProductOnboardingCard from 'merchant/containers/Home/ProductOnboardingCard';
import ProductRecommendationnCard from 'merchant/containers/Home/ProductRecommendationnCard';
import RecentActivity from 'merchant/containers/Home/RecentActivity';
import Traffic from 'merchant/containers/Home/Traffic';
import { fetchCarouselBanner as fetchCarouselBannerProp } from 'merchant/reducers/growthService';
import lazy from 'merchant/routes/LazyLoader';
import WebsiteComplianceNudge from 'merchant/views/Account/WebsiteAppDetails/Nudge';
import WebsiteCompliancePrompt from 'merchant/views/Account/WebsiteAppDetails/Prompt.mobile';
import {
  shouldShowWebsiteComplianceModal,
  isPolicyWizardV2Enabled,
} from 'merchant/views/Account/WebsiteAppDetails/utils';
import { getSettlementStatus } from 'merchant/views/Capital/utils';
import { OnDemandModalEntry } from 'merchant/views/Settlements/Settlements/components/Modals/OnDemandModalEntry';
import SettleNowButton from 'merchant/views/Settlements/Settlements/components/SettleNowButton';
import { STATUSES } from 'merchant/views/TicketSupport/utils';
import OnboardingCard from 'merchant/views/onboarding/mobile/Screens/Home';
import { openModal } from 'merchant_common/reducers/modals';

import DateRangeTooltip from './DateRangeTooltip';
import DiwaliReportBanner from './DiwaliReportBanner';
import FestivalThemeBanner from './FestivalThemeBanner';
import {
  trackPresetChange,
  trackSettlementsClick,
  trackSettleNow,
  selfServeSettleTracking,
  EVENT_CATEGORY_DASHBOARD_HOME,
} from './ga';
import { IsOutsideDateRangeForHPAnalytics } from './utils';
import { Box } from '@razorpay/blade/components';
import { isEligibleForReKyc } from 'merchant/components/ReKycStatusAlerts/utils';

const TerminalStatus = lazy(() =>
  import(
    /* webpackChunkName: 'terminal-status-banner' */ 'merchant/components/Announcements/TerminalStatus'
  ),
);

const ReKycStatusBanner = lazy(() =>
  import(/* webpackChunkName: 'rekycStatusBanner' */ 'merchant/components/ReKycStatusAlerts').then(
    (module) => ({ default: module.ReKycStatusBanner }),
  ),
);

@withI18Service
@connect(
  (state) => ({
    windowWidth: state.app.windowWidth,
    activationData: state.websiteCompliance.activationData,
    websiteSectionDetailsData: state.websiteCompliance.websiteSectionDetailsData,
    websiteComplianceModalVisibility: state.websiteCompliance.bannerAndModalVisibility,
    user: state.session.user,
    config: state.config,
    can_refer: state.merchantReferral.data.can_refer,
    referee: state.merchantReferral.data.referee,
    transactionAmount: state.transactionAmount.amount,
    ticketsRaisedByAgents: state.config.ticketsRaisedByAgents.data[1],
    bannerCarouselData: state?.growthService?.banner_carousel_items,
    org: state.session.org,
  }),
  {
    openModal,
    fetchCarouselBanner: fetchCarouselBannerProp,
  },
)
class AnalyticsMobile extends Component {
  state = {
    settlementExists: true,
    isWebsiteComplianceModalShown: false,
    enableSticky: false,
  };

  constructor(props) {
    super(props);
    this.showOndemandSettlementForm = this.showOndemandSettlementForm.bind(this);
    this.observerRef = null;
    this.containerRef = React.createRef(null);
  }

  componentDidMount() {
    this.checkIfFirstEverSettlement();
    const holdFeature = false; // TODO: remove it once feature is live for prod
    if (holdFeature) this?.props?.fetchCarouselBanner({ fromWhere: window.location.pathname });
    if (
      'IntersectionObserver' in window &&
      'IntersectionObserverEntry' in window &&
      this.containerRef
    ) {
      this.observerRef = new IntersectionObserver(
        ([entry]) => {
          this.setState({
            enableSticky: entry?.boundingClientRect?.top < 100,
          });
        },
        {
          rootMargin: '-100px',
          threshold: [0.1, 1],
        },
      );
      this.observerRef.observe(this.containerRef.current);
    }
  }

  componentWillUnmount() {
    if (this.observerRef) {
      this.observerRef.disconnect();
    }
  }

  checkIfFirstEverSettlement = (callbackSettlementStatus) => {
    const settlementStatus = getSettlementStatus(this.props.user.current, callbackSettlementStatus);
    this.setState({
      settlementExists:
        settlementStatus === 'disableAnimation' || settlementStatus === 'disableAnimationOnReload'
          ? true
          : settlementStatus,
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
        <OnDemandModalEntry
          animatedSettlemnetBtn={!this.state.settlementExists && esOndemandSettlementEnabled}
          currentBalance={balance}
          settlableAmount={settlableAmount}
          eventCategory={EVENT_CATEGORY_DASHBOARD_HOME}
          fromWhere="Home"
          checkIfFirstEverSettlement={this.checkIfFirstEverSettlement}
          goBackToInitialModalView={this.showOndemandSettlementForm}
        />
      ),
      isNew: true,
      size: 'small',
      disableClose: true,
    });
  }

  renderOnboardingWidgets = () => {
    const { user } = this.props;

    const ProductLedOnboardingWidget =
      user?.isProductLedOnboardingRZP && !!user?.activated ? <ProductOnboardingCard /> : null;

    const ProductRecommendationWidget = user.isProductRecommendationEnabled && (
      <ProductRecommendationnCard user={user} />
    );
    return ProductLedOnboardingWidget ?? ProductRecommendationWidget;
  };

  renderWebsiteCompliancePrompt = () => {
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
          component: <WebsiteCompliancePrompt screen="Home page" />,
          size: 'small',
        });
      }
    }
  };

  render() {
    const { settlementExists } = this.state;
    const {
      config,
      current_balance,
      ondemand_restrictions,
      onExtraContentMount,
      isAdmin,
      onFetchPayments,
      dateRangePresets,
      onDatesChange,
      defaultPreset,
      startDate,
      endDate,
      oldestTransactionDate,
      mode,
      user,
      showGroupingByPtfm,
      tabsMeta,
      analyticsFetch,
      onFilterChange,
      expandOnboardingBanner,
      showOnboardingBanner,
      showInstantActivation,
      payments,
      onHideOnboardingBanner,
      onFirstStepClose,
      showOnboardingBannerFirstStep,
      paymentInsightsTitle,
      recentActivityTitle,
      trafficSectionTitle,
      windowWidth,
      settleNowRestrictionMsg,
      isOnDemandDisabled,
      bannerCarouselData: { banner_carousel_items = [] } = {},
      i18: { isConfigTagEnabled },
    } = this.props;

    const hasSecondaryBanner =
      showInstantActivation && config.config && !config.config.hasPersonalised;

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

    const esOndemandSettlementEnabled = user.isFeatureEnabled('es_on_demand');
    const ticketsRaisedByAgents = this.props.ticketsRaisedByAgents.filter(
      (ticket) =>
        Boolean(new Date(ticket.created_at).getSeconds()) &&
        STATUSES[ticket?.status] === 'AWAITING_YOUR_REPLY',
    );
    let carouselItem = [];
    if (banner_carousel_items.length) carouselItem = [...banner_carousel_items];
    const isJkOrg = isJKOfflineMerchant(this.props.org, this.props.user);
    const shouldShowReKycBanner = isEligibleForReKyc(this.props.splitz, user);

    return (
      <div className="home-analytics-mobile">
        <PricingSubscriptionWrapper />
        <Space padding={[2.5, 1, 0, 2]}>
          <Text size="large" weight="bold" color="shade.950">
            Welcome to your dashboard, {user.contact_name}!
          </Text>
        </Space>
        {checkHTML5APIvalidity() && (
          <AnnouncementBanner title="Outdated Browser" theme="warning">
            Please update your web browser. We recommend you to download the latest version of
            Google Chrome, Edge, Safari, Firefox.
          </AnnouncementBanner>
        )}
        <DashboardBanner />
        <div
          ref={(node) => onExtraContentMount(node)}
          className={`extra-content${showOnboardingBanner ? ' has-ob-banner' : ''}${
            !showOnboardingBanner && hasSecondaryBanner ? ' has-secondary-banner' : ''
          }`}
        >
          <Box display="flex" gap="spacing.4" flexDirection="column">
            {shouldShowReKycBanner ? (
              <Suspense fallback={null}>
                <ReKycStatusBanner />
              </Suspense>
            ) : null}
            <FestivalThemeBanner isMobileView={true} />
            <DiwaliReportBanner isMobileView={true} />
          </Box>
          <ShowWhen
            additionalCondition={() =>
              !isConfigTagEnabled('product_recommendations_kyc.product_recommendation_kyc') &&
              !isJkOrg
            }
          >
            {ticketsRaisedByAgents.length && user.isMobileSignupCareActive ? (
              <SupportRequest tickets={ticketsRaisedByAgents} />
            ) : null}

            {showInstantActivation && !user.isOnboardingV2Enabled ? (
              <Announcement mode={mode} user={user} payments={payments} />
            ) : null}
            {user.isPaymentsEnabled && this.props.referee?.status === 'signup' ? (
              <AnnouncementBanner
                title="Unlock Pending Credits"
                theme="warning"
                card_id="merchant_referral"
              >
                Accept payments of minimum ₹2,000 to receive{' '}
                {getFormattedAmountNew(this.props.referee.referral_amount, true)} in collections -
                100% FREE*
              </AnnouncementBanner>
            ) : null}
            {mode === 'live' && user.showTerminalStatusBanner ? (
              <Suspense fallback={null}>
                <TerminalStatus isMobile user={user} />
              </Suspense>
            ) : null}
            <WebsiteComplianceNudge screen="Home page" />
            {user.isWebsiteComplianceFlowEnabled &&
              this.props.canShowL1ActivationModals &&
              user?.website_policy_verification_status !== 'verified' && // if website policy verification status is verified don't show modal
              this.renderWebsiteCompliancePrompt()}
            {carouselItem.length ? (
              <Carousel enableLazy minHeight={200} carouselItem={carouselItem} />
            ) : null}
            {!user.isOnboardingV2Enabled ? (
              <div className={`v2-onboarding-card${expandOnboardingBanner ? ' expand' : ''}`}>
                {showOnboardingBanner && (
                  <NewUserOnboardingCard
                    payments={payments}
                    onClose={onHideOnboardingBanner}
                    onFirstStepClose={onFirstStepClose}
                    isFirstStep={showOnboardingBannerFirstStep}
                    showInstantActivation={showInstantActivation}
                  />
                )}
              </div>
            ) : null}

            {this.props.can_refer ? (
              <Space margin={[1, 2, 2, 2]}>
                <View>
                  <M2MBanner />
                </View>
              </Space>
            ) : null}

            {user.isRepaymentBannerEnabled && <RepaymentAnnouncment userId={user.current} />}
            {user.isOnboardingV2Enabled ? <OnboardingCard referee={this.props.referee} /> : null}
            {hasSecondaryBanner && (
              <div className="secondary-announcement-banner">
                <PersonaliseBanner track={trackPersonaliseBanner} />
              </div>
            )}

            {this.renderOnboardingWidgets()}
            <ShowWhen additionalCondition={() => !isJkOrg}>
              <Header className="clearfix" title="" showMode={false}>
                <div
                  className={`pull-left ${this.props.user.isOndemandSettlementEnabled && 'm-t'}`}
                >
                  Balance:{' '}
                  <b>
                    {!current_balance.loading && (
                      <Amount
                        value={current_balance.data.balance}
                        currency={user.merchant.currency}
                      />
                    )}
                  </b>
                </div>
                <div className="pull-right">
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
                          parentQuerySelector=".settle-btn .settle-now--mobile"
                          theme="dark"
                        >
                          <PopoverBody>{settleNowRestrictionMsg}</PopoverBody>
                        </Popover>
                      )}
                    </div>
                  ) : (
                    <Link className="pull-right btn-text" to="/settlements">
                      <span className="text-no-wrap" onClick={trackSettlementsClick}>
                        View Settlements <i className="i i-chevron-right" />
                      </span>
                    </Link>
                  )}
                </div>
              </Header>
            </ShowWhen>
          </ShowWhen>
          {!isAdmin && (
            <div className="content">
              <p className="section-title">{recentActivityTitle}</p>
              {/* For J&K Bank this is the first component that is shown hence lazy load is not needed */}
              {isJkOrg ? (
                <RecentActivity
                  sectionTitle={recentActivityTitle}
                  onFetchPayments={onFetchPayments}
                  isTabletResolution={true}
                  user={this.props.user}
                  currentBalance={current_balance}
                  onSelect={this.showOndemandSettlementForm}
                />
              ) : (
                <LazyLoad height={100} offset={50} once>
                  <RecentActivity
                    sectionTitle={recentActivityTitle}
                    onFetchPayments={onFetchPayments}
                    isTabletResolution={true}
                    user={this.props.user}
                    currentBalance={current_balance}
                    onSelect={this.showOndemandSettlementForm}
                  />
                </LazyLoad>
              )}
            </div>
          )}
        </div>

        <Sticky
          stickAt={50}
          {...(this.state.enableSticky ? { stickWhen: 0 } : { disableSticky: true })}
        >
          <Header className="clearfix" title="" showMode={false}>
            <div
              id="analytics-daterange-picker"
              className="date-range-container date-range-tooltip-container"
            >
              <DateRangePicker
                presets={dateRangePresets}
                onDatesChange={onDatesChange}
                defaultPreset={defaultPreset}
                onSelectPreset={trackPresetChange}
                numberOfMonths={1}
                horizontalMargin={
                  // adjusting the right position of datepicker so that
                  // it does not overflow, for screen resolution <= 424
                  // presets are hidden , so not adjusting the DRP
                  windowWidth < 530
                    ? windowWidth > 424
                      ? 530 - windowWidth
                      : windowWidth > 360
                      ? 40
                      : 57
                    : 0
                }
                isOutsideRange={IsOutsideDateRangeForHPAnalytics}
              />
              <DateRangeTooltip />
            </div>
          </Header>
        </Sticky>

        <div className="dashboard">
          <div ref={this.containerRef} />
          <LazyLoad height={100} offset={50} once>
            <KeyMetrics
              startDate={startDate}
              endDate={endDate}
              oldestTransactionDate={oldestTransactionDate}
              mode={mode}
              showGroupingByPtfm={showGroupingByPtfm}
              sectionTitle=""
              tabsMeta={tabsMeta}
              isAdmin={isAdmin}
              analyticsFetch={analyticsFetch}
              onFilterChange={onFilterChange}
              isMobile={true}
            />
          </LazyLoad>
          <p className="section-title">{paymentInsightsTitle}</p>
          <LazyLoad height={100} offset={50} once>
            <>
              <ShowWhen
                additionalCondition={() =>
                  !isConfigTagEnabled('product_recommendations_kyc.product_recommendation_kyc')
                }
              >
                <EasterEgg extraClass="ftx-home-page" page="Home" />
              </ShowWhen>

              <PaymentMethods
                startDate={startDate}
                endDate={endDate}
                mode={mode}
                analyticsFetch={analyticsFetch}
                sectionTitle={paymentInsightsTitle}
                isMobile={true}
              />
            </>
          </LazyLoad>
          {showGroupingByPtfm && (
            <React.Fragment>
              <p className="section-title">{trafficSectionTitle}</p>
              <LazyLoad height={100} offset={50} once>
                <Traffic
                  startDate={startDate}
                  endDate={endDate}
                  mode={mode}
                  analyticsFetch={analyticsFetch}
                  sectionTitle=""
                  isMobile={true}
                />
              </LazyLoad>
            </React.Fragment>
          )}
        </div>
      </div>
    );
  }
}

export default withSplitzService(AnalyticsMobile);
