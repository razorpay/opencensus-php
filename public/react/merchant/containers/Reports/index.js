import React, { Component } from 'react';
import { connect } from 'react-redux';
import { NavLink } from 'react-router-dom';
import { Field, reduxForm, formValueSelector } from 'redux-form';
import { titleCase } from 'rzp/utils/rzp-utils';
import AsyncButton from 'react-async-button';
import moment from 'moment';
import ajax from 'merchant/utils/ajax';
import { generateReport } from 'merchant/modules/reports';
import { fetchAccounts } from 'merchant/modules/marketplace/accounts';
import * as NotificationsActions from 'rzp/modules/notifications';
import Datetime from 'react-datetime';

// TODO: Use them to set initial values in date-picker
let now = moment();
let currentMonth = now.month();
let currentYear = now.year();
let currentDate = now.date();

/*
function numberOfDays(month, year) {
  return moment(year + ' ' + month, 'YYYY M').daysInMonth();
}
*/

function validYear(current) {
  return current.year() >= 2015 && current.year() <= 2017;
}

const selector = formValueSelector('generateReports');

@connect(
  state => {
    return {
      mode: state.session.mode,
      user: state.session.user,
      account: state.accounts,
      entity: selector(state, 'entity'),
      type: selector(state, 'type'),
      month: selector(state, 'month'),
      year: selector(state, 'year'),
    };
  },
  { generateReport, fetchAccounts, ...NotificationsActions }
)
@reduxForm({
  form: 'generateReports',
  initialValues: {
    entity: 'payment',
    type: 'monthly',
    month: currentMonth,
    year: currentYear,
    day: currentDate,
  },
})
export default class ReportsContainer extends Component {
  componentWillMount() {
    this.props.fetchAccounts();
  }
  prepareGenerateReport = values => {
    let { entity, type, month, year, day, account_id } = values;

    let data = {
      month,
      year,
    };

    if (entity === 'invoice') {
      return Promise.resolve(
        window.open(
          `/${this.props.mode}/reports/invoice?year=${year}&month=${month}`,
          '_blank'
        )
      );
    }

    if (type === 'daily') {
      data.day = day;
    }

    var ajaxParams = {
      url: '/reports/' + entity,
      data: data,
    };

    if (account_id !== this.props.user.id) {
      data.account_id = account_id;
    }

    if (entity === 'broking') {
      ajaxParams.headers = {
        Accept: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
      };
    }

    return this.props
      .generateReport(ajaxParams)
      .then(data => {
        this.props.showNotification({
          type: 'success',
          message: 'Your report will download shortly',
        });

        if (entity === 'broking') {
          var blob = new Blob([data], {
            type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
          });
          return saveAs(blob, 'broking_report.xlsx');
        }

        location.href = data.data.url;
      })
      .catch(e => {
        this.props.showNotification({
          type: 'error',
          message: 'No data found for given time range',
        });
      });
  };

  render() {
    let { entity, type, mode, month, year, user, handleSubmit } = this.props;
    user.tags.push('Marketplace');

    let isMarketplace = user.tags.indexOf('Marketplace') !== -1;
    isMarketplace = true;

    // console.log('USER', user);
    return (
      <tabbed-container>
        <header>
          <NavLink to="/reports">Download Reports</NavLink>
        </header>
        <div class="report-wrapper col-lg-8 col-sm-10 col-xs-11">
          {/*Report Type Selection*/}
          <div class="col-lg-4 col-md-4 col-sm-12 col-xs-12 report-list-panel">
            <div class="title">SELECT REPORT TYPE</div>
            <Field
              id="combined"
              name="entity"
              value="combined"
              component="input"
              type="radio"
              class="report-type form-control"
            />
            <label for="combined">Combined Report</label>
            <Field
              id="payment"
              name="entity"
              value="payment"
              component="input"
              type="radio"
              class="report-type form-control"
            />
            <label for="payment">Payment</label>
            <Field
              id="refund"
              name="entity"
              value="refund"
              component="input"
              type="radio"
              class="report-type form-control"
            />
            <label for="refund">Refund</label>
          </div>

          {/*Report Generate Panel*/}
          <div class="col-lg-8 col-md-8 col-sm-12 col-xs-12 report-generate-panel">

            <div class="title">
              {isMarketplace ? 'SELECT ' : ''}ACCOUNT
            </div>

            {isMarketplace === false
              ? <div class="account">
                  <strong>{user.name || user.user.name}</strong>
                  {' '}
                  -
                  {' '}
                  {user.email || user.user.email}
                </div>
              : <Field
                  component="select"
                  class="form-control"
                  name="account_id"
                >
                  <option value={user.id || user.user.id} disabled selected>
                    Current Account
                  </option>
                  <option class="divider" disabled />
                  <option value="acc_89wltQH83y3z7s">
                    89wltQH83y3z7s
                  </option>
                </Field>}

            <div class="title">
              DATE RANGE
            </div>
            {entity === 'invoice' ||
              <div class="col-sm-4">
                <div class="form-group">
                  <Field name="type" class="form-control" component="select">
                    <option value="daily">Daily</option>
                    <option value="monthly">Monthly</option>
                  </Field>
                </div>
              </div>}

            {type === 'monthly' &&
              <div class="col-sm-6">
                <div class="form-group">
                  <Field
                    name="date"
                    component={() => (
                      <Datetime
                        dateFormat="MMM, YYYY"
                        inputProps={{
                          placeholder: 'Select Year-Month',
                        }}
                        isValidDate={validYear}
                        timeFormat={false}
                      />
                    )}
                    class="form-control"
                  />
                </div>
              </div>}

            {type === 'daily' &&
              <div class="col-sm-6">
                <div class="form-group">
                  <Field
                    name="date"
                    component={() => (
                      <Datetime
                        dateFormat="DD MMM, YYYY"
                        inputProps={{
                          placeholder: 'Select Year-Month-Date',
                        }}
                        isValidDate={validYear}
                        timeFormat={false}
                      />
                    )}
                    class="form-control"
                  />
                </div>
              </div>}

            {/*           <div class="col-sm-4">
              <div class="form-group">
                <Field name="year" class="form-control" component="select">
                  <option value="2017">2017</option>
                  <option value="2016">2016</option>
                  <option value="2015">2015</option>
                </Field>
              </div>
            </div>
*/}

            {/*
            <div class="col-sm-4">
              <div class="form-group">
                <Field name="month" class="form-control" component="select">
                  {moment.months().map((name, index) => {
                    return (
                      <option value={index + 1} key={index}>
                        {name}
                      </option>
                    );
                  })}
                </Field>
              </div>
            </div>
*/}

            {/*
            {type === 'daily' &&
            <div class="col-sm-4">
              <div class="form-group">
                <Field name="day" class="form-control" component="select">
                  {Array.from(
                    Array(numberOfDays(month, year)),
                    (undef, index) => {
                      return (
                        <option value={index + 1} key={index}>
                          {index + 1}
                        </option>
                      );
                    }
                  )}
                </Field>
              </div>
            </div>}
*/}
          </div>
        </div>
      </tabbed-container>
    );
  }
}
