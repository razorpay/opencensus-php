import React, { Component } from 'react';
import { Link } from 'react-router-dom';

import Header from 'rzp/ui/Header';
import Amount from 'rzp/ui/Amount';
import Sticky from 'rzp/ui/Sticky';
import DateRangePicker from 'rzp/ui/DateRangePicker';

import NewUserOnboardingCard from 'merchant/containers/Home/OnboardingCard';
import KeyMetrics from 'merchant/containers/Home/KeyMetrics';
import PaymentMethods from 'merchant/containers/Home/PaymentMethods';
import RecentActivity from 'merchant/containers/Home/RecentActivity';
import Traffic from 'merchant/containers/Home/Traffic';

import { trackPresetChange, trackSettlementsClick } from './ga';

class AnalyticsMobile extends Component {
  constructor(props) {
    super(props);
  }

  render() {
    const {
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
      showGroupingByPtfm,
      tabsMeta,
      analyticsFetch,
      onFilterChange,
      expandOnboardingBanner,
      showOnboardingBanner,
      payments,
      onHideOnboardingBanner,
      onFirstStepClose,
      showOnboardingBannerFirstStep,
      keymetricsSectionTitle,
      paymentInsightsTitle,
      recentActivityTitle,
      trafficSectionTitle,
    } = this.props;

    return (
      <div className="home-analytics-mobile">
        <div ref={node => onExtraContentMount(node)} className="extra-content">
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
              />
            )}
          </div>

          <Header className="clearfix" title="" showMode={false}>
            <div className="pull-left">
              Current Balance:{' '}
              {!current_balance.loading && (
                <b>
                  <Amount value={current_balance.data.balance} />
                </b>
              )}
            </div>
            <div className="pull-right">
              <Link className="pull-right" to="/settlements">
                <span className="text-no-wrap" onClick={trackSettlementsClick}>
                  View Settlements
                </span>
              </Link>
            </div>
          </Header>
          {!isAdmin && (
            <div className="content">
              <p className="section-title">{recentActivityTitle}</p>
              <RecentActivity
                sectionTitle={recentActivityTitle}
                onFetchPayments={onFetchPayments}
              />
            </div>
          )}
        </div>
        <Sticky stickWhen={scrollAmountToStickHeader} stickAt={50}>
          <Header className="clearfix" title="" showMode={false}>
            <div
              id="analytics-daterange-picker"
              className="date-range-container"
            >
              <DateRangePicker
                presets={dateRangePresets}
                onDatesChange={onDatesChange}
                defaultPreset={defaultPreset}
                onSelectPreset={trackPresetChange}
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
          <p className="section-title">{trafficSectionTitle}</p>
          <Traffic
            startDate={startDate}
            endDate={endDate}
            mode={mode}
            analyticsFetch={analyticsFetch}
            sectionTitle={''}
            isMobile={true}
          />
        </div>
      </div>
    );
  }
}

export default AnalyticsMobile;
