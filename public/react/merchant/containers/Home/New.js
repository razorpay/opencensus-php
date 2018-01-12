import React, { Component } from 'react';
import Header from 'rzp/ui/Header';
import { Link } from 'react-router-dom';
import { connect } from 'react-redux';
import * as HomeActions from 'merchant/modules/home';
import moment from 'moment';

import Amount from 'rzp/ui/Amount';
import Sticky from 'rzp/ui/Sticky';
import Group, { GroupItem } from 'rzp/ui/Group';

import { fetch } from 'merchant/modules/pokedex';
import NewUserOnboardingCard from 'merchant/containers/Home/OnboardingCard';
import KeyMetrics from 'merchant/containers/Home/KeyMetrics';
import PaymentMethods from 'merchant/containers/Home/PaymentMethods';
import Traffic from 'merchant/containers/Home/Traffic';
import RecentActivity from 'merchant/containers/Home/RecentActivity';
import DateRangePicker from 'merchant/components/Home/DateRangePicker';
import { oldestTransactionQuery } from 'merchant/components/Home/data';

import './styles.styl';

const dateRangePresets = [
    ['One Day', -1, 'days'],
    ['Past 7 Days', -7, 'days'],
    ['Past 30 Days', -30, 'days'],
    ['Past 90 Days', -90, 'days'],
    ['All Time', -10, 'years'],
  ],
  defaultPreset = 2; // index of default preset

const oldestTransactionError = {
  message: 'Unable to get your first transaction date',
};

const getPreviousDates = ({ startDate, endDate }) => {
  const diff = endDate.diff(startDate);

  return {
    startDate: startDate.clone().subtract(diff, 'ms'),
    endDate: endDate.clone().subtract(diff, 'ms'),
  };
};

@connect(
  state => {
    return {
      user: state.session.user,
      mode: state.session.mode,
    };
  },
  {
    ...HomeActions,
  }
)
export default class HomeContainer extends Component {
  constructor(props) {
    super(props);

    let endDate = moment(),
      startDate = moment();

    startDate.add(...dateRangePresets[defaultPreset].slice(1));

    this.state = {
      startDate,
      endDate,
      oldestTransactionDate: {
        value: null,
        loading: true,
        ...getPreviousDates({ startDate, endDate }),
      },
    };

    this.onDatesChange = this.onDatesChange.bind(this);
  }

  fetchOldestTransactionDate() {
    const { oldestTransactionDate } = this.state;

    return fetch(oldestTransactionQuery)
      .then(data => {
        if (!data.success) {
          return oldestTransactionError;
        }

        const records = data.data.records.result[0],
          value = records && records.created_at,
          { oldestTransactionDate } = this.state;

        return { value };
      })
      .catch(() => {
        return oldestTransactionError;
      })
      .then(data => {
        const { oldestTransactionDate } = this.state;

        this.setState({
          oldestTransactionDate: {
            ...oldestTransactionDate,
            loading: false,
            value: data.value,
          },
        });

        if (data && data.error) {
          // TODO: Handle Error
        }
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
    this.props.fetchCurrentBalance();
    this.fetchOldestTransactionDate();
  }

  render() {
    let mode = this.props.mode;

    const { startDate, endDate, oldestTransactionDate } = this.state;

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
                    Current Balance: <Amount value={38760} />
                  </span>
                </GroupItem>
                <GroupItem>
                  <Link className="pull-right" to="/settlements">
                    View Settlements
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
              />
            </div>
          </div>

          <div className="row">
            <div className="col-md-12">
              <p className="section-title">Payment Insights</p>
            </div>
            <div className="col-md-12">
              <PaymentMethods startDate={startDate} endDate={endDate} />
            </div>
          </div>

          <div className="row">
            <div className="col-md-12 traffic-activity-row clearfix">
              <div className="traffic-container">
                <p className="content-title section-title">
                  Traffic split on platforms
                </p>
                <div className="content">
                  <Traffic startDate={startDate} endDate={endDate} />
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
        </div>
      </div>
    );
  }
}
