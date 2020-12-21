import React, { Component, Fragment } from 'react';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';
import QueryString from 'query-string';

import Header from 'common/ui/Header';
import Amount from 'common/ui/Amount';
import Sticky from 'common/ui/Sticky';
import DateRangePicker from 'common/ui/DateRangePicker';

import NewUserOnboardingCard from 'merchant/containers/Home/OnboardingCard';
import KeyMetrics from 'merchant/containers/Home/KeyMetrics';
import PaymentMethods from 'merchant/containers/Home/PaymentMethods';
import RecentActivity from 'merchant/containers/Home/RecentActivity';
import Traffic from 'merchant/containers/Home/Traffic';
import Button from 'common/new-ui/Button';
import OndemandModal from 'merchant/views/Settlements/Settlements/components/Modals/OndemandModal';
import { openModal } from 'merchant_common/reducers/modals';
import Announcement from 'merchant/components/Announcements/Instant';
import PersonaliseBanner from 'merchant/components/Announcements/PersonaliseAccount';
import { trackPersonaliseBanner } from 'merchant/containers/Home/OnboardingCard/Instant/ga';
import OnboardingCard from 'v2/merchant/onboarding/mobile/Screens/Home';
import {
  trackPresetChange,
  trackSettlementsClick,
  trackSettleNow,
  EVENT_CATEGORY_DASHBOARD_HOME,
} from './ga';
import ShowWhen from 'merchant/components/ShowWhen';

@connect(
  (state) => ({
    windowWidth: state.app.windowWidth,
    user: state.session.user,
    config: state.config,
  }),
  { openModal },
)
class AnalyticsMobile extends Component {
  constructor(props) {
    super(props);
    this.showOndemandSettlementForm = this.showOndemandSettlementForm.bind(this);
  }

  showOndemandSettlementForm() {
    trackSettleNow();
    let balance = this.props.current_balance.data.balance;
    this.props.openModal({
      component: (
        <OndemandModal
          currentBalance={balance}
          eventCategory={EVENT_CATEGORY_DASHBOARD_HOME}
          fromWhere="Home"
        />
      ),
      size: 'small',
      disableClose: true,
    });
  }

  render() {
    const {
      config,
      current_balance,
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
      keymetricsSectionTitle,
      paymentInsightsTitle,
      recentActivityTitle,
      trafficSectionTitle,
      windowWidth,
    } = this.props;

    const hasSecondaryBanner =
      showInstantActivation && config.config && !config.config.hasPersonalised;
    const query = QueryString.parse(window.location.search);

    return (
      <div className="home-analytics-mobile">
        <div
          ref={(node) => onExtraContentMount(node)}
          className={`extra-content${showOnboardingBanner ? ' has-ob-banner' : ''}${
            !showOnboardingBanner && hasSecondaryBanner ? ' has-secondary-banner' : ''
          }`}
        >
          {showInstantActivation && !query.onboarding_v2 ? (
            <Announcement mode={mode} user={user} payments={payments} />
          ) : null}
          {!query.onboarding_v2 ? (
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

          {query.onboarding_v2 ? <OnboardingCard /> : null}
          {hasSecondaryBanner && (
            <div className="secondary-announcement-banner">
              <PersonaliseBanner track={trackPersonaliseBanner} />
            </div>
          )}
          <Header className="clearfix" title="" showMode={false}>
            <div className={`pull-left ${this.props.user.isOndemandSettlementEnabled && 'm-t'}`}>
              Balance:{' '}
              <b>
                {!current_balance.loading && typeof current_balance.data.balance === 'number' && (
                  <Amount value={current_balance.data.balance} currency={'INR'} />
                )}
              </b>
            </div>
            <div className="pull-right">
              {this.props.user.isOndemandSettlementEnabled &&
              this.props.user.isAllowedView('early_settlement') ? (
                <Button.Secondary
                  class="settle-btn"
                  onClick={this.showOndemandSettlementForm}
                  disabled={current_balance.loading || current_balance.data.balance < 100}
                >
                  Settle Now
                </Button.Secondary>
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
            sectionTitle={''}
            tabsMeta={tabsMeta}
            isAdmin={isAdmin}
            analyticsFetch={analyticsFetch}
            onFilterChange={onFilterChange}
            isMobile={true}
          />
          <p className="section-title">{paymentInsightsTitle}</p>
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
                sectionTitle={''}
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
