import React, { Component } from 'react';
import Header from 'rzp/ui/Header';
import { connect } from 'react-redux';
import AsyncButton from 'react-async-button';
import * as HomeActions from 'merchant/modules/home';
import { Field } from 'redux-form';

import Amount from 'rzp/ui/Amount';
import Definition from 'rzp/ui/Definition';
import NewUserOnboardingCard from 'merchant/containers/Home/OnboardingCard';
import KeyMetrics from './KeyMetrics';
import DateRangePicker from 'merchant/components/Home/DateRangePicker';
import RadioButton from 'rzp/ui/Forms/RadioButton';
import Sticky from 'rzp/ui/Sticky';
import Highcharts from 'rzp/ui/Highcharts';

import './styles.styl';

const dateRangeOptions = [
  ['Past 7 days', -7, 'days'],
  ['Past 15 days', -15, 'days'],
  ['Past 1 month', -1, 'months'],
  ['Past 3 months', -3, 'months'],
  ['Past 6 months', -6, 'months'],
  ['Past 1 year', -1, 'years'],
];

const breakDownVals = [
  ['Days', 'days'],
  ['Weeks', 'weeks'],
  ['Months', 'months'],
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
  render() {
    let mode = this.props.mode;

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
            <div className="pull-left">
              <DateRangePicker
                presets={dateRangeOptions}
                onDatesChange={(s, e) => console.log(s, e)}
              />
            </div>
            <div className="pull-right">
              <div className="form form-horizontal">
                {breakDownVals.map((item, index) => {
                  return (
                    <div class="RadioButton" key={index}>
                      <label>
                        <input type="radio" name="breakdown" value={item[1]} />
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
              <KeyMetrics />
            </div>
          </div>
          <div className="row">
            <div className="col-md-12">
              <p>Payment methods drilldown</p>
            </div>
            <div className="col-md-12">
              <div className="panel">
                <div className="clearfix">
                  <div className="pull-left">Showing: All Payment Methods</div>
                  <div className="pull-right">...</div>
                  <div className="pull-right">
                    <select>
                      <option>By Transaction Volume</option>
                      <option>By Issuer</option>
                    </select>
                  </div>
                </div>
                <Highcharts />
              </div>
            </div>
          </div>
        </div>
      </div>
    );
  }
}
