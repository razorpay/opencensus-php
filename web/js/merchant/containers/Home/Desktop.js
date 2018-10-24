import React, { Component } from 'react';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';

import Header from 'rzp/ui/Header';
import Amount from 'rzp/ui/Amount';
import Banner from 'rzp/ui/Banner';
import Sticky from 'rzp/ui/Sticky';
import Group, { GroupItem } from 'rzp/ui/Group';
import DateRangePicker, { customRangeText } from 'rzp/ui/DateRangePicker';
import Popover, { PopoverTitle, PopoverBody } from 'rzp/ui/Popover';
import LocalStorageService from 'rzp/utils/localStorage';

import NewUserOnboardingCard from 'merchant/containers/Home/OnboardingCard';
import KeyMetrics from 'merchant/containers/Home/KeyMetrics';
import PaymentMethods from 'merchant/containers/Home/PaymentMethods';
import Traffic from 'merchant/containers/Home/Traffic';
import RecentActivity from 'merchant/containers/Home/RecentActivity';
import GenericPanel, { PanelBody } from 'merchant/components/Home/GenericPanel';
import { showOrHideTour } from 'merchant/modules/session';
import Announcement from 'merchant/components/Announcements/Instant';
import PersonaliseBanner from 'merchant/components/Announcements/PersonaliseAccount';
import { EarlySettlementAnnouncement } from 'merchant/components/Announcements';
import Button from 'component/Button';
import OndemandModal from 'merchant/containers/Settlements/OndemandModal';
import { openModal } from 'rzp/modules/modals';

import {
  trackPresetChange,
  trackSettlementsClick,
  trackPlatformAnalyticsHidden,
  trackViewTour,
  trackSettleNow,
} from './ga';

@connect(state => ({ user: state.session.user, config: state.config }), {
  showOrHideTour,
  openModal,
})
class AnalyticsDesktop extends Component {
  constructor(props) {
    super(props);

    const { isAdmin, mode } = props;

    this.state = {
      hasNewAnalyticsTour:
        !isAdmin &&
        mode === 'live' &&
        !LocalStorageService.getItem('hide_new_analytics_banner'),
      dismissNewAnalyticsBanner: false, // used for transition
    };

    this.onShowTour = this.onShowTour.bind(this);
    this.onHideNewAnalyticsBanner = this.onHideNewAnalyticsBanner.bind(this);
    this.showOndemandSettlementForm = this.showOndemandSettlementForm.bind(
      this
    );
  }

  onShowTour() {
    this.onHideNewAnalyticsBanner(() => {
      this.props.setScrollAmountToStickHeader();
      this.props.showOrHideTour(true);
    });

    trackViewTour();
  }

  onHideNewAnalyticsBanner(cb) {
    LocalStorageService.setItem('hide_new_analytics_banner', true);

    this.setState(
      {
        dismissNewAnalyticsBanner: true,
      },
      () => {
        window.setTimeout(() => {
          this.setState(
            {
              dismissNewAnalyticsBanner: false,
              hasNewAnalyticsTour: false,
            },
            () => {
              typeof cb === 'function' && cb();
            }
          );
        }, 500); // let the trasition to hide banner complete
      }
    );
  }

  showOndemandSettlementForm() {
    trackSettleNow();
    let balance = this.props.current_balance.data.balance;
    this.props.openModal({
      component: <OndemandModal currentBalance={balance} fromWhere="Home" />,
      size: 'small',
    });
  }

  render() {
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
      scrollAmountToStickHeader,
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

      defaultPreset,
      keymetricsSectionTitle,
      paymentInsightsTitle,
      recentActivityTitle,
      trafficSectionTitle,
    } = this.props;

    const { hasNewAnalyticsTour, dismissNewAnalyticsBanner } = this.state;

    const { onShowTour, onHideNewAnalyticsBanner } = this;

    const hasSecondaryBanner =
      showInstantActivation &&
      !config.loading &&
      config.config &&
      !config.config.hasPersonalised;

