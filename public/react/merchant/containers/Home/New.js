import React, { Component } from 'react';
import Header from 'rzp/ui/Header';
import { Link } from 'react-router-dom';
import { connect } from 'react-redux';
import * as HomeActions from 'merchant/modules/home';
import moment from 'moment';

import Amount from 'rzp/ui/Amount';
import Sticky from 'rzp/ui/Sticky';
import Group, { GroupItem } from 'rzp/ui/Group';
import { showNotification } from 'rzp/modules/notifications';
import DateRangePicker from 'rzp/ui/DateRangePicker';
import {oldestTransactionQuery} from 'rzp/utils/pokedex';

import { fetch } from 'merchant/modules/pokedex';
import NewUserOnboardingCard from 'merchant/containers/Home/OnboardingCard';
import KeyMetrics from 'merchant/containers/Home/KeyMetrics';
import PaymentMethods from 'merchant/containers/Home/PaymentMethods';
import Traffic from 'merchant/containers/Home/Traffic';
import RecentActivity from 'merchant/containers/Home/RecentActivity';
import {
  OLDEST_TXN_ERROR,
  API_INVALID_RESP,
} from 'merchant/components/Home/data';
import GenericPanel, {
  PanelBody,
} from 'merchant/components/Home/GenericPanel';


import './styles.styl';

const dateRangePresets = [
    ['One Day', -1, 'days'],
    ['Past 7 Days', -7, 'days'],
    ['Past 30 Days', -30, 'days'],
    ['Past 90 Days', -90, 'days'],
    ['All Time', -10, 'years'],
  ],
  defaultPreset = 2;

const getPreviousDates = ({ startDate, endDate }) => {
  const diff = endDate.diff(startDate);

  return {
    startDate: startDate.clone()
                        .subtract(diff, 'ms'),
    endDate: endDate.clone()
                    .subtract(1, 'day')
                    .subtract(diff, 'ms')
                    .endOf("day"),
  };
};

const bodyClass = ' analytics-v2-active';

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
export default class HomeContainer extends Component {
  constructor(props) {
    super(props);

    // recording new analytics interactions in hotjar
    if (typeof window.hj === "function") {
    
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
    };

    this.oldestTxnReqId = 0;
    this.onDatesChange = this.onDatesChange.bind(this);
  }

  fetchOldestTransactionDate() {
    let { oldestTransactionDate, dateRangePresets } = this.state;

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
        if (!data) {
          return;
        }

        oldestTransactionDate.loading = false;

        if (data.error) {
          oldestTransactionDate.error = data.error;

          this.props.showNotification({
            type: 'error',
            message: data.error,
          });
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
      });
  }

  onDatesChange(startDate, endDate) {
    const { oldestTransactionDate } = this.state;

    this.setState(
      {
        startDate,
        endDate,
        oldestTransactionDate: {
          ...oldestTransactionDate,
          ...getPreviousDates({ startDate, endDate }),
        },
      },
      () => {
        this.fetchOldestTransactionDate();
      }
    );
  }

  componentWillMount() {
    // to style react-power-selct specific to this tab
    document.body.className += bodyClass;

    this.props.fetchCurrentBalance();
    this.fetchOldestTransactionDate();
  }

  componentWillUnmount() {
    document.body.className = document.body.className.replace(bodyClass, '');
  }

  render() {
    let {mode, current_balance} = this.props;

    const {
      startDate,
      endDate,
      oldestTransactionDate,
      dateRangePresets,
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
              />
            </div>
            <div className="pull-right">
              <Group>
                <GroupItem>
                  <span>
                    Current Balance: {
                      !current_balance.loading &&
                      <Amount value={current_balance.data.balance} />
                    }
                  </span>
                </GroupItem>
                <GroupItem>
                  <Link className="pull-right" to="/settlements">
                    <span className="text-no-wrap">View Settlements</span>
                  </Link>
                </GroupItem>
              </Group>
            </div>
          </Header>
        </Sticky>

        <div className="dashboard">
          <div className="row">
            <div className="col-md-12">
              <p className="section-title keymetrics-title">
                Transactions Overview
              </p>
            </div>
            <div className="col-md-12">
              <KeyMetrics
                startDate={startDate}
                endDate={endDate}
                oldestTransactionDate={oldestTransactionDate}
                mode={mode}
              />
            </div>
          </div>

          <div className="row">
            <div className="col-md-12">
              <p className="section-title">Payment Insights</p>
            </div>
            <div className="col-md-12">
              <PaymentMethods
                startDate={startDate}
                endDate={endDate}
                mode={mode}
              />
            </div>
          </div>

          <div className="row">
            <div className="col-md-12 traffic-activity-row clearfix">
              <div className="traffic-container">
                <p className="content-title section-title">
                  Traffic split on platforms
                </p>
                <div className="content">
                  <Traffic
                    startDate={startDate}
                    endDate={endDate}
                    mode={mode}
                  />
                </div>
              </div>
              <div className="activity-container">
                <p className="content-title section-title">Recent Activity</p>
                <div className="content">
                  <RecentActivity />
                </div>
              </div>
            </div>
          </div>
          <div className="row home-credits-section">
            <div className="col-md-12">
              <GenericPanel>
                <PanelBody>
                  <div className="text-center">
                    <small>
                      <i class="icon icon-info-circle"></i> Please share your feedback/suggestions by clicking the Feedback button on the right edge of your screen. You could also write to us at <a target="_blank" href="mailto:support@razorpay.com">support@razorpay.com</a>.
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
