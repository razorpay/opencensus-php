import React, { Component } from 'react';
import Header from 'rzp/ui/Header';
import { connect } from 'react-redux';
import AsyncButton from 'react-async-button';
import * as HomeActions from 'merchant/modules/home';
import { Field } from 'redux-form';
import moment from 'moment';

import Amount from 'rzp/ui/Amount';
import Definition from 'rzp/ui/Definition';
import NewUserOnboardingCard from 'merchant/containers/Home/OnboardingCard';

import KeyMetrics from 'merchant/containers/Home/KeyMetrics';
import PaymentMethods from './PaymentMethods';
import Traffic from 'merchant/containers/Home/Traffic';
import RecentActivity from './RecentActivity';

import DateRangePicker from 'merchant/components/Home/DateRangePicker';
import RadioButton from 'rzp/ui/Forms/RadioButton';
import Sticky from 'rzp/ui/Sticky';
import Highcharts from 'rzp/ui/Highcharts';

import { getData } from 'merchant/models/HomeKeyMetricsMock';

import './styles.styl';

const dateRangePresets = [
  ['Past 7 days', -7, 'days'],
  ['Past 15 days', -15, 'days'],
  ['Past 1 month', -1, 'months'],
  ['Past 3 months', -3, 'months'],
  ['Past 6 months', -6, 'months'],
  ['Past 1 year', -1, 'years'],
];

const breakDownVals = [
  ['Days', 'daily'],
  ['Weeks', 'weekly'],
  ['Months', 'monthly'],
];

// graph data
// numbers
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

    const endDate = moment(),
      startDate = endDate.add(...dateRangePresets[0].slice(1)),
      selectedBreakdown = breakDownVals[0][1];

    this.state = {
      startDate,
      endDate,
      selectedBreakdown,
    };

    this.onDatesChange = this.onDatesChange.bind(this);
    this.onBreakdownChange = this.onBreakdownChange.bind(this);
  }

  onDatesChange(startDate, endDate) {
    this.setState({ startDate, endDate });
  }

  onBreakdownChange(e) {
    this.setState({ selectedBreakdown: e.target.value });
  }

  render() {
    let mode = this.props.mode;

    const { startDate, endDate, selectedBreakdown } = this.state;

    return (
      <div class="react-root">
        <Header title="Dashboard" showMode={true}>
          <div class="pull-right">
            <Definition>
              <span>
                Current Balance: <Amount value={38760} />
              </span>
              <span>Updated 10 mins ago</span>
            </Definition>
          </div>
        </Header>

        <Sticky stickWhen={68} stickAt={50}>
          <div className="dashboard-ctrl-bar clearfix">
            <div className="pull-left date-range-container">
              <DateRangePicker
                presets={dateRangePresets}
                onDatesChange={this.onDatesChange}
              />
            </div>
            <div className="pull-right">
              <div
                className="form form-horizontal breakdown-container"
                onChange={this.onBreakdownChange}
              >
                {breakDownVals.map((item, index) => {
                  return (
                    <div class="RadioButton" key={index}>
                      <label>
                        <input
                          type="radio"
                          name="breakdown"
                          value={item[1]}
                          checked={selectedBreakdown === item[1]}
                          readOnly={true}
                        />

                        <div>
                          <div class="RadioButton__button" />
                          <div class="RadioButton__label">
                            <div>
                              <span>{item[0]}</span>
                            </div>
                          </div>
                        </div>
                      </label>
                    </div>
                  );
                })}
              </div>
            </div>
          </div>
        </Sticky>

        <div className="dashboard">
          <div className="row">
            <div className="col-md-12">
              <KeyMetrics
                startDate={startDate}
                endDate={endDate}
                selectedBreakdown={selectedBreakdown}
              />
            </div>
          </div>

          <div className="row">
            <div className="col-md-12">
              <p>Payment methods drilldown</p>
            </div>
            <div className="col-md-12">
              <PaymentMethods startDate={startDate} endDate={endDate} />
            </div>
          </div>

          <div className="row">
            <div className="col-md-6">
              <Traffic startDate={startDate} endDate={endDate} />
            </div>

            <div className="col-md-6">
              <RecentActivity startDate={startDate} endDate={endDate} />
            </div>
          </div>
        </div>
      </div>
    );
  }
}
