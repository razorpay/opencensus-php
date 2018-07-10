import React, { Component } from 'react';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';

import Header from 'rzp/ui/Header';
import Amount from 'rzp/ui/Amount';
import Sticky from 'rzp/ui/Sticky';
import DateRangePicker from 'rzp/ui/DateRangePicker';

import KeyMetrics from 'merchant/containers/Home/KeyMetrics';
import PaymentMethods from 'merchant/containers/Home/PaymentMethods';
import RecentActivity from 'merchant/containers/Home/RecentActivity';
import Traffic from 'merchant/containers/Home/Traffic';

import { trackPresetChange, trackSettlementsClick } from './ga';

@connect(state => ({
  windowWidth: state.app.windowWidth,
}))
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
      windowWidth,
    } = this.props;

    return (
      <div className="home-analytics-mobile">
        <div ref={node => onExtraContentMount(node)} className="extra-content">
          <Header className="clearfix" title="" showMode={false}>
            <div className="pull-left">
              Balance:{' '}
              <b>
                {!current_balance.loading &&
                  typeof current_balance.data.balance === 'number' && (
                    <Amount value={current_balance.data.balance} />
                  )}
              </b>
            </div>
            <div className="pull-right">
              <Link className="pull-right" to="/settlements">
                <span className="text-no-wrap" onClick={trackSettlementsClick}>
                  View Settlements <i className="i i-chevron-right" />
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
                isTabletResolution={true}
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
                numberOfMonths={1}
                horizontalMargin={
                  // adjusting the right position of datepicker so that
                  // it does not overflow, for screen resolution <= 424
                  // presets are hidden , so not adjusting the DRP
                  windowWidth < 530
                    ? windowWidth > 424
                      ? 530 - windowWidth
                      : windowWidth > 360 ? 40 : 57
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
