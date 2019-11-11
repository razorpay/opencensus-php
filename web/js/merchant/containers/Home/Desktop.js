import React, { Component } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router';
import { Link } from 'react-router-dom';

import Header from 'rzp/ui/Header';
import Amount from 'rzp/ui/Amount';
import Sticky from 'rzp/ui/Sticky';
import Group, { GroupItem } from 'rzp/ui/Group';
import DateRangePicker, { customRangeText } from 'rzp/ui/DateRangePicker';
import Popover, { PopoverTitle, PopoverBody } from 'rzp/ui/Popover';
import ShowWhen from 'merchant/components/ShowWhen';
import LocalStorageService from 'rzp/utils/localStorage';
import NewUserOnboardingCard from 'merchant/containers/Home/OnboardingCard';
import KeyMetrics from 'merchant/containers/Home/KeyMetrics';
import PaymentMethods from 'merchant/containers/Home/PaymentMethods';
import Traffic from 'merchant/containers/Home/Traffic';
import RecentActivity from 'merchant/containers/Home/RecentActivity';
import GenericPanel, { PanelBody } from 'merchant/components/Home/GenericPanel';
import Announcement from 'merchant/components/Announcements/Instant';
import CapitalAnnouncement from 'merchant/components/Announcements/Capital';
import CreditPullAnnouncement from 'merchant/components/Announcements/CreditPull';
import PersonaliseBanner from 'merchant/components/Announcements/PersonaliseAccount';
import Button from 'component/Button';
import OndemandModal from 'merchant/containers/Settlements/OndemandModal';
import { openModal } from 'rzp/modules/modals';
import { trackPersonaliseBanner } from 'merchant/containers/Home/OnboardingCard/Instant/ga';
import CreditPullModal from 'merchant/containers/CreditPull/CreditPullModal';

import {
  trackPresetChange,
  trackSettlementsClick,
  trackPlatformAnalyticsHidden,
  trackViewTour,
  trackSettleNow,
} from './ga';

@withRouter
@connect(state => ({ user: state.session.user, config: state.config }), {
  openModal,
})
class AnalyticsDesktop extends Component {
  constructor(props) {
    super(props);

    const { isAdmin, mode } = props;

    this.showOndemandSettlementForm = this.showOndemandSettlementForm.bind(
      this
    );
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

  resetHash = () => {
    this.props.history.push({
      pathname: this.props.history.location.pathname,
      hash: '',
    });
  };

  showOndemandSettlementForm() {
    trackSettleNow();
    let balance = this.props.current_balance.data.balance;
    this.props.openModal({
      component: <OndemandModal currentBalance={balance} fromWhere="Home" />,
      size: 'small',
      disableClose: true,
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

    const hasSecondaryBanner =
      showInstantActivation && config.config && !config.config.hasPersonalised;
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
          {showInstantActivation && (
            <Announcement mode={mode} user={user} payments={payments} />
          )}

          {user.isCreditPullEnabled && (
            <ShowWhen myRole="owner">
              <CreditPullAnnouncement />
            </ShowWhen>
          )}

          {/* capital banner*/}
          {user.isCapitalBannerEnabled && (
            <CapitalAnnouncement userId={user.current} />
          )}

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
              <PersonaliseBanner track={trackPersonaliseBanner} />
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
                {this.props.user.isOrgAllowedFunctionality(
                  'current_balance'
                ) && (
                  <GroupItem>
                    <span className="balance-amount">
                      Current Balance:{' '}
                      {!current_balance.loading && (
                        <Amount
                          value={current_balance.data.balance}
                          currency={'INR'}
                        />
                      )}
                    </span>
                  </GroupItem>
                )}
                <GroupItem>
                  {this.props.user.isOndemandSettlementEnabled ? (
                    <ShowWhen myRole="owner admin finance">
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
                    </ShowWhen>
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

export default AnalyticsDesktop;