    return (
      <div className="home-analytics-desktop">
        <div
          ref={node => onExtraContentMount(node)}
          className={`extra-content${
            showOnboardingBanner ? ' has-ob-banner' : ''
          }${
            !showOnboardingBanner && hasSecondaryBanner
              ? ' has-secondary-banner'
              : ''
          }`}
        >
          {!isAdmin && (
            <div>
              {hasNewAnalyticsTour && (
                <div
                  className={`v2-tour-banner${
                    dismissNewAnalyticsBanner ? ' dismiss' : ''
                  }`}
                >
                  <div className="banner-icon">
                    <i className="i i-loudspeaker" />
                  </div>
                  <div className="banner-content">
                    <Banner cta="View Tour" ctaOnClick={onShowTour}>
                      <span>
                        Take a quick tour to learn how to use dashboard
                        analytics effectively.
                      </span>
                    </Banner>
                  </div>
                  <div className="banner-close">
                    <a
                      className="banner-close-icon"
                      onClick={onHideNewAnalyticsBanner}
                    >
                      <i className="i i-close" />
                    </a>
                  </div>
                </div>
              )}
            </div>
          )}

          {showInstantActivation && (
            <Announcement mode={mode} user={user} payments={payments} />
          )}

          <EarlySettlementAnnouncement
            withTour={hasNewAnalyticsTour}
            from="Home-Desktop"
            marginBottom={!showOnboardingBanner}
          />

          <div
            className={`v2-onboarding-card${
              expandOnboardingBanner ? ' expand' : ''
            }`}
          >
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

          {hasSecondaryBanner && (
            <div className="secondary-announcement-banner">
              <PersonaliseBanner />
            </div>
          )}
        </div>
        <Sticky stickWhen={scrollAmountToStickHeader} stickAt={50}>
          <Header className="clearfix" title="" showMode={false}>
            <div
              id="analytics-daterange-picker"
              className="pull-left date-range-container"
            >
              <DateRangePicker
                presets={dateRangePresets}
                onDatesChange={onDatesChange}
                defaultPreset={defaultPreset}
                onSelectPreset={trackPresetChange}
              />
            </div>
            <div
              className={`pull-right ${
                this.props.user.isOndemandSettlementEnabled
                  ? 'ondemand-enabled'
                  : ''
              }`}
            >
              <Group>
                <GroupItem>
                  <span className="balance-amount">
                    Current Balance:{' '}
                    {!current_balance.loading && (
                      <Amount value={current_balance.data.balance} />
                    )}
                  </span>
                </GroupItem>
                <GroupItem>
                  {this.props.user.isOndemandSettlementEnabled ? (
                    <Button.Secondary
                      class="settle-btn"
                      onClick={this.showOndemandSettlementForm}
                      disabled={
                        current_balance.loading ||
                        current_balance.data.balance < 100
                      }
                    >
                      Settle Now
                    </Button.Secondary>
                  ) : (
                    <Link className="pull-right" to="/settlements">
                      <span
                        className="text-no-wrap"
                        onClick={trackSettlementsClick}
                      >
                        View Settlements
                      </span>
                    </Link>
                  )}
                </GroupItem>
              </Group>
            </div>
          </Header>
        </Sticky>

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
              <div className="section-title payment-insights-title">
                {paymentInsightsTitle}&nbsp;
                <small>
                  <i class="i i-help" />
                  <Popover align="top">
                    <PopoverBody>
                      <p>
                        This graph helps you gain insights into your overall
                        payments by seeing how different payment methods stack
                        up against each other in your revenue pool.
                      </p>
                      <div>
                        <span className="popover-highlight">Click tiles</span>{' '}
                        to drill-down into the hierarchy.
                      </div>
                      <div>
                        <span className="popover-highlight">Hover</span> to view
                        information for smaller tiles.
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
                  <p className="content-title section-title">
                    {trafficSectionTitle}
                  </p>
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
                  <p className="content-title section-title">
                    {recentActivityTitle}
                  </p>
                  <div className="content">
                    <RecentActivity
                      sectionTitle={recentActivityTitle}
                      onFetchPayments={onFetchPayments}
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

export default AnalyticsDesktop;
