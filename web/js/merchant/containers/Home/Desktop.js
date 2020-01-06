import React, { Component } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router';
import { Link } from 'react-router-dom';

import Header from 'common/ui/Header';
import Amount from 'common/ui/Amount';
import Sticky from 'common/ui/Sticky';
import Group, { GroupItem } from 'common/ui/Group';
import DateRangePicker from 'common/ui/DateRangePicker';
import Popover, { PopoverBody } from 'common/ui/Popover';
import ShowWhen from 'merchant/components/ShowWhen';

import NewUserOnboardingCard from 'merchant/containers/Home/OnboardingCard';
import KeyMetrics from 'merchant/containers/Home/KeyMetrics';
import PaymentMethods from 'merchant/containers/Home/PaymentMethods';
import Traffic from 'merchant/containers/Home/Traffic';
import RecentActivity from 'merchant/containers/Home/RecentActivity';
import Announcement from 'merchant/components/Announcements/Instant';
import NPSAnnouncement from 'merchant/components/Announcements/NPSAnnouncement';
import CapitalAnnouncement from 'merchant/components/Announcements/Capital';
import PersonaliseBanner from 'merchant/components/Announcements/PersonaliseAccount';
import Button from 'common/new-ui/Button';
import OndemandModal from 'merchant/views/Settlements/components/Modals/OndemandModal';
import { openModal } from 'merchant_common/reducers/modals';
import { trackPersonaliseBanner } from 'merchant/containers/Home/OnboardingCard/Instant/ga';
import CreditPullModal from 'merchant/containers/CreditPull/CreditPullModal';
import OnHoldBanner from 'common/ui/OnHoldBanner';
import SettlementDetail from 'merchant/views/Settlements/components/SettlementDetail';

import { trackPresetChange, trackSettlementsClick, trackSettleNow } from './ga';
import Time from 'common/ui/Time';

@withRouter
@connect(state => ({ user: state.session.user, config: state.config }), {
  openModal,
})
class AnalyticsDesktop extends Component {
  constructor(props) {
    super(props);

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
      settlement_amount,
      defaultPreset,
      keymetricsSectionTitle,
      paymentInsightsTitle,
      recentActivityTitle,
      trafficSectionTitle,
    } = this.props;

    const { settlement_ux_revamp } = config.config;

    const hasSecondaryBanner =
      showInstantActivation && config.config && !config.config.hasPersonalised;

    const nextSettlement = !settlement_amount.data.next_settlement_time;
    const { no_settlement } = settlement_amount.data;

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
          {/* nps banner */}
          {user.isNPSSurveyBannerEnabled &&
            user.isAccepted && <NPSAnnouncement user={user} />}

          {showInstantActivation && (
            <Announcement mode={mode} user={user} payments={payments} />
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
                    <div style={{ textAlign: 'right' }}>
                      <span class="settlement-balance-amount">
                        Current Balance:{' '}
                        {!current_balance.loading && (
                          <Amount
                            value={current_balance.data.balance}
                            currency={'INR'}
                          />
                        )}
                      </span>
                      <br />
                      {no_settlement && settlement_ux_revamp ? (
                        <div class="text-right" style={{ width: '100%' }}>
                          {no_settlement.caption}
                          {no_settlement.reason && (
                            <React.Fragment>
                              <div style={{ display: 'inline' }}>
                                <i class="i i-info-circle" />
                                <Popover theme="dark" align="left">
                                  <PopoverBody>
                                    <div>{no_settlement.reason}</div>
                                  </PopoverBody>
                                </Popover>
                              </div>
                            </React.Fragment>
                          )}
                        </div>
                      ) : null}
                      {!no_settlement &&
                      !nextSettlement &&
                      settlement_ux_revamp ? (
                        <div class="text-right" style={{ width: '100%' }}>
                          <strong>
                            <Amount
                              value={settlement_amount.data.settlement_amount}
                              currency={'INR'}
                            />
                          </strong>{' '}
                          will be settled on{' '}
                          <Time
                            value={settlement_amount.data.next_settlement_time}
                            format={'DD MMM YYYY, hh:mm:ss a'}
                          />{' '}
                          {settlement_amount.data.reason_for_delay && (
                            <React.Fragment>
                              <div style={{ display: 'inline' }}>
                                <i class="i i-info-circle" />
                                <Popover theme="dark" align="left">
                                  <PopoverBody>
                                    <div>
                                      {settlement_amount.data.reason_for_delay}
                                    </div>
                                  </PopoverBody>
                                </Popover>
                              </div>
                            </React.Fragment>
                          )}
                          <span
                            class="btn-link"
                            style={{ marginLeft: '5px' }}
                            onClick={() => {
                              this.props.openModal({
                                size: 'regular',
                                component: (
                                  <SettlementDetail
                                    settlementAmount={settlement_amount.data}
                                  />
                                ),
                              });

                              window.rzpAnalytics({
                                eventCategory:
                                  'Dashboard - Settlement UI Revamp',
                                eventAction: 'Click Know More - Home Page',
                              });
                            }}
                          >
                            Know more
                          </span>
                        </div>
                      ) : null}
                    </div>
                  </GroupItem>
                )}
                <GroupItem>
                  {this.props.user.isOndemandSettlementEnabled ? (
                    <ShowWhen myRole="owner admin finance">
                      <Button.Secondary
                        class="settle-btn btn-outline"
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
        {settlement_ux_revamp && nextSettlement ? (
          <OnHoldBanner
            ctaOnClick={() => {
              this.props.openModal({
                size: 'regular',
                component: (
                  <SettlementDetail settlementAmount={settlement_amount.data} />
                ),
              });

              window.rzpAnalytics({
                eventCategory: 'Dashboard - Settlement UI Revamp',
                eventAction: 'Click Know More(On Hold) - Home Page',
              });
            }}
          />
        ) : null}
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
