import React, { Component } from 'react';
import Header from 'rzp/ui/Header';
import { Link } from 'react-router-dom';
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

import { getData } from 'merchant/models/HomeKeyMetricsMock';

import './styles.styl';

const dateRangePresets = [
    ['One Day', -1, 'days'],
    ['Past 7 Days', -7, 'days'],
    ['Past 30 Days', -30, 'days'],
    ['Past 90 Days', -90, 'days'],
    ['All Time', -10, 'years'],
  ],
  defaultPreset = 2;

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
    };

    this.onDatesChange = this.onDatesChange.bind(this);
  }

  onDatesChange(startDate, endDate) {
    this.setState({ startDate, endDate });
  }

  render() {
    let mode = this.props.mode;

    const { startDate, endDate } = this.state;

    return (
      <div class="react-root">
        <Header className="clearfix" title="" showMode={false}>
          <div className="pull-left date-range-container">
            <DateRangePicker
              presets={dateRangePresets}
              onDatesChange={this.onDatesChange}
              defaultPreset={defaultPreset}
            />
          </div>
          <div className="pull-right">
            <Definition>
              <span>
                Current Balance: <Amount value={38760} />
              </span>
              <Link to="/settlements">View Settlements &gt;</Link>
            </Definition>
          </div>
        </Header>

        <div className="dashboard">
          <div className="row">
            <div className="col-md-12">
              <KeyMetrics startDate={startDate} endDate={endDate} />
            </div>
          </div>

          <div className="row hide">
            <div className="col-md-12">
              <p>Payment methods drilldown</p>
            </div>
            <div className="col-md-12" />
          </div>

          <div className="row">
            <div className="col-md-6">
              <p>Traffic split on platforms</p>
              <Traffic startDate={startDate} endDate={endDate} />
            </div>
            <div className="col-md-6" />
          </div>
        </div>
      </div>
    );
  }
}
