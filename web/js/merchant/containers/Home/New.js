import React, { Component } from 'react';
import Header from 'rzp/ui/Header';
import { Link } from 'react-router-dom';
import { connect } from 'react-redux';
import * as HomeActions from 'merchant/modules/home';
import moment from 'moment';
import { Redirect } from 'react-router-dom';

import Amount from 'rzp/ui/Amount';
import Sticky from 'rzp/ui/Sticky';
import Group, { GroupItem } from 'rzp/ui/Group';
import { showNotification } from 'rzp/modules/notifications';
import DateRangePicker, { customRangeText } from 'rzp/ui/DateRangePicker';
import {
  oldestTransactionQuery,
  getDefaultPaymentFilter,
  platformGroupingVals,
  groupByPlatform,
  OTHERS,
} from 'rzp/utils/pokedex';

import { fetch } from 'merchant/modules/pokedex';
import NewUserOnboardingCard from 'merchant/containers/Home/OnboardingCard';
import KeyMetrics from 'merchant/containers/Home/KeyMetrics';
import PaymentMethods from 'merchant/containers/Home/PaymentMethods';
import Traffic from 'merchant/containers/Home/Traffic';
import RecentActivity from 'merchant/containers/Home/RecentActivity';
import {
  OLDEST_TXN_ERROR,
  API_ERROR,
  API_INVALID_RESP,
  isMobileDevice
} from 'merchant/components/Home/data';
import GenericPanel, { PanelBody } from 'merchant/components/Home/GenericPanel';

import {
  trackDatesChange,
  trackPresetChange,
  trackSettlementsClick,
  trackPlatformAnalyticsHidden,
  trackForceOldDashboard
} from './ga';

const dateRangePresets = [
    ['Past 7 Days', -7, 'days'],
    ['Past 30 Days', -30, 'days'],
    ['Past 90 Days', -90, 'days'],
    ['All Time', -10, 'years'],
  ],
  defaultPreset = 1;

const getPreviousDates = ({ startDate, endDate }) => {
  const diff = endDate.diff(startDate);

  return {
    startDate: startDate.clone().subtract(diff, 'ms'),
    endDate: endDate
      .clone()
      .subtract(1, 'day')
      .subtract(diff, 'ms')
      .endOf('day'),
  };
};

const bodyClass = ' analytics-v2-active';

// used to show titles for sections and also GA
const keymetricsSectionTitle = 'Transactions Overview',
  paymentInsightsTitle = 'Payment Insights',
  trafficSectionTitle = 'Traffic split on platforms',
  recentActivityTitle = 'Recent Activity';

@connect(
  state => {
    return {
      user: state.session.user,
      mode: state.session.mode,
      current_balance: state.home.current_balance,
    };
  },
  {
    ...HomeActions,
    showNotification,
  }
)
class HomeContainer extends Component {
  constructor(props) {
    super(props);

    // recording new analytics interactions in hotjar
    if (typeof window.hj === 'function') {
      window.hj('trigger', 'new_analytics');
      window.hj('tagRecording', ['new_analytics']);
    }

    let endDate = moment().endOf('day'),
      startDate = endDate.clone().startOf('day');

    startDate.add(...dateRangePresets[defaultPreset].slice(1));

    this.state = {
      startDate,
      endDate,
      oldestTransactionDate: {
        value: null,
        loading: false,
        error: '',
        ...getPreviousDates({ startDate, endDate }),
      },
      dateRangePresets,
      showGrouping: false,
    };

    this.oldestTxnReqId = 0;
    this.onDatesChange = this.onDatesChange.bind(this);
  }

  fetchTxnsGroupedByPlatform() {
    // need to figureout whether we should show group by platform
    // or not

    const { startDate, endDate } = this.state,
          { isAdmin } = this.props;

    const query = {
      filters: {
        default: [getDefaultPaymentFilter(startDate.unix(), endDate.unix())],
      },
      aggregations: {
        records: {
          agg_type: 'count',
          details: {
            index: 'payments',
            group_by: platformGroupingVals,
          },
        },
      },
    };

    return fetch(query, this.props.mode)
      .then(data => {
        if (!data.success) {
          return API_ERROR;
        }

        if (!data.data || !data.data.records) {
          return API_INVALID_RESP;
        }

        data = groupByPlatform(data.data.records.result);

        return data;
      })
      .catch(err => {
        console.error(err);

        return API_ERROR;
      })
      .then(data => {
        if (data.error) {
          return this.props.showNotification({
            type: 'error',
            message: data.error,
          });
        }

        if (!isAdmin) {

          const platforms = Object.keys(data);

          // if we do not get platforms for given daterange
          // do not show grouping
          if (platforms.length === 0) {
            return;
          }

          let grandTotal = 0;

          const totalByPlatform = platforms.reduce((group, platform) => {
            group[platform] = data[platform].reduce((sum, entry) => {
              return sum + entry.value;
            }, 0);

            grandTotal += group[platform];

            return group;
          }, {});

          // if the txn count of platforms for given daterange
          // do not show grouping
          if (grandTotal === 0) {
            return;
          }

          const ratio = (totalByPlatform[OTHERS] || 0) / grandTotal;

          // if `Others` platform count is greater than 30%
          // do not show grouping
          if (ratio > 0.3) {
            trackPlatformAnalyticsHidden(ratio * 100);
            return;
          }
        }

        // this will show group by platform dropdowns and also
        // traffic graph
        this.setState({
          showGrouping: true,
        });
      });
  }

