import React, { Component } from 'react';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';

import Amount from 'common/ui/Amount';
import Banner from 'common/ui/Banner';
import DateRangePicker from 'common/ui/DateRangePicker';
import Group, { GroupItem } from 'common/ui/Group';
import Header from 'common/ui/Header';
import Sticky from 'common/ui/Sticky';
import LocalStorageService from 'common/utils/localStorage';
import KeyMetrics from 'merchantLA/containers/Home/KeyMetrics';
import RecentActivity from 'merchantLA/containers/Home/RecentActivity';
import Traffic from 'merchantLA/containers/Home/Traffic';
import { showOrHideTour } from 'merchantLA/reducers/session';

import { trackPresetChange, trackSettlementsClick, trackViewTour } from './ga';

class AnalyticsDesktop extends Component {
  constructor(props) {
    super(props);

    const { isAdmin, mode } = props;

    this.state = {
      hasNewAnalyticsTour:
        !isAdmin && mode === 'live' && !LocalStorageService.getItem('hide_new_analytics_banner'),
      dismissNewAnalyticsBanner: false, // used for transition
    };

    this.onShowTour = this.onShowTour.bind(this);
    this.onHideNewAnalyticsBanner = this.onHideNewAnalyticsBanner.bind(this);
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
            },
          );
        }, 500); // let the trasition to hide banner complete
      },
    );
  }

  render() {
    const {
      mode,
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
      onDatesChange,
      onFetchTransfers,
      onExtraContentMount,

      defaultPreset,
      keymetricsSectionTitle,
      recentActivityTitle,
      trafficSectionTitle,
      user,
    } = this.props;

    const { hasNewAnalyticsTour, dismissNewAnalyticsBanner } = this.state;

    const { onShowTour, onHideNewAnalyticsBanner } = this;

    return (
      <div className="home-analytics-desktop">
        <div ref={(node) => onExtraContentMount(node)} className="extra-content">
          {!isAdmin && (
            <div>
              {hasNewAnalyticsTour && (
                <div className={`v2-tour-banner${dismissNewAnalyticsBanner ? ' dismiss' : ''}`}>
                  <div className="banner-icon">
                    <i className="i i-loudspeaker" />
                  </div>
                  <div className="banner-content">
                    <Banner cta="View Tour" ctaOnClick={onShowTour}>
                      <span>
                        Take a quick tour to learn how to use dashboard analytics effectively.
                      </span>
                    </Banner>
                  </div>
                  <div className="banner-close">
                    <a className="banner-close-icon" onClick={onHideNewAnalyticsBanner}>
                      <i className="i i-close" />
                    </a>
                  </div>
                </div>
              )}
            </div>
          )}
        </div>
        <Sticky stickWhen={scrollAmountToStickHeader} stickAt={50}>
          <Header className="clearfix" title="" showMode={false}>
            <div id="analytics-daterange-picker" className="pull-left date-range-container">
              <DateRangePicker
                presets={dateRangePresets}
                onDatesChange={onDatesChange}
                defaultPreset={defaultPreset}
                onSelectPreset={trackPresetChange}
              />
            </div>
            <div className="pull-right">
              <Group>
                <GroupItem>
                  <span className="balance-amount">
                    Current Balance:
                    {!current_balance.loading && (
                      <Amount
                        value={current_balance.data.balance}
                        currency={user?.merchant?.currency || 'INR'}
                      />
                    )}
                  </span>
                </GroupItem>
                <GroupItem>
                  <Link className="pull-right" to="/settlements">
                    <span className="text-no-wrap" onClick={trackSettlementsClick}>
                      View Settlements
                    </span>
                  </Link>
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
                      sectionTitle={recentActivityTitle}
                      onFetchTransfers={onFetchTransfers}
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

export default connect(
  (state) => {
    return {
      user: state.session.user,
    };
  },
  { showOrHideTour },
)(AnalyticsDesktop);
