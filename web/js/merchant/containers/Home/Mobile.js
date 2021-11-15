import React, { Component } from 'react';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';

import Header from 'common/ui/Header';
import Amount from 'common/ui/Amount';
import Sticky from 'common/ui/Sticky';
import DateRangePicker from 'common/ui/DateRangePicker';
import Popover, { PopoverBody } from 'common/ui/Popover';

import NewUserOnboardingCard from 'merchant/containers/Home/OnboardingCard';
import ProductRecommendationnCard from 'merchant/containers/Home/ProductRecommendationnCard';
import KeyMetrics from 'merchant/containers/Home/KeyMetrics';
import PaymentMethods from 'merchant/containers/Home/PaymentMethods';
import RecentActivity from 'merchant/containers/Home/RecentActivity';
import Traffic from 'merchant/containers/Home/Traffic';
import OndemandModal from 'merchant/views/Settlements/Settlements/components/Modals/OndemandModal';
import { openModal } from 'merchant_common/reducers/modals';
import Announcement from 'merchant/components/Announcements/Instant';
import PersonaliseBanner from 'merchant/components/Announcements/PersonaliseAccount';
import { trackPersonaliseBanner } from 'merchant/containers/Home/OnboardingCard/Instant/ga';
import OnboardingCard from 'merchant/views/onboarding/mobile/Screens/Home';
import {
  trackPresetChange,
  trackSettlementsClick,
  trackSettleNow,
  EVENT_CATEGORY_DASHBOARD_HOME,
} from './ga';
import { getSettlementStatus } from 'merchant/views/Capital/utils';
import SettleNowButton from 'merchant/views/Settlements/Settlements/components/SettleNowButton';
import EasterEgg from 'merchant/components/EasterEgg';

@connect(
  (state) => ({
    windowWidth: state.app.windowWidth,
    user: state.session.user,
    config: state.config,
  }),
  { openModal },
)
class AnalyticsMobile extends Component {
  state = {
    settlementExists: true,
  };

  constructor(props) {
    super(props);
    this.showOndemandSettlementForm = this.showOndemandSettlementForm.bind(this);
  }

  componentDidMount() {
    this.checkIfFirstEverSettlement();
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

  renderRecommendationWidget = () => {
    const { user } = this.props;
    const ProductRecommendationWidget = user.isProductRecommendationEnabled && (
      <ProductRecommendationnCard user={user} />
    );
    return ProductRecommendationWidget;
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
      scrollAmountToStickHeader,
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
    } = this.props;

    const hasSecondaryBanner =
      showInstantActivation && config.config && !config.config.hasPersonalised;

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
    const esOndemandSettlementEnabled = user.isFeatureEnabled('es_on_demand');

    return (
      <div className="home-analytics-mobile">
        <div
          ref={(node) => onExtraContentMount(node)}
          className={`extra-content${showOnboardingBanner ? ' has-ob-banner' : ''}${
            !showOnboardingBanner && hasSecondaryBanner ? ' has-secondary-banner' : ''
          }`}
        >
          {showInstantActivation && !user.isOnboardingV2Enabled ? (
            <Announcement mode={mode} user={user} payments={payments} />
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
          {user.isOnboardingV2Enabled ? <OnboardingCard /> : null}
          {hasSecondaryBanner && (
            <div className="secondary-announcement-banner">
              <PersonaliseBanner track={trackPersonaliseBanner} />
            </div>
          )}
          {this.renderRecommendationWidget()}
          <Header className="clearfix" title="" showMode={false}>
            <div className={`pull-left ${this.props.user.isOndemandSettlementEnabled && 'm-t'}`}>
              Balance:{' '}
              <b>
                {!current_balance.loading && typeof current_balance.data.balance === 'number' && (
                  <Amount value={current_balance.data.balance} currency="INR" />
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
                <Link className="pull-right" to="/settlements">
                  <span className="text-no-wrap" onClick={trackSettlementsClick}>
                    View Settlements <i className="i i-chevron-right" />
                  </span>
                </Link>
              )}
            </div>
          </Header>
          {!isAdmin && (
            <div className="content">
              <p className="section-title">{recentActivityTitle}</p>
              <RecentActivity
                sectionTitle={recentActivityTitle}
                onFetchPayments={onFetchPayments}
                isTabletResolution={true}
                user={this.props.user}
                currentBalance={current_balance}
                onSelect={this.showOndemandSettlementForm}
              />
            </div>
          )}
        </div>
        <Sticky stickWhen={scrollAmountToStickHeader} stickAt={50}>
          <Header className="clearfix" title="" showMode={false}>
            <div id="analytics-daterange-picker" className="date-range-container">
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
              />
            </div>
          </Header>
        </Sticky>
        <div className="dashboard">
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
          <p className="section-title">{paymentInsightsTitle}</p>
          <EasterEgg extraClass="ftx-home-page" page="Home" />
          <PaymentMethods
            startDate={startDate}
            endDate={endDate}
            mode={mode}
            analyticsFetch={analyticsFetch}
            sectionTitle={paymentInsightsTitle}
            isMobile={true}
          />
          {showGroupingByPtfm && (
            <React.Fragment>
              <p className="section-title">{trafficSectionTitle}</p>
              <Traffic
                startDate={startDate}
                endDate={endDate}
                mode={mode}
                analyticsFetch={analyticsFetch}
                sectionTitle=""
                isMobile={true}
              />
            </React.Fragment>
          )}
        </div>
      </div>
    );
  }
}

export default AnalyticsMobile;