  fetchOldestTransactionDate() {
    let { oldestTransactionDate, dateRangePresets } = this.state;

    const { onFirstTxnDate } = this.props;

    var oldestTxnReqId = ++this.oldestTxnReqId;

    oldestTransactionDate = { ...oldestTransactionDate };

    oldestTransactionDate.error = '';
    oldestTransactionDate.loading = true;

    this.setState({
      oldestTransactionDate: { ...oldestTransactionDate },
    });

    return fetch(oldestTransactionQuery, this.props.mode)
      .then(data => {
        if (oldestTxnReqId !== this.oldestTxnReqId) {
          return null;
        }

        if (!data.success) {
          return OLDEST_TXN_ERROR;
        }

        if (!data.data || !data.data.records) {
          return API_INVALID_RESP;
        }

        const records = data.data.records.result[0],
          value = records && records.created_at;

        return { value };
      })
      .catch(err => {
        console.error(err);

        return OLDEST_TXN_ERROR;
      })
      .then(data => {

        oldestTransactionDate.loading = false;

        if (!data || data.error) {

          if (data.error) {
            oldestTransactionDate.error = data.error;

            this.props.showNotification({
              type: 'error',
              message: data.error,
            });
          }

          this.setState({
            oldestTransactionDate
          });

          return onFirstTxnDate && onFirstTxnDate();
        }

        const presetsLastIndex = dateRangePresets.length - 1,
          presetsLastItem = dateRangePresets[presetsLastIndex];

        // updates All Time present in daterange picker
        dateRangePresets = [...dateRangePresets];

        dateRangePresets.splice(presetsLastIndex, 1, [
          presetsLastItem[0],
          -(moment().unix() - data.value),
          'seconds',
        ]);

        this.setState({
          oldestTransactionDate: {
            ...oldestTransactionDate,
            value: data.value,
          },
          dateRangePresets,
        });

        return onFirstTxnDate && onFirstTxnDate(data.value);
      });
  }

  onDatesChange(startDate, endDate, selectedPreset) {
    const { oldestTransactionDate } = this.state;

    this.setState(
      {
        startDate,
        endDate,
        oldestTransactionDate: {
          ...oldestTransactionDate,
          ...getPreviousDates({ startDate, endDate }),
        }
      }
    );

    if (selectedPreset.name === customRangeText) {
      trackDatesChange(startDate, endDate);
    }
  }

  componentWillMount() {
    // to style react-power-selct specific to this tab
    document.body.className += bodyClass;

    this.props.fetchCurrentBalance();
    this.fetchOldestTransactionDate();
    this.fetchTxnsGroupedByPlatform();
  }

  componentWillUnmount() {
    document.body.className = document.body.className.replace(bodyClass, '');
  }

  render() {
    let {
      mode,
      current_balance,
      tabsMeta,
      isAdmin,
      onFilterChange
    } = this.props;

    const {
      startDate,
      endDate,
      oldestTransactionDate,
      dateRangePresets,
      showGrouping,
    } = this.state;

    return (
      <div class="react-root dashboard-home">
        <Sticky stickWhen={0} stickAt={50}>
          <Header className="clearfix" title="" showMode={false}>
            <div className="pull-left date-range-container">
              <DateRangePicker
                presets={dateRangePresets}
                onDatesChange={this.onDatesChange}
                defaultPreset={defaultPreset}
                onSelectPreset={trackPresetChange}
              />
            </div>
            <div className="pull-right">
              <Group>
                <GroupItem>
                  <span>
                    Current Balance:{' '}
                    {!current_balance.loading && (
                      <Amount value={current_balance.data.balance} />
                    )}
                  </span>
                </GroupItem>
                <GroupItem>
                  <Link className="pull-right" to="/settlements">
                    <span
                      className="text-no-wrap"
                      onClick={trackSettlementsClick}
                    >
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
                showGrouping={showGrouping}
                sectionTitle={keymetricsSectionTitle}
                tabsMeta={tabsMeta}
                isAdmin={isAdmin}
                onFilterChange={onFilterChange}
              />
            </div>
          </div>

          <div className="row">
            <div className="col-md-12">
              <p className="section-title">{paymentInsightsTitle}</p>
            </div>
            <div className="col-md-12">
              <PaymentMethods
                startDate={startDate}
                endDate={endDate}
                mode={mode}
                sectionTitle={paymentInsightsTitle}
              />
            </div>
          </div>

          <div className="row">
            <div
              className={`col-md-12 traffic-activity-row clearfix${showGrouping
                ? ''
                : ' traffic-hidden'}`}
            >
              {showGrouping && (
                <div className="traffic-container">
                  <p className="content-title section-title">
                    {trafficSectionTitle}
                  </p>
                  <div className="content">
                    <Traffic
                      startDate={startDate}
                      endDate={endDate}
                      mode={mode}
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
                    <RecentActivity sectionTitle={recentActivityTitle} />
                  </div>
                </div>
              )}
            </div>
          </div>

          <div className="row home-credits-section">
            <div className="col-md-12">
              <GenericPanel>
                <PanelBody>
                  <div className="text-center">
                    <small>
                      <i class="icon icon-info-circle" /> Please share your
                      feedback/suggestions by clicking the Feedback button on
                      the right edge of your screen. You could also write to us
                      at{' '}
                      <a target="_blank" href="mailto:support@razorpay.com">
                        support@razorpay.com
                      </a>.
                    </small>
                  </div>
                </PanelBody>
              </GenericPanel>
            </div>
          </div>
        </div>
      </div>
    );
  }
}

export default (props) => isMobileDevice
                       ? (trackForceOldDashboard(), <Redirect to="/dashboard"/>)
                       : <HomeContainer {...props}/>
