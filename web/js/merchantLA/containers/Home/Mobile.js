import React, { Component } from 'react';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';

import Amount from 'common/ui/Amount';
import DateRangePicker from 'common/ui/DateRangePicker';
import Header from 'common/ui/Header';
import Sticky from 'common/ui/Sticky';
import KeyMetrics from 'merchantLA/containers/Home/KeyMetrics';
import RecentActivity from 'merchantLA/containers/Home/RecentActivity';
import Traffic from 'merchantLA/containers/Home/Traffic';

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
      onFetchTransfers,
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
      recentActivityTitle,
      trafficSectionTitle,
      windowWidth,
    } = this.props;

    return (
      <div className="home-analytics-mobile">
        <div ref={(node) => onExtraContentMount(node)} className="extra-content">
          <Header className="clearfix" title="" showMode={false}>
            <div className="pull-left">
              Balance:{' '}
              <b>
                {!current_balance.loading && typeof current_balance.data.balance === 'number' && (
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
                onFetchTransfers={onFetchTransfers}
                isTabletResolution={true}
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

export default connect((state) => ({
  windowWidth: state.app.windowWidth,
}))(AnalyticsMobile);
